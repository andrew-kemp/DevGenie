#!/bin/bash

set -e
echo "==== DevGenie Automated Installer ===="

REPO_URL="https://github.com/andrew-kemp/DevGenie.git"
REPO_DIR="/tmp/DevGenie"
CERT_DIR="/etc/devgenie"
CERT_PATH="$CERT_DIR/keyvault.crt"
KEY_PATH="$CERT_DIR/keyvault.key"

# 1. Prompt for domain, DB, web root
read -p "Enter the full domain name for this instance (e.g., devgenie.andykemp.cloud): " DOMAIN
DEFAULT_DBNAME="db_${DOMAIN//./_}"
DEFAULT_DBUSER="user_${DOMAIN//./_}"
DEFAULT_WEBROOT="/var/www/$DOMAIN"
read -p "Enter MySQL database name [$DEFAULT_DBNAME]: " DBNAME
DBNAME=${DBNAME:-$DEFAULT_DBNAME}
read -p "Enter MySQL username [$DEFAULT_DBUSER]: " DBUSER
DBUSER=${DBUSER:-$DEFAULT_DBUSER}
DBPASS=$(openssl rand -base64 20)
read -p "Enter the web folder location [$DEFAULT_WEBROOT]: " WEBROOT
WEBROOT=${WEBROOT:-$DEFAULT_WEBROOT}

# 2. Update or reinstall logic
if [ -d "$WEBROOT" ]; then
    echo "It looks like $WEBROOT already exists."
    echo "Do you want to [U]pdate (keep DB/files), [R]einstall (WIPE ALL), or [C]ancel?"
    read -p "[U]pdate/[R]einstall/[C]ancel: " UPD_CHOICE
    case "$UPD_CHOICE" in
        [Uu]* )
            echo "Will update the code and dependencies only..."
            ;;
        [Rr]* )
            echo "WARNING: This will REMOVE EVERYTHING for $WEBROOT and DB $DBNAME."
            read -p "Are you absolutely sure? (type YES): " SURE
            if [ "$SURE" = "YES" ]; then
                sudo rm -rf "$WEBROOT"
                sudo mysql -e "DROP DATABASE IF EXISTS $DBNAME;"
                sudo mysql -e "DROP USER IF EXISTS '$DBUSER'@'localhost';"
                echo "All site files and database have been removed."
            else
                echo "Cancelled."
                exit 1
            fi
            ;;
        * )
            echo "Cancelled."
            exit 1
            ;;
    esac
fi

# 3. Clone repo, install deps, venv, etc.
if [ ! -d "$REPO_DIR/.git" ]; then
    git clone "$REPO_URL" "$REPO_DIR"
else
    cd "$REPO_DIR"
    git pull
    cd -
fi

sudo apt update
sudo apt upgrade -y
sudo apt install -y apache2 mysql-server php php-mysql libapache2-mod-php python3 python3-venv python3-certbot-apache certbot git unzip curl openssl jq php-curl php-xml php-mbstring

# Install Composer if not already present or if outdated
if ! command -v composer &> /dev/null; then
    echo "Installing Composer..."
    EXPECTED_SIGNATURE="$(wget -q -O - https://composer.github.io/installer.sig)"
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    ACTUAL_SIGNATURE="$(php -r "echo hash_file('sha384', 'composer-setup.php');")"
    if [ "$EXPECTED_SIGNATURE" != "$ACTUAL_SIGNATURE" ]; then
        >&2 echo 'ERROR: Invalid installer signature'
        rm composer-setup.php
        exit 1
    fi
    sudo php composer-setup.php --install-dir=/usr/local/bin --filename=composer
    rm composer-setup.php
else
    echo "Composer already installed. Checking for updates..."
    sudo composer self-update
fi

# Ensure correct permissions for composer cache (especially if run as root)
sudo mkdir -p /var/www/.composer
sudo chown -R www-data:www-data /var/www/.composer

