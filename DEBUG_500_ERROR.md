# Debugging 500 Error on Login

## Quick Debugging Steps

### 1. Check Laravel Logs

```bash
# On server, check the latest errors
docker exec nrapa-app tail -100 /var/www/html/storage/logs/laravel.log

# Or check container logs
docker logs nrapa-app --tail 100
```

### 2. Common Causes After Deployment

#### A. Cache Issues
```bash
docker exec nrapa-app php artisan optimize:clear
docker exec nrapa-app php artisan config:clear
docker exec nrapa-app php artisan route:clear
docker exec nrapa-app php artisan view:clear
docker exec nrapa-app php artisan cache:clear
```

#### B. Missing Environment Variables
```bash
# Check if APP_KEY is set
docker exec nrapa-app php artisan tinker --execute="echo config('app.key');"

# Check database connection
docker exec nrapa-app php artisan tinker --execute="DB::connection()->getPdo();"
```

#### C. Permission Issues
```bash
# Fix storage permissions
docker exec nrapa-app chmod -R 775 /var/www/html/storage
docker exec nrapa-app chmod -R 775 /var/www/html/bootstrap/cache
```

#### D. Database Connection Issues
```bash
# Test database connection
docker exec nrapa-app php artisan migrate:status
```

### 3. Enable Debug Mode Temporarily

```bash
# Check current APP_DEBUG setting
docker exec nrapa-app php artisan tinker --execute="echo config('app.debug');"

# If needed, update .env (but don't leave debug on in production!)
```

### 4. Check Specific Error

The log file will show the exact error. Common issues:

- **Class not found** → Run `composer dump-autoload`
- **Route not found** → Clear route cache
- **Database error** → Check database connection
- **Permission denied** → Fix storage permissions
- **Missing config** → Clear config cache

## Quick Fix Commands

Run these in order:

```bash
# 1. Clear all caches
docker exec nrapa-app php artisan optimize:clear

# 2. Fix permissions
docker exec nrapa-app chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# 3. Regenerate autoload
docker exec nrapa-app composer dump-autoload

# 4. Restart container
docker compose restart app

# 5. Check logs again
docker exec nrapa-app tail -50 /var/www/html/storage/logs/laravel.log
```
