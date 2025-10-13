#!/usr/bin/env bash
set -Eeuo pipefail

# DevGenie Full Installer for Apache+mod_wsgi+Flask+SSL
# - Creates correct Apache vhost config for mod_wsgi and SSL
# - Creates the .wsgi entrypoint file
# - Sets up SSL, Python venv, DB, permissions, etc.

gen_password() {
  LC_ALL=C tr -dc 'A-Za-z0-9' </dev/urandom 2>/dev/null | head -c 24 || echo "fallbackpassword123ABC"
}
normalize_for_mysql() {
  local s="${1//[^a-zA-Z0-9]/_}"
  echo "${s:0:32}"
}
ask() {
  local prompt="$1"
  local default="${2:-}"
  local __outvar="$3"
  local reply
  if [[ -n "$default" ]]; then
    read -r -p "$prompt [$default]: " reply || true
    reply="${reply:-$default}"
  else
    read -r -p "$prompt: " reply || true
  fi
  printf -v "$__outvar" '%s' "$reply"
}
ask_hidden() {
  local prompt="$1"
  local __outvar="$2"
  local reply
  read -r -s -p "$prompt: " reply || true
  echo
  printf -v "$__outvar" '%s' "$reply"
}
require_root() {
  if [[ $EUID -ne 0 ]]; then
    echo "Error: This script must be run as root. Try: sudo bash $0" >&2
    exit 1
  fi
}
test_mysql_connection() {
  local dbuser="$1"
  local dbpass="$2"
  local dbname="$3"
  mysql -u "$dbuser" -p"$dbpass" -e "USE \`${dbname}\`;" 2>/dev/null
}

require_root

echo "DevGenie Installer (Apache + mod_wsgi + Flask + SSL)"
echo "-----------------------------------------------------"

# 1. PROMPT FOR DOMAIN AND PATHS
ask "Enter domain or subdomain for DevGenie (e.g. devgenie.yourdomain.com)" "devgenie.local" GENIE_DOMAIN
GENIE_WEBROOT="/var/www/${GENIE_DOMAIN}"
APPDIR="$GENIE_WEBROOT/DevGenie"
VENV="$APPDIR/venv"

# 2. CHECK FOR EXISTING FOLDER
if [ -d "$GENIE_WEBROOT" ]; then
  echo "Webroot $GENIE_WEBROOT exists. Remove and recreate? (y/n)"
  read -r CONFIRM
  if [[ "$CONFIRM" =~ ^[Yy]$ ]]; then
    rm -rf "$GENIE_WEBROOT"
    echo "Removed $GENIE_WEBROOT."
  else
    echo "Aborting setup."
    exit 1
  fi
fi

mkdir -p "$GENIE_WEBROOT"
chown -R www-data:www-data "$GENIE_WEBROOT"

# 3. INSTALL SYSTEM DEPENDENCIES
export DEBIAN_FRONTEND=noninteractive
apt-get update
apt-get install -y apache2 mysql-server python3 python3-pip python3-venv \
  libapache2-mod-wsgi-py3 openssl git certbot python3-certbot-apache \
  pkg-config libmysqlclient-dev

# 4. CLONE OR UPDATE REPO
if [ ! -d "$APPDIR" ]; then
  git clone https://github.com/andrew-kemp/DevGenie.git "$APPDIR"
else
  cd "$APPDIR"
  git pull
  cd -
fi

# 5. PYTHON VIRTUALENV & DEPENDENCIES
cd "$APPDIR"
python3 -m venv venv
source venv/bin/activate
pip install --upgrade pip
pip install -r requirements.txt

# 6. DATABASE SETUP
DB_NAME="devgenie"
DB_USER="devgenie"
ask_hidden "Enter MySQL DB password for devgenie (leave blank to auto-generate)" DB_PASS
if [[ -z "$DB_PASS" ]]; then
  DB_PASS="$(gen_password)"
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

echo "Testing database connection..."
if ! test_mysql_connection "$DB_USER" "$DB_PASS" "$DB_NAME"; then
  echo "ERROR: Could not connect to database as $DB_USER using specified password."
  exit 1
fi
echo "Database connection successful."

# 7. IMPORT DB SCHEMA
mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < scripts/schema.sql

