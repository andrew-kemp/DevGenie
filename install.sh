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

# 2. Prompt for SMTP, Azure, From Name/Address
read -p "Enter SMTP host: " SMTP_HOST
read -p "Enter SMTP port: " SMTP_PORT
read -p "Enter SMTP username: " SMTP_USER
read -sp "Enter SMTP password: " SMTP_PASS
echo
read -p "Enter Email From Address: " EMAIL_FROM
read -p "Enter Email From Name: " EMAIL_FROM_NAME

# 3. Azure setup
echo "Do you want to create an Azure Service Principal and Key Vault automatically? (y/N): "
read CREATE_AZURE

if [[ "$CREATE_AZURE" =~ ^[Yy]$ ]]; then
    echo "Creating Azure Service Principal..."
    # SP Name must be unique in tenant
    SP_NAME="DevGenieSP-$(date +%s)"
    AZURE_SP_JSON=$(az ad sp create-for-rbac --name "$SP_NAME" --skip-assignment --sdk-auth)
    CLIENT_ID=$(echo $AZURE_SP_JSON | jq -r .clientId)
    TENANT_ID=$(echo $AZURE_SP_JSON | jq -r .tenantId)
    echo "Created Service Principal: $CLIENT_ID"

    echo "Creating Azure Key Vault..."
    read -p "Enter Azure Resource Group for Key Vault: " AZ_RG
    read -p "Enter Azure location for Key Vault (e.g., uksouth): " AZ_LOC
    KV_NAME="devgeniekv$(date +%s)"
    az keyvault create --name $KV_NAME --resource-group $AZ_RG --location $AZ_LOC
    KV_URI="https://$KV_NAME.vault.azure.net/"
    echo "Assigning access to SP on Key Vault..."
    az keyvault set-policy --name $KV_NAME --spn $CLIENT_ID --secret-permissions get list set delete --key-permissions get list --certificate-permissions get list
    echo "Azure Key Vault URI: $KV_URI"
else
    read -p "Enter Azure Service Principal (App) Client ID: " CLIENT_ID
    read -p "Enter Azure Tenant ID: " TENANT_ID
    read -p "Enter Azure Key Vault URI: " KV_URI
fi

# 4. Check for/removal of existing resources
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

# 5. Clone repo, install deps, venv etc (as before)
if [ ! -d "$REPO_DIR/.git" ]; then
    git clone "$REPO_URL" "$REPO_DIR"
else
    cd "$REPO_DIR"
    git pull
    cd -
fi

sudo apt update
sudo apt upgrade -y
sudo apt install -y apache2 mysql-server php php-mysql libapache2-mod-php python3 python3-venv python3-certbot-apache certbot git unzip curl openssl jq

VENV_DIR="$WEBROOT/venv"
python3 -m venv "$VENV_DIR"
"$VENV_DIR/bin/pip" install --upgrade pip
"$VENV_DIR/bin/pip" install -r "$REPO_DIR/requirements.txt"

sudo mkdir -p "$WEBROOT"
sudo rsync -a "$REPO_DIR/public_html/" "$WEBROOT/public_html/"
sudo rsync -a "$REPO_DIR/public_html/assets/" "$WEBROOT/public_html/assets/"
sudo rsync -a "$REPO_DIR/config/" "$WEBROOT/config/"
sudo rsync -a "$REPO_DIR/db/" "$WEBROOT/db/"
sudo chown -R www-data:www-data "$WEBROOT"

sudo mysql -e "CREATE DATABASE IF NOT EXISTS $DBNAME;"
sudo mysql -e "CREATE USER IF NOT EXISTS '$DBUSER'@'localhost' IDENTIFIED BY '$DBPASS';"
sudo mysql -e "GRANT ALL PRIVILEGES ON $DBNAME.* TO '$DBUSER'@'localhost';"
sudo mysql -e "FLUSH PRIVILEGES;"
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

# Insert initial settings into DB (except SMTP_PASS, which is stored as secret in Key Vault or after web wizard)
sudo mysql "$DBNAME" -e "
INSERT INTO settings (setting_key, setting_value) VALUES
('smtp_host', '$SMTP_HOST'),
('smtp_port', '$SMTP_PORT'),
('smtp_user', '$SMTP_USER'),
('smtp_from', '$EMAIL_FROM'),
('smtp_from_name', '$EMAIL_FROM_NAME'),
('kv_uri', '$KV_URI'),
('sp_client_id', '$CLIENT_ID'),
('tenant_id', '$TENANT_ID'),
('cert_path', '$CERT_PATH'),
('key_path', '$KEY_PATH')
ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value);
"

# Optionally store SMTP_PASS in Azure Key Vault if created
if [[ "$CREATE_AZURE" =~ ^[Yy]$ ]]; then
    az keyvault secret set --vault-name $KV_NAME --name "smtp-password" --value "$SMTP_PASS"
    echo "SMTP password stored in Azure Key Vault as secret 'smtp-password'."
fi

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
echo "Configure SMTP2Go, Azure, and KeyVault in the web setup wizard if needed."