# Deployment Guide

## Overview

This guide covers the deployment process for the CussUp multi-tenant support system, including server setup, environment configuration, and production deployment best practices.

## Prerequisites

### Server Requirements

- **Operating System**: Ubuntu 20.04+ or CentOS 8+
- **PHP**: 8.2+ with required extensions
- **Database**: MySQL 8.0+ or PostgreSQL 13+
- **Web Server**: Nginx or Apache
- **SSL Certificate**: Valid SSL certificate for production
- **Memory**: Minimum 2GB RAM, 4GB+ recommended
- **Storage**: Minimum 20GB, SSD recommended

### Required PHP Extensions

```bash
# Install required PHP extensions
sudo apt-get install php8.2-fpm php8.2-mysql php8.2-xml php8.2-curl php8.2-mbstring php8.2-zip php8.2-gd php8.2-bcmath php8.2-intl php8.2-redis
```

## Server Setup

### 1. System Updates

```bash
# Update system packages
sudo apt update && sudo apt upgrade -y

# Install essential packages
sudo apt install -y curl wget git unzip software-properties-common apt-transport-https ca-certificates gnupg lsb-release
```

### 2. Install PHP 8.2

```bash
# Add PHP repository
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update

# Install PHP 8.2 and extensions
sudo apt install -y php8.2-fpm php8.2-cli php8.2-common php8.2-mysql php8.2-zip php8.2-gd php8.2-mbstring php8.2-curl php8.2-xml php8.2-bcmath php8.2-intl php8.2-redis php8.2-opcache

# Verify installation
php -v
```

### 3. Install Composer

```bash
# Download and install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
sudo chmod +x /usr/local/bin/composer

# Verify installation
composer --version
```

### 4. Install Node.js

```bash
# Install Node.js 18+
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt-get install -y nodejs

# Verify installation
node --version
npm --version
```

### 5. Install Database

#### MySQL 8.0
```bash
# Install MySQL
sudo apt install -y mysql-server

# Secure MySQL installation
sudo mysql_secure_installation

# Create database and user
sudo mysql -u root -p
```

```sql
CREATE DATABASE cussup CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'cussup_user'@'localhost' IDENTIFIED BY 'strong_password';
GRANT ALL PRIVILEGES ON cussup.* TO 'cussup_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

#### PostgreSQL 13+ (Alternative)
```bash
# Install PostgreSQL
sudo apt install -y postgresql postgresql-contrib

# Create database and user
sudo -u postgres psql
```

```sql
CREATE DATABASE cussup;
CREATE USER cussup_user WITH PASSWORD 'strong_password';
GRANT ALL PRIVILEGES ON DATABASE cussup TO cussup_user;
\q
```

### 6. Install Redis

```bash
# Install Redis
sudo apt install -y redis-server

# Configure Redis
sudo nano /etc/redis/redis.conf
```

Add/modify these settings:
```
maxmemory 256mb
maxmemory-policy allkeys-lru
```

```bash
# Restart Redis
sudo systemctl restart redis-server
sudo systemctl enable redis-server
```

### 7. Install Nginx

```bash
# Install Nginx
sudo apt install -y nginx

# Start and enable Nginx
sudo systemctl start nginx
sudo systemctl enable nginx
```

## Application Deployment

### 1. Clone Repository

```bash
# Create application directory
sudo mkdir -p /var/www/cussup
sudo chown $USER:$USER /var/www/cussup

# Clone repository
cd /var/www/cussup
git clone <repository-url> .
```

### 2. Install Dependencies

```bash
# Install PHP dependencies
composer install --optimize-autoloader --no-dev

# Install Node.js dependencies
npm ci

# Build frontend assets
npm run build
```

### 3. Environment Configuration

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Edit environment file
nano .env
```

#### Production Environment Variables

```env
APP_NAME="CussUp Support System"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=cussup
DB_USERNAME=cussup_user
DB_PASSWORD=strong_password

BROADCAST_DRIVER=log
CACHE_DRIVER=redis
FILESYSTEM_DISK=local
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
SESSION_LIFETIME=120

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="noreply@yourdomain.com"
MAIL_FROM_NAME="${APP_NAME}"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

PUSHER_APP_ID=
PUSHER_APP_KEY=
PUSHER_APP_SECRET=
PUSHER_HOST=
PUSHER_PORT=443
PUSHER_SCHEME=https
PUSHER_APP_CLUSTER=mt1

VITE_APP_NAME="${APP_NAME}"
VITE_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
VITE_PUSHER_HOST="${PUSHER_HOST}"
VITE_PUSHER_PORT="${PUSHER_PORT}"
VITE_PUSHER_SCHEME="${PUSHER_SCHEME}"
VITE_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"
```

