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

# Clear caches - env vars come from docker at runtime, so don't cache config
echo "⚡ Preparing for production..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# Publish Livewire assets (required for Livewire to work)
echo "📦 Publishing Livewire assets..."
php artisan livewire:publish --assets 2>/dev/null || true

# Create storage link if not exists
php artisan storage:link 2>/dev/null || true

echo "✅ NRAPA is ready!"

# Start supervisor
exec /usr/bin/supervisord -c /etc/supervisord.conf
