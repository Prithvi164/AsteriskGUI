# Quick Start Guide

Get your Asterisk PBX Management GUI up and running in minutes!

## 🚀 Quick Installation (Ubuntu/Debian)

### One-Line Install (Coming Soon)

```bash
curl -s https://raw.githubusercontent.com/yourusername/asterisk-pbx-gui/main/install.sh | bash
```

### Manual Installation (10 minutes)

#### 1. Install Prerequisites

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install required packages
sudo apt install -y php8.1 php8.1-fpm php8.1-mysql php8.1-xml \
    php8.1-mbstring php8.1-curl php8.1-zip php8.1-redis \
    mysql-server redis-server nginx nodejs npm git composer

# Install Asterisk (if not already installed)
sudo apt install asterisk -y
```

#### 2. Clone and Setup Application

```bash
# Clone repository
cd /var/www
sudo git clone https://github.com/yourusername/asterisk-pbx-gui.git
cd asterisk-pbx-gui

# Install dependencies
sudo composer install
sudo npm install

# Set permissions
sudo chown -R www-data:www-data /var/www/asterisk-pbx-gui
sudo chmod -R 755 /var/www/asterisk-pbx-gui
sudo chmod -R 775 storage bootstrap/cache
```

#### 3. Configure Environment

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Edit .env file
nano .env
```

**Minimum required settings in `.env`:**

```env
DB_DATABASE=asterisk_gui
DB_USERNAME=root
DB_PASSWORD=your_mysql_password

ASTERISK_AMI_HOST=127.0.0.1
ASTERISK_AMI_USERNAME=phpgui
ASTERISK_AMI_PASSWORD=your_ami_password
```

#### 4. Setup Database

```bash
# Create database
mysql -u root -p -e "CREATE DATABASE asterisk_gui;"

# Run migrations
php artisan migrate

# Seed initial data
php artisan db:seed
```

#### 5. Configure Asterisk AMI

```bash
# Edit manager configuration
sudo nano /etc/asterisk/manager.conf
```

Add this configuration:

```ini
[general]
enabled = yes
port = 5038
bindaddr = 127.0.0.1

[phpgui]
secret = your_ami_password
deny=0.0.0.0/0.0.0.0
permit=127.0.0.1/255.255.255.0
read = all
write = all
```

Reload Asterisk:

```bash
sudo asterisk -rx "manager reload"
```

#### 6. Build Frontend

```bash
npm run build
```

#### 7. Start Services

```bash
# Start queue worker
php artisan queue:work &

# Start AMI listener
php artisan ami:listen &
```

#### 8. Configure Nginx

```bash
# Copy Nginx configuration
sudo cp deployment/nginx.conf /etc/nginx/sites-available/asterisk-gui
sudo ln -s /etc/nginx/sites-available/asterisk-gui /etc/nginx/sites-enabled/

# Update paths in the config
sudo nano /etc/nginx/sites-available/asterisk-gui

# Test and reload
sudo nginx -t
sudo systemctl reload nginx
```

#### 9. Access the Application

Open your browser and go to:

```
http://your-server-ip
```

**Default Login:**
- Email: `admin@asterisk-gui.local`
- Password: `password`

🎉 **You're all set!** Change your password immediately.

---

## 🐳 Docker Installation (Easiest)

### Prerequisites

- Docker
- Docker Compose

### Installation

```bash
# Clone repository
git clone https://github.com/yourusername/asterisk-pbx-gui.git
cd asterisk-pbx-gui

# Copy environment file
cp .env.example .env

# Edit .env if needed
nano .env

# Start containers
docker-compose up -d

# Run migrations
docker-compose exec app php artisan migrate --seed

# Build frontend
docker-compose exec app npm run build
```

Access at: `http://localhost:8080`

---

## 📋 Post-Installation Checklist

- [ ] Change default admin password
- [ ] Configure email settings (for notifications)
- [ ] Add your first extension
- [ ] Test click-to-call functionality
- [ ] Configure backup schedule
- [ ] Enable SSL certificate
- [ ] Review security settings
- [ ] Set up monitoring/alerts

---

## 🔧 Common Commands

### Artisan Commands

```bash
# Start AMI listener
php artisan ami:listen

# Sync CDR data
php artisan cdr:sync

# Clean old recordings
php artisan recordings:cleanup

# Create new user
php artisan make:user

# Clear cache
php artisan cache:clear
```

### Supervisor Management

```bash
# Check status
sudo supervisorctl status

# Restart all
sudo supervisorctl restart all

# View logs
sudo supervisorctl tail asterisk-ami-listener
```

### Database

```bash
# Run migrations
php artisan migrate

# Rollback last migration
php artisan migrate:rollback

# Fresh install (WARNING: deletes all data)
php artisan migrate:fresh --seed
```

---

## 🆘 Quick Troubleshooting

### Can't Connect to AMI

```bash
# Check Asterisk is running
sudo asterisk -rx "core show version"

# Check AMI port is listening
netstat -tulpn | grep 5038

# Test AMI connection
telnet localhost 5038
```

### Permission Errors

```bash
# Fix storage permissions
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### Redis Connection Failed

```bash
# Check Redis is running
redis-cli ping

# Should return: PONG

# Start Redis if not running
sudo systemctl start redis
```

### White Screen / 500 Error

```bash
# Check logs
tail -f storage/logs/laravel.log

# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

---

## 🎯 Next Steps

1. **Add Extensions**: Go to Extensions > Add Extension
2. **Configure Queues**: Set up your first call queue
3. **Test Calls**: Make a test call using Click-to-Call
4. **View Reports**: Check Dashboard for analytics
5. **Customize**: Adjust settings to your needs

---

## 📚 Additional Resources

- [Full Documentation](README.md)
- [API Documentation](API.md)
- [Video Tutorials](https://youtube.com/@asterisk-gui)
- [Community Forum](https://forum.asterisk-gui.local)

---

## 💡 Tips for Best Experience

1. **Use Chrome/Firefox** - Best browser compatibility
2. **Enable Notifications** - Get real-time alerts
3. **Regular Backups** - Database and recordings
4. **Monitor Logs** - Watch for errors
5. **Update Regularly** - Get latest features and fixes

---

**Need Help?** Open an issue on [GitHub](https://github.com/yourusername/asterisk-pbx-gui/issues)

