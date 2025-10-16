#!/bin/bash

###############################################################################
# Asterisk PBX Management GUI - Automated Installation Script
# 
# This script automates the installation process on Ubuntu/Debian systems
# 
# Usage: sudo bash install.sh
###############################################################################

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Print colored message
print_message() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

# Check if running as root
if [[ $EUID -ne 0 ]]; then
   print_error "This script must be run as root (use sudo)"
   exit 1
fi

print_message "Starting Asterisk PBX Management GUI Installation..."
echo ""

# Get installation directory
read -p "Enter installation directory [/var/www/asterisk-gui]: " INSTALL_DIR
INSTALL_DIR=${INSTALL_DIR:-/var/www/asterisk-gui}

# Get domain name
read -p "Enter domain name [asterisk-gui.local]: " DOMAIN_NAME
DOMAIN_NAME=${DOMAIN_NAME:-asterisk-gui.local}

# Database configuration
print_message "Database Configuration"
read -p "Enter MySQL database name [asterisk_gui]: " DB_NAME
DB_NAME=${DB_NAME:-asterisk_gui}

read -p "Enter MySQL username [asterisk_user]: " DB_USER
DB_USER=${DB_USER:-asterisk_user}

read -sp "Enter MySQL password: " DB_PASS
echo ""

# AMI Configuration
print_message "Asterisk AMI Configuration"
read -p "Enter AMI username [phpgui]: " AMI_USER
AMI_USER=${AMI_USER:-phpgui}

read -sp "Enter AMI password: " AMI_PASS
echo ""

print_message "Updating system packages..."
apt update && apt upgrade -y

print_message "Installing required packages..."
apt install -y \
    software-properties-common \
    apt-transport-https \
    ca-certificates \
    curl \
    wget \
    git \
    unzip

# Install PHP 8.1
print_message "Installing PHP 8.1 and extensions..."
add-apt-repository -y ppa:ondrej/php
apt update
apt install -y \
    php8.1 \
    php8.1-fpm \
    php8.1-mysql \
    php8.1-xml \
    php8.1-mbstring \
    php8.1-curl \
    php8.1-zip \
    php8.1-redis \
    php8.1-gd \
    php8.1-bcmath

# Install MySQL
print_message "Installing MySQL..."
apt install -y mysql-server

# Secure MySQL installation
mysql -e "CREATE DATABASE IF NOT EXISTS ${DB_NAME};"
mysql -e "CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';"
mysql -e "GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'localhost';"
mysql -e "FLUSH PRIVILEGES;"

print_success "MySQL database created and configured"

# Install Redis
print_message "Installing Redis..."
apt install -y redis-server
systemctl enable redis-server
systemctl start redis-server

# Install Nginx
print_message "Installing Nginx..."
apt install -y nginx

# Install Node.js and NPM
print_message "Installing Node.js 18.x..."
curl -fsSL https://deb.nodesource.com/setup_18.x | bash -
apt install -y nodejs

# Install Composer
print_message "Installing Composer..."
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install Supervisor
print_message "Installing Supervisor..."
apt install -y supervisor
systemctl enable supervisor

# Clone repository or use existing directory
if [ ! -d "$INSTALL_DIR" ]; then
    print_message "Creating installation directory..."
    mkdir -p $INSTALL_DIR
    
    print_message "Cloning repository..."
    read -p "Enter Git repository URL (or press Enter to skip): " GIT_REPO
    
    if [ ! -z "$GIT_REPO" ]; then
        git clone $GIT_REPO $INSTALL_DIR
    else
        print_warning "Skipping git clone. Please manually copy files to $INSTALL_DIR"
    fi
else
    print_warning "Installation directory already exists. Using existing files."
fi

cd $INSTALL_DIR

# Install PHP dependencies
print_message "Installing PHP dependencies..."
composer install --optimize-autoloader --no-dev

# Setup environment file
print_message "Configuring environment..."
if [ ! -f .env ]; then
    cp .env.example .env
fi

