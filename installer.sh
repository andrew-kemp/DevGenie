#!/bin/bash
set -e

echo "==== DevGenie Enhanced Installer ===="

# 1. Update packages and install system dependencies
echo "[*] Updating package list and installing dependencies..."
sudo apt-get update

sudo apt-get install -y apache2 mysql-server python3 python3-pip python3-venv \
    libapache2-mod-wsgi-py3 openssl git certbot python3-certbot-apache

# Optional: Uncomment if using PHP setup scripts
# sudo apt-get install -y php php-mysql

# Optional: Uncomment for Easy-RSA (advanced cert management)
# sudo apt-get install -y easy-rsa

# 2. Clone or update the DevGenie repo
if [ ! -d "DevGenie" ]; then
    git clone https://github.com/andrew-kemp/DevGenie.git
else
    cd DevGenie
    git pull
    cd ..
fi
cd DevGenie

# 3. Create Python virtualenv and install Python dependencies
echo "[*] Setting up Python virtual environment..."
python3 -m venv venv
source venv/bin/activate
pip install --upgrade pip
pip install -r requirements.txt

# 4. Generate DB password or prompt user
read -p "[?] Enter MySQL DB password for devgenie (leave blank to auto-generate): " DBPASS
if [ -z "$DBPASS" ]; then
    DBPASS=$(openssl rand -base64 16)
    echo "[*] Generated DB password: $DBPASS"
fi

# 5. Setup MySQL
echo "[*] Creating MySQL database and user..."
sudo mysql -u root <<MYSQL_SCRIPT
CREATE DATABASE IF NOT EXISTS devgenie;
CREATE USER IF NOT EXISTS 'devgenie'@'localhost' IDENTIFIED BY '${DBPASS}';
GRANT ALL PRIVILEGES ON devgenie.* TO 'devgenie'@'localhost';
FLUSH PRIVILEGES;
MYSQL_SCRIPT

# 6. Import schema
echo "[*] Importing database schema..."
mysql -u devgenie -p"${DBPASS}" devgenie < scripts/schema.sql

# 7. SSL setup
read -p "[?] Enter domain name for SSL (leave blank to skip): " DOMAIN
if [ ! -z "$DOMAIN" ]; then
    echo "[*] Setting up SSL with Let's Encrypt for $DOMAIN"
    sudo certbot --apache -d "$DOMAIN"
fi

# 8. Create .env file
cat > .env <<EOF
FLASK_ENV=production
SECRET_KEY=$(openssl rand -hex 16)
DATABASE_URL=mysql://devgenie:${DBPASS}@localhost/devgenie
EOF
chmod 600 .env
echo "[*] .env created with secure permissions."

# 9. Instructions for next steps
echo
echo "==== DevGenie stack installed ===="
echo "Next steps:"
echo "1. Activate your virtual environment:"
echo "   source venv/bin/activate"
echo "2. (Optional) Adjust Apache config for WSGI if deploying in production."
echo "3. Run the Flask app:"
echo "   flask run"
echo "4. Open http://localhost:5000/setup in your browser to create the initial admin account."
echo
echo "If you set up SSL, access via https://$DOMAIN/"
echo
echo "==== Setup complete! ===="
