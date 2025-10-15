#!/bin/bash

echo "==== DevGenie Automated Installer ===="
set -e

REPO_URL="https://github.com/andrew-kemp/DevGenie.git"
REPO_DIR="/tmp/DevGenie"

# 0. Clone or update the repo
if [ ! -d ".git" ]; then
    if [ ! -d "$REPO_DIR/.git" ]; then
        echo "Cloning DevGenie repository from GitHub..."
        git clone "$REPO_URL" "$REPO_DIR"
    else
        echo "Updating DevGenie repository..."
        cd "$REPO_DIR"
        git pull
        cd -
    fi
    cd "$REPO_DIR"
else
    echo "Already in a git repository, updating..."
    git pull
fi

# 1. Prerequisite check and install
echo "Updating system and installing prerequisites..."
sudo apt update
sudo apt upgrade -y
sudo apt install -y apache2 mysql-server php php-mysql libapache2-mod-php python3 python3-pip postfix certbot python3-certbot-apache unzip curl git

# 2. Install Azure CLI if not present
if ! command -v az &>/dev/null; then
    echo "Azure CLI not found, installing..."
    curl -sL https://aka.ms/InstallAzureCLIDeb | sudo bash
fi

# 3. Install required Python modules
pip3 install -r requirements.txt

# 4. Prompt for domain and web root
read -p "Enter the domain name (e.g., devgenie.domain.com): " DOMAIN
read -p "Enter the web folder location (e.g., /var/www/devgenie.domain.com): " WEBROOT

# 5. Generate certificate for Azure Key Vault authentication
CERT_DIR="/etc/devgenie"
sudo mkdir -p "$CERT_DIR"
if [ ! -f "$CERT_DIR/keyvault.key" ]; then
    echo "Generating certificate for Azure Key Vault authentication..."
    sudo openssl req -x509 -newkey rsa:4096 -keyout "$CERT_DIR/keyvault.key" -out "$CERT_DIR/keyvault.crt" -days 3650 -nodes -subj "/CN=DevGenieKeyVault"
    sudo chmod 600 "$CERT_DIR/keyvault.key"
    sudo chmod 644 "$CERT_DIR/keyvault.crt"
else
    echo "Certificate already exists, skipping generation."
fi

# 6. Azure login
echo "Logging in to Azure. Please complete authentication in your browser window if prompted."
az login

# 7. List Azure subscriptions and select one
echo "Available Azure subscriptions:"
az account list --output table
read -p "Enter the subscription ID to use: " AZ_SUBSCRIPTION_ID
az account set --subscription "$AZ_SUBSCRIPTION_ID"

# 8. Prompt for Resource Group
echo "Listing resource groups..."
az group list --output table
read -p "Enter the resource group to use (will be created if not exists): " AZ_RG
if ! az group show --name "$AZ_RG" &>/dev/null; then
    read -p "Resource group does not exist. Enter Azure region (e.g., westeurope): " AZ_REGION
    az group create --name "$AZ_RG" --location "$AZ_REGION"
fi

# 9. Key Vault selection/creation
read -p "Do you want to use an existing Key Vault? (y/n): " USE_EXISTING
if [[ "$USE_EXISTING" =~ ^[Yy]$ ]]; then
    az keyvault list --resource-group "$AZ_RG" --output table
    read -p "Enter the Key Vault name: " KEYVAULT_NAME
else
    read -p "Enter a name for the new Key Vault: " KEYVAULT_NAME
    read -p "Enter Azure region for Key Vault (e.g., westeurope): " AZ_KV_REGION
    az keyvault create --name "$KEYVAULT_NAME" --resource-group "$AZ_RG" --location "$AZ_KV_REGION"
fi
KEYVAULT_URI="https://$KEYVAULT_NAME.vault.azure.net/"

# 10. App Registration (Service Principal) creation
APP_NAME="DevGenie_${DOMAIN//./_}"
echo "Creating App Registration: $APP_NAME"
APP_OBJECT_ID=$(az ad app create --display-name "$APP_NAME" --query id -o tsv)
APP_ID=$(az ad app show --id "$APP_OBJECT_ID" --query appId -o tsv)

# 11. Upload certificate to App Registration
echo "Uploading certificate to App Registration..."
az ad app credential reset --id "$APP_ID" --cert "$CERT_DIR/keyvault.crt" --append

# 12. Create Service Principal identity
az ad sp create --id "$APP_ID"

# 13. Assign Key Vault access policy to Service Principal
az keyvault set-policy --name "$KEYVAULT_NAME" --spn "$APP_ID" --secret-permissions get list

# 14. Create MySQL DB, user, and import schema
DBNAME="devgenie"
DBUSER="devgenieadmin"
DBPASS=$(openssl rand -base64 16)
sudo mysql -e "CREATE DATABASE IF NOT EXISTS $DBNAME;"
sudo mysql -e "CREATE USER IF NOT EXISTS '$DBUSER'@'localhost' IDENTIFIED BY '$DBPASS';"
sudo mysql -e "GRANT ALL PRIVILEGES ON $DBNAME.* TO '$DBUSER'@'localhost';"
sudo mysql -e "FLUSH PRIVILEGES;"
sudo mysql "$DBNAME" < db/schema.sql

# 15. Setup web root and Apache
sudo mkdir -p "$WEBROOT"
sudo chown -R www-data:www-data "$WEBROOT"
sudo cp -r public_html "$WEBROOT/"
sudo cp -r config "$WEBROOT/"
sudo cp -r db "$WEBROOT/"

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

# 16. Output for setup.php and config.php
TENANT_ID=$(az account show --query tenantId -o tsv)
echo "==== Azure/Entra & Key Vault Setup Complete ===="
echo "Key Vault URI: $KEYVAULT_URI"
echo "App (client) ID: $APP_ID"
echo "Tenant ID: $TENANT_ID"
echo "Cert public: $CERT_DIR/keyvault.crt"
echo "Cert private: $CERT_DIR/keyvault.key"
echo "DB user: $DBUSER"
echo "DB pass: $DBPASS"
echo "DB name: $DBNAME"
echo ""
echo "Copy these values into your /config/config.php and complete the portal setup at https://$DOMAIN/setup.php"

echo ""
echo "==== Installation complete! ===="