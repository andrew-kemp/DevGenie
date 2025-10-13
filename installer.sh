#!/bin/bash
set -euo pipefail

# DevGenie Robust Installer: nginx + systemd + Flask + MySQL + HTTPS

DOMAIN_DEFAULT="devgenie.local"
INSTALL_PARENT="/var/www"
REPO_URL="https://github.com/andrew-kemp/DevGenie.git"
APP_SUBDIR="DevGenie"
VENV_SUBDIR="venv"
FLASK_PORT=5000
SYSTEMD_SERVICE="devgenie"
DB_NAME="devgenie"
DB_USER="devgenie"

echo "----------------------------------------------------"
echo "         DevGenie Automated Installer"
echo "----------------------------------------------------"

if [ "$EUID" -ne 0 ]; then
  echo "Please run as root (use sudo)"
  exit 1
fi

# 1. --- Prompt for domain name ---
read -rp "Enter domain or subdomain for DevGenie [${DOMAIN_DEFAULT}]: " GENIE_DOMAIN
GENIE_DOMAIN="${GENIE_DOMAIN:-$DOMAIN_DEFAULT}"

INSTALL_DIR="${INSTALL_PARENT}/${GENIE_DOMAIN}"
APP_DIR="${INSTALL_DIR}/${APP_SUBDIR}"
VENV_DIR="${APP_DIR}/${VENV_SUBDIR}"

sudo mkdir -p "$INSTALL_DIR"
sudo chown www-data:www-data "$INSTALL_DIR"

# 2. --- Remove Apache if present and stop it ---
if systemctl is-active --quiet apache2; then
  echo "Stopping Apache (not needed)..."
  systemctl stop apache2
  systemctl disable apache2
fi

# 3. --- Install dependencies (including nginx) ---
echo "Installing dependencies..."
apt-get update
apt-get install -y python3 python3-pip python3-venv git nginx mysql-server \
    openssl certbot python3-certbot-nginx pkg-config libmysqlclient-dev

# 4. --- Clone or update repo as www-data ---
echo "Cloning or updating DevGenie repository..."
if [ ! -d "$APP_DIR" ]; then
    sudo -u www-data git clone "$REPO_URL" "$APP_DIR"
    sudo chown -R www-data:www-data "$APP_DIR"
else
    cd "$APP_DIR"
    sudo -u www-data git pull
    cd -
    sudo chown -R www-data:www-data "$APP_DIR"
fi

# 5. --- Set up Python venv and requirements ---
echo "Setting up Python virtual environment..."
sudo -u www-data python3 -m venv "$VENV_DIR"
sudo -u www-data bash -c "source $VENV_DIR/bin/activate && pip install --upgrade pip && pip install -r $APP_DIR/requirements.txt"

# 6. --- Database Setup ---
echo "Setting up MySQL database..."
read -rsp "Enter MySQL DB password for devgenie (leave blank to auto-generate): " DB_PASS
echo
if [[ -z "$DB_PASS" ]]; then
    DB_PASS="$(LC_ALL=C tr -dc 'A-Za-z0-9' </dev/urandom | head -c 24)"
    echo "Generated DB password: $DB_PASS"
fi

systemctl enable --now mysql
mysql -u root <<SQL
CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost';
ALTER USER '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
SQL

# 7. --- Import schema if present ---
if [ -f "$APP_DIR/scripts/schema.sql" ]; then
    echo "Importing database schema..."
    mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < "$APP_DIR/scripts/schema.sql"
fi

# 8. --- Create .env for the app ---
echo "Creating .env file..."
sudo -u www-data bash -c "cat > $APP_DIR/.env" <<EOF
FLASK_ENV=production
SECRET_KEY=$(openssl rand -hex 16)
DATABASE_URL=mysql://$DB_USER:$DB_PASS@localhost/$DB_NAME
EOF
sudo chown www-data:www-data "$APP_DIR/.env"
sudo chmod 600 "$APP_DIR/.env"

# 9. --- Create systemd service file for Flask app ---
echo "Creating systemd service for DevGenie..."
sudo tee /etc/systemd/system/${SYSTEMD_SERVICE}.service > /dev/null <<EOF
[Unit]
Description=DevGenie Flask App
After=network.target

[Service]
User=www-data
Group=www-data
WorkingDirectory=$APP_DIR
Environment="PATH=$VENV_DIR/bin"
Environment="FLASK_ENV=production"
ExecStart=$VENV_DIR/bin/python $APP_DIR/run.py
Restart=always

[Install]
WantedBy=multi-user.target
EOF

systemctl daemon-reload
systemctl enable "${SYSTEMD_SERVICE}"
systemctl restart "${SYSTEMD_SERVICE}"

# 10. --- Configure nginx reverse proxy ---
echo "Configuring nginx reverse proxy..."
NGINX_CONF="/etc/nginx/sites-available/devgenie.conf"
sudo tee "$NGINX_CONF" > /dev/null <<NGINXCONF
server {
    listen 80;
    server_name ${GENIE_DOMAIN};

    location / {
        proxy_pass http://127.0.0.1:${FLASK_PORT};
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
    }
}
NGINXCONF

sudo ln -sf "$NGINX_CONF" "/etc/nginx/sites-enabled/devgenie.conf"
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t && sudo systemctl reload nginx

# 11. --- Obtain and configure SSL certificate with Let's Encrypt ---
echo
read -rp "Do you want to set up SSL with Let's Encrypt now? (y/n) [y]: " DO_SSL
DO_SSL="${DO_SSL,,}"; DO_SSL="${DO_SSL:-y}"
if [[ "$DO_SSL" == "y" ]]; then
    certbot --nginx -d "${GENIE_DOMAIN}" --non-interactive --agree-tos -m admin@${GENIE_DOMAIN} --redirect
    systemctl reload nginx
    echo "SSL configured with Let's Encrypt."
else
    echo "You can set up SSL later with certbot."
fi

echo
echo "==== DevGenie installation complete! ===="
echo "App location: $APP_DIR"
echo "Virtualenv:   $VENV_DIR"
echo "Database user: $DB_USER"
echo "Database password: $DB_PASS"
echo
echo "App is running as a systemd service: sudo systemctl status ${SYSTEMD_SERVICE}"
echo "Access it at: https://${GENIE_DOMAIN}/setup"
echo "If not using SSL yet: http://${GENIE_DOMAIN}/setup"
echo
echo "Check logs:"
echo "  App:   sudo journalctl -u ${SYSTEMD_SERVICE} -f"
echo "  nginx: sudo tail -f /var/log/nginx/error.log"
echo
echo "==== All done! ===="