#!/bin/sh
set -e

echo "🚀 Starting NRAPA..."

# Wait for database to be ready
echo "⏳ Waiting for database..."
while ! php artisan db:monitor --databases=mysql > /dev/null 2>&1; do
    sleep 2
done
echo "✅ Database is ready"

# Run migrations
echo "📦 Running migrations..."
php artisan migrate --force

# Clear and cache config for production
echo "⚡ Optimizing for production..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Create storage link if not exists
php artisan storage:link 2>/dev/null || true

echo "✅ NRAPA is ready!"

# Start supervisor
exec /usr/bin/supervisord -c /etc/supervisord.conf