VENV_DIR="$WEBROOT/venv"
python3 -m venv "$VENV_DIR"
"$VENV_DIR/bin/pip" install --upgrade pip
if [ -f "$REPO_DIR/requirements.txt" ]; then
    "$VENV_DIR/bin/pip" install -r "$REPO_DIR/requirements.txt"
fi

sudo mkdir -p "$WEBROOT"
sudo rsync -a "$REPO_DIR/public_html/" "$WEBROOT/public_html/"
sudo rsync -a "$REPO_DIR/public_html/assets/" "$WEBROOT/public_html/assets/"
sudo rsync -a "$REPO_DIR/config/" "$WEBROOT/config/"
sudo rsync -a "$REPO_DIR/db/" "$WEBROOT/db/"
sudo chown -R www-data:www-data "$WEBROOT"

# Install PHP SAML library (onelogin/php-saml)
cd "$WEBROOT"
if [ ! -f composer.json ]; then
    cat <<EOC > composer.json
{
  "require": {
    "onelogin/php-saml": "^4.0"
  }
}
EOC
fi

sudo -u www-data composer install --no-interaction || sudo -u www-data composer update --no-interaction

sudo mysql -e "CREATE DATABASE IF NOT EXISTS $DBNAME;"
sudo mysql -e "CREATE USER IF NOT EXISTS '$DBUSER'@'localhost' IDENTIFIED BY '$DBPASS';"
sudo mysql -e "GRANT ALL PRIVILEGES ON $DBNAME.* TO '$DBUSER'@'localhost';"
sudo mysql -e "FLUSH PRIVILEGES;"

# 4. Create all tables using db/schema.sql (merged schema)
sudo mysql "$DBNAME" < "$WEBROOT/db/schema.sql"

sudo mkdir -p "$CERT_DIR"
if [ ! -f "$KEY_PATH" ] || [ ! -f "$CERT_PATH" ]; then
    sudo openssl req -x509 -newkey rsa:4096 -keyout "$KEY_PATH" -out "$CERT_PATH" -days 3650 -nodes -subj "/CN=DevGenieKeyVault"
    sudo chmod 600 "$KEY_PATH"
    sudo chmod 644 "$CERT_PATH"
    echo "Generated SP/Key Vault certificate and key."
else
    echo "Certificate and key already exist at $CERT_PATH and $KEY_PATH"
fi

cat <<EOF | sudo tee "$WEBROOT/config/config.php" > /dev/null
<?php
define('DB_HOST', 'localhost');
define('DB_USER', '$DBUSER');
define('DB_PASS', '$DBPASS');
define('DB_NAME', '$DBNAME');
define('CERT_PATH', '$CERT_PATH');
define('KEY_PATH', '$KEY_PATH');
?>
EOF

# Store only the certificate and key path settings for now
sudo mysql "$DBNAME" -e "
INSERT INTO settings (setting_key, setting_value) VALUES
('cert_path', '$CERT_PATH'),
('key_path', '$KEY_PATH')
ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value);
"

VHOST_CONF="/etc/apache2/sites-available/$DOMAIN.conf"
sudo bash -c "cat <<EOF > $VHOST_CONF
<VirtualHost *:80>
    ServerName $DOMAIN
    DocumentRoot $WEBROOT/public_html
    <Directory $WEBROOT/public_html>
        AllowOverride All
        Require all granted
    </Directory>
    ErrorLog \${APACHE_LOG_DIR}/$DOMAIN-error.log
    CustomLog \${APACHE_LOG_DIR}/$DOMAIN-access.log combined
</VirtualHost>
EOF"
sudo a2ensite "$DOMAIN"
sudo a2enmod rewrite
sudo systemctl reload apache2

sudo certbot --apache -d $DOMAIN --non-interactive --agree-tos -m admin@$DOMAIN

echo "==== INSTALL COMPLETE ===="
echo "Site files: $WEBROOT"
echo "Go to https://$DOMAIN/setup.php to continue setup."
echo "Database credentials and cert paths have been added to $WEBROOT/config/config.php."
echo "Python venv for automation: $VENV_DIR"
echo "All Entra, Key Vault, SSO (SAML), and SMTP configuration will be handled in the web portal."