# Update .env file
sed -i "s/DB_DATABASE=.*/DB_DATABASE=${DB_NAME}/" .env
sed -i "s/DB_USERNAME=.*/DB_USERNAME=${DB_USER}/" .env
sed -i "s/DB_PASSWORD=.*/DB_PASSWORD=${DB_PASS}/" .env
sed -i "s/ASTERISK_AMI_USERNAME=.*/ASTERISK_AMI_USERNAME=${AMI_USER}/" .env
sed -i "s/ASTERISK_AMI_PASSWORD=.*/ASTERISK_AMI_PASSWORD=${AMI_PASS}/" .env

# Generate application key
print_message "Generating application key..."
php artisan key:generate --force

# Install NPM dependencies
print_message "Installing NPM dependencies..."
npm install

# Build frontend assets
print_message "Building frontend assets..."
npm run build

# Set permissions
print_message "Setting permissions..."
chown -R www-data:www-data $INSTALL_DIR
chmod -R 755 $INSTALL_DIR
chmod -R 775 $INSTALL_DIR/storage
chmod -R 775 $INSTALL_DIR/bootstrap/cache

# Run migrations
print_message "Running database migrations..."
php artisan migrate --force

# Seed database
print_message "Seeding database..."
php artisan db:seed --force

# Configure Nginx
print_message "Configuring Nginx..."
cat > /etc/nginx/sites-available/asterisk-gui << EOF
server {
    listen 80;
    server_name ${DOMAIN_NAME};
    root ${INSTALL_DIR}/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
EOF

# Enable site
ln -sf /etc/nginx/sites-available/asterisk-gui /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default

# Test and reload Nginx
nginx -t
systemctl reload nginx

# Configure Supervisor
print_message "Configuring Supervisor..."
cat > /etc/supervisor/conf.d/asterisk-gui.conf << EOF
[program:asterisk-gui-worker]
process_name=%(program_name)s_%(process_num)02d
command=php ${INSTALL_DIR}/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=${INSTALL_DIR}/storage/logs/worker.log

[program:asterisk-ami-listener]
process_name=%(program_name)s
command=php ${INSTALL_DIR}/artisan ami:listen
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=${INSTALL_DIR}/storage/logs/ami-listener.log
EOF

# Reload Supervisor
supervisorctl reread
supervisorctl update
supervisorctl start all

# Configure Asterisk AMI
print_message "Configuring Asterisk Manager Interface..."
if [ -f /etc/asterisk/manager.conf ]; then
    # Backup existing configuration
    cp /etc/asterisk/manager.conf /etc/asterisk/manager.conf.backup
    
    # Add GUI user to manager.conf if not exists
    if ! grep -q "\[${AMI_USER}\]" /etc/asterisk/manager.conf; then
        cat >> /etc/asterisk/manager.conf << EOF

[${AMI_USER}]
secret = ${AMI_PASS}
deny=0.0.0.0/0.0.0.0
permit=127.0.0.1/255.255.255.0
read = system,call,log,verbose,command,agent,user,config,reporting
write = system,call,log,verbose,command,agent,user,config,reporting
EOF
        
        # Reload Asterisk manager
        asterisk -rx "manager reload" 2>/dev/null || print_warning "Could not reload Asterisk. Please reload manually."
    fi
else
    print_warning "Asterisk manager.conf not found. Please configure manually."
fi

# Clear caches
print_message "Clearing caches..."
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo ""
print_success "========================================="
print_success "Installation completed successfully!"
print_success "========================================="
echo ""
print_message "Access the application at: http://${DOMAIN_NAME}"
echo ""
print_message "Default login credentials:"
print_message "  Email: admin@asterisk-gui.local"
print_message "  Password: password"
echo ""
print_warning "IMPORTANT: Change the default password immediately!"
echo ""
print_message "Useful commands:"
print_message "  Check worker status: sudo supervisorctl status"
print_message "  View logs: tail -f ${INSTALL_DIR}/storage/logs/laravel.log"
print_message "  Restart services: sudo supervisorctl restart all"
echo ""
print_message "Documentation: ${INSTALL_DIR}/README.md"
echo ""

