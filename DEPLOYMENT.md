# NRAPA Deployment Guide

## Prerequisites

- PHP 8.2+ with extensions: BCMath, Ctype, Fileinfo, JSON, Mbstring, OpenSSL, PDO, Tokenizer, XML
- MySQL 8.0+ or PostgreSQL 13+
- Composer 2.x
- Node.js 18+ & NPM (for building assets)
- Git

## Server Setup (Ubuntu/Debian)

### 1. Install Required Packages

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install PHP 8.3 and extensions
sudo add-apt-repository ppa:ondrej/php
sudo apt install php8.3 php8.3-fpm php8.3-cli php8.3-mysql php8.3-pgsql \
    php8.3-mbstring php8.3-xml php8.3-bcmath php8.3-curl php8.3-zip \
    php8.3-gd php8.3-intl -y

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install Node.js 18
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt install nodejs -y

# Install MySQL
sudo apt install mysql-server -y
sudo mysql_secure_installation
```

### 2. Create Database

```bash
sudo mysql -u root -p
```

```sql
CREATE DATABASE nrapa_members CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'nrapa_user'@'localhost' IDENTIFIED BY 'YOUR_STRONG_PASSWORD';
GRANT ALL PRIVILEGES ON nrapa_members.* TO 'nrapa_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 3. Deploy Application

```bash
# Navigate to web directory
cd /var/www

# Clone repository (or upload files)
git clone YOUR_REPO_URL nrapa
cd nrapa

# Set permissions
sudo chown -R www-data:www-data /var/www/nrapa
sudo chmod -R 755 /var/www/nrapa
sudo chmod -R 775 /var/www/nrapa/storage
sudo chmod -R 775 /var/www/nrapa/bootstrap/cache

# Install PHP dependencies (no dev packages)
composer install --no-dev --optimize-autoloader

# Copy and configure environment
cp .env.production.example .env
nano .env  # Edit with your production values

# Generate application key
php artisan key:generate

# Run migrations
php artisan migrate --force

# Seed initial data
php artisan db:seed --force

# Build assets
npm install
npm run build

# Cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan icons:cache
```

### 4. Configure Nginx

Create `/etc/nginx/sites-available/nrapa`:

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name members.nrapa.co.za;
    root /var/www/nrapa/public;

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
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

```bash
# Enable site
sudo ln -s /etc/nginx/sites-available/nrapa /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

### 5. SSL Certificate (Let's Encrypt)

```bash
sudo apt install certbot python3-certbot-nginx -y
sudo certbot --nginx -d members.nrapa.co.za
```

### 6. Set Up Scheduler (Cron)

```bash
sudo crontab -e -u www-data
```

Add this line:
```
* * * * * cd /var/www/nrapa && php artisan schedule:run >> /dev/null 2>&1
```

### 7. Schedule License Notifications

Add to `app/Console/Kernel.php` or `routes/console.php`:

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('nrapa:send-license-expiry-notifications')->daily();
```

## Quick Deployment Updates

After pushing changes to your repository:

```bash
cd /var/www/nrapa

# Pull latest changes
git pull origin main

# Install any new dependencies
composer install --no-dev --optimize-autoloader

# Run migrations
php artisan migrate --force

# Clear and rebuild caches
php artisan optimize:clear
php artisan optimize

# Rebuild assets if needed
npm install
npm run build

# Restart PHP-FPM
sudo systemctl restart php8.3-fpm
```

## Common Commands

```bash
# View logs
tail -f /var/www/nrapa/storage/logs/laravel.log

# Run queue worker (if using queues)
php artisan queue:work --daemon

# Clear all caches
php artisan optimize:clear

# Check application status
php artisan about
```

## Security Checklist

- [ ] APP_DEBUG=false
- [ ] APP_ENV=production
- [ ] Strong database password
- [ ] SSL certificate installed
- [ ] File permissions correct (755 for directories, 644 for files)
- [ ] Storage and bootstrap/cache writable by web server
- [ ] .env file not accessible from web
- [ ] Regular backups configured

## Troubleshooting

### 500 Error
```bash
# Check Laravel logs
tail -100 /var/www/nrapa/storage/logs/laravel.log

# Check PHP-FPM logs
sudo tail -100 /var/log/php8.3-fpm.log

# Check Nginx logs
sudo tail -100 /var/log/nginx/error.log
```

### Permission Issues
```bash
sudo chown -R www-data:www-data /var/www/nrapa
sudo find /var/www/nrapa -type d -exec chmod 755 {} \;
sudo find /var/www/nrapa -type f -exec chmod 644 {} \;
sudo chmod -R 775 /var/www/nrapa/storage
sudo chmod -R 775 /var/www/nrapa/bootstrap/cache
```

### Clear All Caches
```bash
php artisan optimize:clear
composer dump-autoload
```
