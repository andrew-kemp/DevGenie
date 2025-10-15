#!/bin/bash

set -e
echo "==== DevGenie Automated Installer ===="

REPO_URL="https://github.com/andrew-kemp/DevGenie.git"
REPO_DIR="/tmp/DevGenie"

# 1. Prompt for all details
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

# 2. Check if the web folder, DB, or user exists, and prompt for removal
FOLDER_EXISTS=0
DB_EXISTS=0
USER_EXISTS=0

if [ -d "$WEBROOT" ]; then FOLDER_EXISTS=1; fi
DB_EXISTS=$(sudo mysql -N -e "SHOW DATABASES LIKE '$DBNAME';" | grep -c "$DBNAME" || true)
USER_EXISTS=$(sudo mysql -N -e "SELECT EXISTS(SELECT 1 FROM mysql.user WHERE user = '$DBUSER');" | tail -n1)

if [ "$FOLDER_EXISTS" -eq 1 ] || [ "$DB_EXISTS" -eq 1 ] || [ "$USER_EXISTS" -eq 1 ]; then
    echo "WARNING: One or more of the following exist and will be removed if you continue:"
    [ "$FOLDER_EXISTS" -eq 1 ] && echo " - Web folder: $WEBROOT"
    [ "$DB_EXISTS" -eq 1 ] && echo " - MySQL database: $DBNAME"
    [ "$USER_EXISTS" -eq 1 ] && echo " - MySQL user: $DBUSER"
    read -p "Do you want to REMOVE ALL of the above and start fresh? (y/N): " REMOVE_ALL
    if [[ "$REMOVE_ALL" =~ ^[Yy]$ ]]; then
        [ "$FOLDER_EXISTS" -eq 1 ] && sudo rm -rf "$WEBROOT" && echo "Removed $WEBROOT."
        [ "$DB_EXISTS" -eq 1 ] && sudo mysql -e "DROP DATABASE $DBNAME;" && echo "Dropped database $DBNAME."
        [ "$USER_EXISTS" -eq 1 ] && sudo mysql -e "DROP USER '$DBUSER'@'localhost';" && echo "Dropped user $DBUSER."
    else
        echo "Aborting installation. Nothing was changed."
        exit 1
    fi
fi

# 3. Clone or update repo (always get latest)
if [ ! -d "$REPO_DIR/.git" ]; then
    git clone "$REPO_URL" "$REPO_DIR"
else
    cd "$REPO_DIR"
    git pull
    cd -
fi

# 4. Install system dependencies (no postfix, no Azure CLI)
sudo apt update
sudo apt upgrade -y
sudo apt install -y apache2 mysql-server php php-mysql libapache2-mod-php python3 python3-venv python3-certbot-apache certbot git unzip curl

# 5. Python venv for automation tools
VENV_DIR="$WEBROOT/venv"
python3 -m venv "$VENV_DIR"
"$VENV_DIR/bin/pip" install --upgrade pip
"$VENV_DIR/bin/pip" install -r "$REPO_DIR/requirements.txt"

# 6. Copy site files (force all assets, not just public_html)
sudo mkdir -p "$WEBROOT"
sudo rsync -a "$REPO_DIR/public_html/" "$WEBROOT/public_html/"
sudo rsync -a "$REPO_DIR/public_html/assets/" "$WEBROOT/public_html/assets/"
sudo rsync -a "$REPO_DIR/config/" "$WEBROOT/config/"
sudo rsync -a "$REPO_DIR/db/" "$WEBROOT/db/"
sudo chown -R www-data:www-data "$WEBROOT"

# 7. MySQL DB/User setup (fresh)
sudo mysql -e "CREATE DATABASE IF NOT EXISTS $DBNAME;"
sudo mysql -e "CREATE USER IF NOT EXISTS '$DBUSER'@'localhost' IDENTIFIED BY '$DBPASS';"
sudo mysql -e "GRANT ALL PRIVILEGES ON $DBNAME.* TO '$DBUSER'@'localhost';"
sudo mysql -e "FLUSH PRIVILEGES;"
sudo mysql "$DBNAME" < "$WEBROOT/db/schema.sql"

# 8. Apache VirtualHost
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

# 9. LetsEncrypt SSL
sudo certbot --apache -d $DOMAIN --non-interactive --agree-tos -m admin@$DOMAIN

# 10. Output credentials for config.php and next steps
echo "==== INSTALL COMPLETE ===="
echo "Site files: $WEBROOT"
echo "Go to https://$DOMAIN/setup.php to continue setup."
echo "Database credentials (add to config/config.php):"
echo "  DB Name: $DBNAME"
echo "  DB User: $DBUSER"
echo "  DB Pass: $DBPASS"
echo ""
echo "Python venv for automation: $VENV_DIR"
echo "You do NOT need Postfix or local mail!"
echo "Configure SMTP2Go, Azure, and KeyVault in the web setup wizard."