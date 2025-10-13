#!/bin/bash
set -e

echo "==== DevGenie Installer ===="

# 1. Clone repo
if [ ! -d "DevGenie" ]; then
  git clone https://github.com/andrew-kemp/DevGenie.git
fi
cd DevGenie

# 2. Generate DB password or prompt
read -p "Enter MySQL DB password for devgenie (leave blank to auto-generate): " DBPASS
if [ -z "$DBPASS" ]; then
  DBPASS=$(openssl rand -base64 16)
  echo "Generated DB password: $DBPASS"
fi

# 3. Setup MySQL
echo "Creating MySQL DB and user..."
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS devgenie;
CREATE USER IF NOT EXISTS 'devgenie'@'localhost' IDENTIFIED BY '$DBPASS';
GRANT ALL PRIVILEGES ON devgenie.* TO 'devgenie'@'localhost';
FLUSH PRIVILEGES;"

# 4. Import schema
mysql -u devgenie -p"$DBPASS" devgenie < scripts/schema.sql

# 5. SSL setup
read -p "Enter domain name for SSL (or leave blank to skip): " DOMAIN
if [ ! -z "$DOMAIN" ]; then
  sudo apt-get update
  sudo apt-get install -y certbot python3-certbot-apache
  sudo certbot --apache -d "$DOMAIN"
fi

# 6. Create .env file
cat > .env <<EOF
FLASK_ENV=production
SECRET_KEY=$(openssl rand -hex 16)
DATABASE_URL=mysql://devgenie:$DBPASS@localhost/devgenie
EOF

echo ".env created."

echo "==== DevGenie base setup complete ===="
echo "Now run: source venv/bin/activate && pip install -r requirements.txt && flask run"