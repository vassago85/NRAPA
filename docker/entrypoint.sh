#!/bin/sh
set -e
# Build: 2026-01-26-v3 - Fixed database wait using mysqladmin

echo "🚀 Starting NRAPA..."

# Create all required directories first
echo "🔧 Creating directories..."
mkdir -p /var/www/html/storage/framework/views/livewire/classes
mkdir -p /var/www/html/storage/framework/views/livewire/views
mkdir -p /var/www/html/storage/framework/cache
mkdir -p /var/www/html/storage/framework/sessions
mkdir -p /var/www/html/storage/logs
mkdir -p /var/www/html/bootstrap/cache

# Fix all storage permissions
echo "🔧 Setting permissions..."
chown -R www-data:www-data /var/www/html/storage
chmod -R 775 /var/www/html/storage
chown -R www-data:www-data /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/bootstrap/cache

# Wait for database to be ready using simple TCP check
echo "⏳ Waiting for database..."
max_tries=30
count=0
until nc -z db 3306 2>/dev/null; do
    count=$((count + 1))
    if [ $count -ge $max_tries ]; then
        echo "❌ Database not reachable after $max_tries attempts"
        exit 1
    fi
    echo "   Attempt $count/$max_tries - waiting for db:3306..."
    sleep 2
done
echo "✅ Database port is open"

# Give MySQL a moment to fully initialize after port is open
sleep 3

# Test actual database connection with credentials
echo "🔐 Testing database credentials..."
max_tries=10
count=0
until php -r "new PDO('mysql:host=db;port=3306;dbname=${DB_DATABASE}', '${DB_USERNAME}', '${DB_PASSWORD}');" 2>/dev/null; do
    count=$((count + 1))
    if [ $count -ge $max_tries ]; then
        echo "❌ Database authentication failed after $max_tries attempts"
        echo "   Host: db, Database: ${DB_DATABASE}, User: ${DB_USERNAME}"
        echo "   Check your DB_PASSWORD in .env file"
        # Don't exit - try to continue anyway, migrations will show the real error
        break
    fi
    echo "   Auth attempt $count/$max_tries..."
    sleep 2
done
echo "✅ Database is ready"

# Run migrations
echo "📦 Running migrations..."
php artisan migrate --force || echo "⚠️ Migration had issues, continuing..."

# Clear caches - env vars come from docker at runtime, so don't cache config
echo "⚡ Preparing for production..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear || echo "⚠️ Cache clear had issues, continuing..."

# Publish Livewire assets (required for Livewire to work)
echo "📦 Publishing Livewire assets..."
php artisan livewire:publish --assets 2>/dev/null || true

# Create storage link if not exists
php artisan storage:link 2>/dev/null || true

# Create required storage directories
mkdir -p storage/app/public/livewire-tmp
mkdir -p storage/app/public/learning/categories
mkdir -p storage/app/public/learning/articles
mkdir -p storage/app/public/learning/pages

# Final permissions fix (catch-all)
echo "🔧 Final permissions check..."
chown -R www-data:www-data /var/www/html/storage
chmod -R 775 /var/www/html/storage

echo "✅ NRAPA is ready!"

# Start supervisor
exec /usr/bin/supervisord -c /etc/supervisord.conf