### 4. Database Setup

```bash
# Run migrations
php artisan migrate --force

# Seed database (if needed)
php artisan db:seed --force

# Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 5. File Permissions

```bash
# Set proper permissions
sudo chown -R www-data:www-data /var/www/cussup
sudo chmod -R 755 /var/www/cussup
sudo chmod -R 775 /var/www/cussup/storage
sudo chmod -R 775 /var/www/cussup/bootstrap/cache
```

## Web Server Configuration

### Nginx Configuration

Create Nginx configuration file:

```bash
sudo nano /etc/nginx/sites-available/cussup
```

```nginx
server {
    listen 80;
    server_name yourdomain.com www.yourdomain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name yourdomain.com www.yourdomain.com;

    # SSL Configuration
    ssl_certificate /etc/letsencrypt/live/yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/yourdomain.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-RSA-AES256-GCM-SHA512:DHE-RSA-AES256-GCM-SHA512:ECDHE-RSA-AES256-GCM-SHA384:DHE-RSA-AES256-GCM-SHA384;
    ssl_prefer_server_ciphers off;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;

    # Root directory
    root /var/www/cussup/public;
    index index.php index.html index.htm;

    # Handle PHP files
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Deny access to hidden files
    location ~ /\. {
        deny all;
    }

    # Handle static files
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # Main location block
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_proxied expired no-cache no-store private must-revalidate auth;
    gzip_types text/plain text/css text/xml text/javascript application/x-javascript application/xml+rss application/javascript;
}
```

Enable the site:

```bash
sudo ln -s /etc/nginx/sites-available/cussup /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

### Apache Configuration (Alternative)

If using Apache, create virtual host:

```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    ServerAlias www.yourdomain.com
    DocumentRoot /var/www/cussup/public
    
    <Directory /var/www/cussup/public>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/cussup_error.log
    CustomLog ${APACHE_LOG_DIR}/cussup_access.log combined
</VirtualHost>
```

## SSL Certificate

### Let's Encrypt SSL

```bash
# Install Certbot
sudo apt install -y certbot python3-certbot-nginx

# Obtain SSL certificate
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com

# Auto-renewal
sudo crontab -e
```

Add this line for auto-renewal:
```
0 12 * * * /usr/bin/certbot renew --quiet
```

## Queue Workers

### Supervisor Configuration

Install Supervisor:

```bash
sudo apt install -y supervisor
```

Create configuration file:

```bash
sudo nano /etc/supervisor/conf.d/cussup.conf
```

```ini
[program:cussup-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/cussup/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/cussup/storage/logs/worker.log
stopwaitsecs=3600
```

Start Supervisor:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start cussup-worker:*
```

## Monitoring and Logging

### Application Logs

```bash
# View Laravel logs
tail -f /var/www/cussup/storage/logs/laravel.log

# View queue worker logs
tail -f /var/www/cussup/storage/logs/worker.log
```

### System Monitoring

Install monitoring tools:

```bash
# Install htop for system monitoring
sudo apt install -y htop

# Monitor system resources
htop
```

### Laravel Telescope (Development Only)

For debugging in development:

```bash
# Install Telescope
composer require laravel/telescope --dev

# Publish configuration
php artisan telescope:install

# Access Telescope
http://yourdomain.com/telescope
```

## Backup Strategy

### Database Backup

Create backup script:

```bash
sudo nano /var/www/cussup/backup.sh
```

```bash
#!/bin/bash

# Database backup
mysqldump -u cussup_user -p'strong_password' cussup > /var/www/cussup/storage/backups/cussup_$(date +%Y%m%d_%H%M%S).sql

# Keep only last 7 days of backups
find /var/www/cussup/storage/backups -name "*.sql" -mtime +7 -delete
```

Make executable and add to cron:

```bash
chmod +x /var/www/cussup/backup.sh

