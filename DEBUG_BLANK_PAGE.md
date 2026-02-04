# Debugging Blank Page on /armoury/add

## Immediate Debugging Steps

### 1. Check Server Logs

```bash
# Check Laravel logs for errors
docker exec nrapa-app tail -100 /var/www/html/storage/logs/laravel.log

# Check PHP error logs
docker exec nrapa-app tail -50 /var/log/php-fpm/error.log

# Check Nginx error logs
docker exec nrapa-app tail -50 /var/log/nginx/error.log
```

### 2. Check if Firearm Reference Tables Exist

```bash
# Check if tables exist
docker exec nrapa-app php artisan tinker --execute="
echo 'Firearm Calibres: ' . (Schema::hasTable('firearm_calibres') ? 'EXISTS' : 'MISSING') . PHP_EOL;
echo 'Firearm Makes: ' . (Schema::hasTable('firearm_makes') ? 'EXISTS' : 'MISSING') . PHP_EOL;
echo 'Firearm Models: ' . (Schema::hasTable('firearm_models') ? 'EXISTS' : 'MISSING') . PHP_EOL;
"
```

### 3. Check Migration Status

```bash
# Check which migrations have run
docker exec nrapa-app php artisan migrate:status | grep firearm
```

### 4. Run Missing Migrations

If tables are missing:

```bash
# Run firearm reference migrations
docker exec nrapa-app php artisan migrate --path=database/migrations/2026_01_30_100000_create_firearm_calibres_table.php --force
docker exec nrapa-app php artisan migrate --path=database/migrations/2026_01_30_100002_create_firearm_makes_table.php --force
docker exec nrapa-app php artisan migrate --path=database/migrations/2026_01_30_100003_create_firearm_models_table.php --force
docker exec nrapa-app php artisan migrate --path=database/migrations/2026_01_30_100004_add_firearm_reference_fields_to_firearms_tables.php --force
```

### 5. Clear Livewire Cache

```bash
# Clear Livewire compiled views
docker exec nrapa-app php artisan view:clear
docker exec nrapa-app rm -rf /var/www/html/storage/framework/views/livewire
docker exec nrapa-app chmod -R 775 /var/www/html/storage/framework/views
```

### 6. Test Component Directly

```bash
# Try to access the component via tinker
docker exec nrapa-app php artisan tinker --execute="
try {
    \$component = new \App\Livewire\FirearmSearchPanel();
    echo 'Component loaded successfully';
} catch (\Exception \$e) {
    echo 'Error: ' . \$e->getMessage();
}
"
```

## Common Causes

1. **Missing Tables** - Firearm reference tables not migrated
2. **Permission Issues** - Storage directory not writable for Livewire compilation
3. **PHP Fatal Error** - Component failing during mount/initialization
4. **Missing Dependencies** - Models not found

## Quick Fix Commands

```bash
# Full fix sequence
cd /opt/nrapa
docker exec nrapa-app php artisan migrate --force
docker exec nrapa-app php artisan view:clear
docker exec nrapa-app rm -rf /var/www/html/storage/framework/views/livewire
docker exec nrapa-app chmod -R 775 /var/www/html/storage/framework/views
docker exec nrapa-app php artisan optimize:clear
docker compose restart app
```
