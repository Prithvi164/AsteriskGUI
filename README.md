# Asterisk PBX Management GUI

A modern, web-based GUI for managing and monitoring Asterisk PBX systems. Built with Laravel 10, Vue.js 3, and real-time WebSocket updates.

![License](https://img.shields.io/badge/license-MIT-blue.svg)
![PHP](https://img.shields.io/badge/PHP-8.1+-blue.svg)
![Laravel](https://img.shields.io/badge/Laravel-10.x-red.svg)
![Vue.js](https://img.shields.io/badge/Vue.js-3.x-green.svg)

## 📋 Features

### Core Features
- ✅ **Real-time Call Monitoring** - View active calls with live updates
- ✅ **Call History & CDR** - Comprehensive call detail records with search and export
- ✅ **Extension Management** - Create and manage SIP/IAX extensions
- ✅ **Queue Management** - Monitor and manage call queues and agents
- ✅ **Call Recordings** - Access, play, and download call recordings
- ✅ **Click-to-Call** - Initiate calls directly from the web interface
- ✅ **IVR Builder** - Visual IVR configuration interface
- ✅ **Dashboard Analytics** - Real-time statistics and charts
- ✅ **User Management** - Role-based access control (RBAC)
- ✅ **Trunk Management** - Configure and monitor SIP/IAX trunks

### Technical Features
- 🚀 Real-time updates via WebSocket (Laravel Echo)
- 📊 Interactive charts and statistics
- 🔒 Secure authentication with Laravel Sanctum
- 📱 Responsive design (mobile-friendly)
- 🎨 Modern UI with Bootstrap 5
- ⚡ Fast performance with Redis caching
- 📝 Comprehensive audit logging
- 🔄 Background job processing
- 📤 Export capabilities (CSV, PDF)

## 🛠️ Tech Stack

### Backend
- **Framework**: Laravel 10.x
- **PHP**: 8.1 or higher
- **Database**: MySQL 8.0+ / PostgreSQL 14+
- **Cache/Queue**: Redis
- **AMI Library**: PAMI (PHP Asterisk Manager Interface)

### Frontend
- **Framework**: Vue.js 3
- **CSS**: Bootstrap 5
- **Charts**: ApexCharts / Chart.js
- **Icons**: Bootstrap Icons
- **Build Tool**: Vite

### Infrastructure
- **Web Server**: Nginx / Apache
- **Process Manager**: Supervisor
- **WebSocket**: Laravel Reverb / Pusher
- **Asterisk**: 18.x or higher

## 📦 Installation

### Prerequisites

Before you begin, ensure you have the following installed:

- PHP 8.1 or higher
- Composer
- Node.js 18+ and NPM
- MySQL 8.0+ or PostgreSQL 14+
- Redis
- Asterisk PBX (18.x or higher)
- Git

### Step 1: Clone the Repository

```bash
git clone https://github.com/yourusername/asterisk-pbx-gui.git
cd asterisk-pbx-gui
```

### Step 2: Install PHP Dependencies

```bash
composer install
```

### Step 3: Install NPM Dependencies

```bash
npm install
```

### Step 4: Environment Configuration

```bash
# Copy the example environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

Edit the `.env` file and configure your settings:

```env
# Database Configuration
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=asterisk_gui
DB_USERNAME=root
DB_PASSWORD=your_password

# Asterisk AMI Configuration
ASTERISK_AMI_HOST=127.0.0.1
ASTERISK_AMI_PORT=5038
ASTERISK_AMI_USERNAME=phpgui
ASTERISK_AMI_PASSWORD=your_ami_password

# Asterisk CDR Database (if different from main DB)
ASTERISK_CDR_DATABASE=asteriskcdrdb
ASTERISK_CDR_USERNAME=asteriskuser
ASTERISK_CDR_PASSWORD=your_cdr_password

# Redis Configuration
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

# Broadcasting (WebSocket)
BROADCAST_DRIVER=redis
```

### Step 5: Configure Asterisk Manager Interface (AMI)

Edit Asterisk's manager configuration:

```bash
sudo nano /etc/asterisk/manager.conf
```

Add the following configuration:

```ini
[general]
enabled = yes
port = 5038
bindaddr = 127.0.0.1

[phpgui]
secret = your_ami_password
deny=0.0.0.0/0.0.0.0
permit=127.0.0.1/255.255.255.0
read = system,call,log,verbose,command,agent,user,config,reporting
write = system,call,log,verbose,command,agent,user,config,reporting
```

Reload Asterisk configuration:

```bash
sudo asterisk -rx "manager reload"
```

### Step 6: Database Setup

```bash
# Run migrations
php artisan migrate

# Seed the database with initial data (roles, admin user, etc.)
php artisan db:seed
```

**Default Admin Credentials** (after seeding):
- Email: `admin@asterisk-gui.local`
- Password: `password`

⚠️ **Change these credentials immediately after first login!**

### Step 7: Build Frontend Assets

```bash
# For development
npm run dev

# For production
npm run build
```

### Step 8: Start Queue Workers and AMI Listener

#### Option A: Using Supervisor (Recommended for Production)

Create supervisor configuration:

```bash
sudo nano /etc/supervisor/conf.d/asterisk-gui.conf
```

Add the following:

```ini
[program:asterisk-gui-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/asterisk-gui/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/asterisk-gui/storage/logs/worker.log

[program:asterisk-ami-listener]
process_name=%(program_name)s
command=php /path/to/asterisk-gui/artisan ami:listen
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/path/to/asterisk-gui/storage/logs/ami-listener.log
```

Start the processes:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start all
```

#### Option B: Manual (for Development)

Open separate terminal windows:

```bash
# Terminal 1: Queue worker
php artisan queue:work

# Terminal 2: AMI listener
php artisan ami:listen
```

### Step 9: Web Server Configuration

#### Nginx Configuration

Create a new site configuration:

```bash
sudo nano /etc/nginx/sites-available/asterisk-gui
```

Add the following:

```nginx
server {
    listen 80;
    server_name asterisk-gui.local;
    root /path/to/asterisk-gui/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Enable the site and reload Nginx:

```bash
sudo ln -s /etc/nginx/sites-available/asterisk-gui /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### Step 10: Set Permissions

```bash
sudo chown -R www-data:www-data /path/to/asterisk-gui
sudo chmod -R 755 /path/to/asterisk-gui
sudo chmod -R 775 /path/to/asterisk-gui/storage
sudo chmod -R 775 /path/to/asterisk-gui/bootstrap/cache
```

### Step 11: Access the Application

Open your browser and navigate to:

```
http://asterisk-gui.local
```

Login with the default admin credentials.

## 🔧 Configuration

### Role-Based Access Control (RBAC)

The application comes with pre-defined roles:

| Role | Permissions |
|------|-------------|
| **Admin** | Full system access |
| **Supervisor** | View all calls, manage queues, view reports |
| **Agent** | View own calls, access voicemail |
| **Viewer** | Read-only access |

To create a new user:

```bash
php artisan make:user
```

Or use the web interface: `Settings > Users > Add User`

### Customizing Permissions

Edit `config/permissions.php` to customize role permissions.

### Asterisk Configuration Files

The application can generate Asterisk configuration files. Set the path in `config/asterisk.php`:

```php
'config_path' => env('ASTERISK_CONFIG_PATH', '/etc/asterisk'),
```

## 📊 Usage

### Monitoring Active Calls

Navigate to **Dashboard** or **Active Calls** to see real-time call information:

- Caller ID and destination
- Call duration (live updating)
- Call status (Ringing, Connected, etc.)
- Actions: Hangup, Transfer, Listen

### Managing Extensions

**Extensions > Add Extension**

1. Enter extension number (e.g., 100)
2. Set extension name
3. Configure SIP settings (secret, context, etc.)
4. Enable voicemail if needed
5. Save

The application will generate the necessary Asterisk configuration.

### Queue Management

**Queues > Add Queue**

1. Enter queue name and description
2. Select strategy (ringall, roundrobin, etc.)
3. Configure timing settings
4. Add agents (extensions) to the queue
5. Monitor queue statistics in real-time

### Viewing Call History

**Call History**

- Filter by date range, extension, or number
- Search for specific calls
- Export to CSV or PDF
- Play call recordings (if available)

### Click-to-Call

1. Navigate to **Dashboard** or **Extensions**
2. Click the "Call" button next to an extension
3. Enter the destination number
4. Your extension will ring first, then the destination

### Generating Reports

**Reports > Generate Report**

1. Select report type (Call Volume, Agent Performance, etc.)
2. Choose date range
3. Select filters
4. Generate and download (CSV or PDF)

## 🔌 API Documentation

The application provides a RESTful API for integration with external systems.

### Authentication

All API requests require authentication using Laravel Sanctum tokens.

**Get Token:**

```bash
POST /api/auth/login
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "password"
}
```

**Use Token:**

```bash
Authorization: Bearer {your-token}
```

### Example API Requests

#### Get Active Calls

```bash
GET /api/calls/active
Authorization: Bearer {token}
```

#### Originate Call

```bash
POST /api/calls/originate
Authorization: Bearer {token}
Content-Type: application/json

{
  "extension": "100",
  "destination": "5551234567",
  "context": "from-internal"
}
```

#### Get Call History

```bash
GET /api/cdr?from=2024-01-01&to=2024-01-31
Authorization: Bearer {token}
```

Full API documentation available at: `/api/documentation`

## 🐛 Troubleshooting

### AMI Connection Failed

**Problem**: "Failed to connect to Asterisk AMI"

**Solution**:
1. Verify Asterisk is running: `sudo asterisk -rx "core show version"`
2. Check AMI is enabled in `/etc/asterisk/manager.conf`
3. Verify credentials in `.env` match `manager.conf`
4. Check firewall rules allow connection on port 5038

### No Active Calls Showing

**Problem**: Active calls not appearing in dashboard

**Solution**:
1. Ensure AMI listener is running: `sudo supervisorctl status asterisk-ami-listener`
2. Check logs: `tail -f storage/logs/ami-listener.log`
3. Verify database connection
4. Clear cache: `php artisan cache:clear`

### WebSocket Not Connecting

**Problem**: Real-time updates not working

**Solution**:
1. Ensure Redis is running: `redis-cli ping`
2. Verify `BROADCAST_DRIVER=redis` in `.env`
3. Check Laravel Echo configuration in `resources/js/bootstrap.js`
4. Clear browser cache

### Call Recordings Not Playing

**Problem**: Recording player shows error

**Solution**:
1. Verify recording path in `config/asterisk.php`
2. Check file permissions on recording directory
3. Ensure symlink exists: `php artisan storage:link`
4. Verify recording format is supported (WAV, MP3)

## 🧪 Testing

Run the test suite:

```bash
# Run all tests
php artisan test

# Run specific test
php artisan test --filter CallManagementTest

# With coverage
php artisan test --coverage
```

## 📈 Performance Optimization

### Production Optimizations

```bash
# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Optimize autoloader
composer install --optimize-autoloader --no-dev
```

### Database Optimization

```bash
# Add indexes for frequently queried fields
# Already included in migrations

# For large CDR tables, consider partitioning
```

### Redis Optimization

Adjust Redis configuration in `/etc/redis/redis.conf`:

```conf
maxmemory 256mb
maxmemory-policy allkeys-lru
```

## 🔐 Security Best Practices

1. **Change Default Credentials** - Immediately after installation
2. **Use HTTPS** - Install SSL certificate (Let's Encrypt)
3. **Restrict AMI Access** - Only allow local connections
4. **Enable Audit Logging** - Track all user actions
5. **Regular Updates** - Keep Laravel and dependencies updated
6. **Strong Passwords** - Enforce password policies
7. **Two-Factor Authentication** - Enable for admin users
8. **Backup Regularly** - Database and configuration files

## 📝 Contributing

Contributions are welcome! Please follow these guidelines:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🙏 Acknowledgments

- [Laravel Framework](https://laravel.com)
- [Vue.js](https://vuejs.org)
- [PAMI Library](https://github.com/marcelog/PAMI)
- [Bootstrap](https://getbootstrap.com)
- [Asterisk PBX](https://www.asterisk.org)

## 📞 Support

- **Documentation**: [https://docs.asterisk-gui.local](https://docs.asterisk-gui.local)
- **Issues**: [GitHub Issues](https://github.com/yourusername/asterisk-pbx-gui/issues)
- **Email**: support@asterisk-gui.local

## 🗺️ Roadmap

- [ ] Mobile application (React Native)
- [ ] Advanced analytics with AI
- [ ] Multi-tenant support
- [ ] WebRTC softphone integration
- [ ] Speech-to-text transcription
- [ ] CRM integration (Salesforce, HubSpot)
- [ ] SMS/WhatsApp integration
- [ ] Video conferencing
- [ ] Call quality monitoring
- [ ] Predictive dialer

---

**Built with ❤️ for the Asterisk community**

#   A s t e r i s k G U I  
 