# Add to crontab
crontab -e
```

Add this line for daily backups:
```
0 2 * * * /var/www/cussup/backup.sh
```

### File Backup

```bash
# Backup application files
tar -czf /var/www/cussup/storage/backups/cussup_files_$(date +%Y%m%d_%H%M%S).tar.gz /var/www/cussup --exclude=/var/www/cussup/storage/logs --exclude=/var/www/cussup/storage/framework/cache
```

## Performance Optimization

### PHP Optimization

Edit PHP configuration:

```bash
sudo nano /etc/php/8.2/fpm/php.ini
```

Optimize these settings:
```ini
memory_limit = 512M
max_execution_time = 60
upload_max_filesize = 64M
post_max_size = 64M
opcache.enable = 1
opcache.memory_consumption = 128
opcache.interned_strings_buffer = 8
opcache.max_accelerated_files = 4000
opcache.revalidate_freq = 2
opcache.fast_shutdown = 1
```

### Redis Optimization

Edit Redis configuration:

```bash
sudo nano /etc/redis/redis.conf
```

Optimize these settings:
```
maxmemory 512mb
maxmemory-policy allkeys-lru
save 900 1
save 300 10
save 60 10000
```

### Nginx Optimization

Edit Nginx configuration:

```nginx
# Add to http block in /etc/nginx/nginx.conf
client_max_body_size 64M;
client_body_timeout 60s;
client_header_timeout 60s;
keepalive_timeout 65;
send_timeout 60s;
```

## Security Hardening

### Firewall Configuration

```bash
# Install UFW
sudo apt install -y ufw

# Configure firewall
sudo ufw default deny incoming
sudo ufw default allow outgoing
sudo ufw allow ssh
sudo ufw allow 'Nginx Full'
sudo ufw enable
```

### File Permissions

```bash
# Secure file permissions
sudo find /var/www/cussup -type f -exec chmod 644 {} \;
sudo find /var/www/cussup -type d -exec chmod 755 {} \;
sudo chmod -R 775 /var/www/cussup/storage
sudo chmod -R 775 /var/www/cussup/bootstrap/cache
```

### Security Headers

Add security headers to Nginx configuration:

```nginx
# Add to server block
add_header X-Frame-Options "SAMEORIGIN" always;
add_header X-XSS-Protection "1; mode=block" always;
add_header X-Content-Type-Options "nosniff" always;
add_header Referrer-Policy "no-referrer-when-downgrade" always;
add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;
```

## Troubleshooting

### Common Issues

#### 500 Internal Server Error
```bash
# Check Laravel logs
tail -f /var/www/cussup/storage/logs/laravel.log

# Check Nginx error logs
sudo tail -f /var/log/nginx/error.log

# Check PHP-FPM logs
sudo tail -f /var/log/php8.2-fpm.log
```

#### Queue Workers Not Running
```bash
# Check Supervisor status
sudo supervisorctl status

# Restart workers
sudo supervisorctl restart cussup-worker:*
```

#### Database Connection Issues
```bash
# Test database connection
php artisan tinker
DB::connection()->getPdo();

# Check database configuration
php artisan config:show database
```

#### File Permission Issues
```bash
# Fix permissions
sudo chown -R www-data:www-data /var/www/cussup
sudo chmod -R 755 /var/www/cussup
sudo chmod -R 775 /var/www/cussup/storage
sudo chmod -R 775 /var/www/cussup/bootstrap/cache
```

## Maintenance

### Regular Maintenance Tasks

#### Daily
- Monitor application logs
- Check queue worker status
- Monitor system resources

#### Weekly
- Review error logs
- Check backup status
- Update system packages

#### Monthly
- Review performance metrics
- Update SSL certificates
- Clean old log files

### Update Process

```bash
# Create backup before updates
/var/www/cussup/backup.sh

# Pull latest changes
cd /var/www/cussup
git pull origin main

# Install dependencies
composer install --optimize-autoloader --no-dev
npm ci
npm run build

# Run migrations
php artisan migrate --force

# Clear and rebuild caches
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Restart services
sudo systemctl restart php8.2-fpm
sudo systemctl restart nginx
sudo supervisorctl restart cussup-worker:*
```

---

This deployment guide provides comprehensive instructions for deploying the CussUp support system to production. Follow these steps carefully and ensure all security measures are implemented before going live. 