# 8. CREATE .env WITH CORRECT PERMISSIONS
cat > .env <<EOF
FLASK_ENV=production
SECRET_KEY=$(openssl rand -hex 16)
DATABASE_URL=mysql://$DB_USER:$DB_PASS@localhost/$DB_NAME
EOF
chown www-data:www-data .env
chmod 600 .env

# 9. CREATE THE .wsgi FILE FOR APACHE+mod_wsgi
WSGI_FILE="$APPDIR/devgenie.wsgi"
cat > "$WSGI_FILE" <<EOF
import sys
import os

sys.path.insert(0, '$APPDIR')
os.environ['FLASK_ENV'] = 'production'

from app import app as application  # Change if your app's main file/object is different
EOF
chown www-data:www-data "$WSGI_FILE"
chmod 644 "$WSGI_FILE"

# 10. CREATE (OR OVERWRITE) APACHE VHOST CONF FILE FOR MOD_WSGI + SSL
VHOST_FILE="/etc/apache2/sites-available/${GENIE_DOMAIN}.conf"
cat > "$VHOST_FILE" <<EOF
<VirtualHost *:80>
    ServerName ${GENIE_DOMAIN}
    DocumentRoot ${APPDIR}

    <Directory ${APPDIR}>
        Require all granted
        Options -Indexes
    </Directory>

    WSGIDaemonProcess devgenie user=www-data group=www-data threads=5 python-home=${VENV}
    WSGIScriptAlias / ${WSGI_FILE}

    ErrorLog \${APACHE_LOG_DIR}/${GENIE_DOMAIN}_error.log
    CustomLog \${APACHE_LOG_DIR}/${GENIE_DOMAIN}_access.log combined
</VirtualHost>

# The following SSL VirtualHost will be enabled by certbot automatically.
# If you want to pre-create it, uncomment and adapt:
#
# <IfModule mod_ssl.c>
# <VirtualHost *:443>
#     ServerName ${GENIE_DOMAIN}
#     DocumentRoot ${APPDIR}
#     <Directory ${APPDIR}>
#         Require all granted
#         Options -Indexes
#     </Directory>
#     WSGIDaemonProcess devgenie-ssl user=www-data group=www-data threads=5 python-home=${VENV}
#     WSGIScriptAlias / ${WSGI_FILE}
#     ErrorLog \${APACHE_LOG_DIR}/${GENIE_DOMAIN}_ssl_error.log
#     CustomLog \${APACHE_LOG_DIR}/${GENIE_DOMAIN}_ssl_access.log combined
#     SSLEngine on
#     SSLCertificateFile /etc/letsencrypt/live/${GENIE_DOMAIN}/fullchain.pem
#     SSLCertificateKeyFile /etc/letsencrypt/live/${GENIE_DOMAIN}/privkey.pem
#     Include /etc/letsencrypt/options-ssl-apache.conf
# </VirtualHost>
# </IfModule>
EOF

a2ensite "${GENIE_DOMAIN}.conf"
a2enmod wsgi
a2enmod rewrite
a2enmod ssl
systemctl reload apache2

# 11. SSL SETUP WITH CERTBOT
echo
read -p "Do you want to set up SSL with Let's Encrypt now? (y/n) [y]: " SSL_SETUP
SSL_SETUP="${SSL_SETUP,,}"
SSL_SETUP="${SSL_SETUP:-y}"
if [[ "${SSL_SETUP}" == "y" ]]; then
    certbot --apache -d "${GENIE_DOMAIN}"
    systemctl reload apache2
    echo "SSL configured with Let's Encrypt."
else
    echo "You can set up SSL later with certbot."
fi

# 12. REMOVE SYSTEMD FLASK RUN SERVICE IF IT EXISTS
systemctl stop devgenie || true
systemctl disable devgenie || true

echo
echo "==== DevGenie installation complete! ===="
echo "App location: $APPDIR"
echo "Virtualenv:   $VENV"
echo "Database user: $DB_USER"
echo "Database password: $DB_PASS"
echo
echo "App is running under Apache with mod_wsgi and SSL."
echo "You do NOT need to run 'flask run'."
echo "Check logs: sudo tail -f /var/log/apache2/${GENIE_DOMAIN}_error.log"
echo
echo "Visit https://${GENIE_DOMAIN}/setup to complete setup."
echo "==== All done! ===="