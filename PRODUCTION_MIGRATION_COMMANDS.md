# Production Migration & Seeding Commands

## ⚠️ Important: Production Mode

Laravel blocks migrations/seeds in production by default. Use the `--force` flag.

## ✅ Correct Commands

### Run Migrations (with --force flag):
```bash
docker exec nrapa-app php artisan migrate --force
```

### Seed Certificate Types (with --force flag):
```bash
docker exec nrapa-app php artisan db:seed --class=MembershipConfigurationSeeder --force
```

## 🔍 Verify Database Tables

### Option 1: From App Container (Recommended)
```bash
# Check tables via Laravel
docker exec nrapa-app php artisan tinker --execute="echo \Illuminate\Support\Facades\Schema::hasTable('payments') ? 'payments table exists' : 'payments table missing';"
docker exec nrapa-app php artisan tinker --execute="echo \Illuminate\Support\Facades\Schema::hasTable('member_status_history') ? 'member_status_history table exists' : 'member_status_history table missing';"
docker exec nrapa-app php artisan tinker --execute="echo \Illuminate\Support\Facades\Schema::hasTable('comments') ? 'comments table exists' : 'comments table missing';"
```

### Option 2: Direct MySQL (if password is correct)
```bash
# Use root user instead of nrapa user
docker exec nrapa-db mysql -uroot -p'${DB_ROOT_PASSWORD}' nrapa -e "SHOW TABLES LIKE 'payments';"

# Or check from app container using Laravel's DB connection
docker exec nrapa-app php artisan tinker --execute="DB::select('SHOW TABLES LIKE \"payments\"');"
```

### Option 3: Check Migration Status
```bash
docker exec nrapa-app php artisan migrate:status
```

## 📋 Complete Production Deployment Steps

```bash
# 1. Pull latest code
cd /opt/nrapa
git pull

# 2. Rebuild container
docker build -t nrapa-app:latest .
docker compose down
docker compose up -d

# 3. Wait for containers to be ready
sleep 10

# 4. Run migrations (with --force)
docker exec nrapa-app php artisan migrate --force

# 5. Seed certificate types (with --force)
docker exec nrapa-app php artisan db:seed --class=MembershipConfigurationSeeder --force

# 6. Clear and cache config
docker exec nrapa-app php artisan config:cache
docker exec nrapa-app php artisan route:cache
docker exec nrapa-app php artisan view:cache

# 7. Verify
docker exec nrapa-app php artisan migrate:status
docker logs nrapa-app --tail 30
```

## 🔐 Database Access

If you need to access the database directly:

```bash
# Get the root password from environment
docker exec nrapa-app printenv DB_ROOT_PASSWORD

# Then use it to connect
docker exec -it nrapa-db mysql -uroot -p'<root_password_from_env>' nrapa

# Or use the app container's DB connection
docker exec -it nrapa-app php artisan tinker
# Then in tinker:
# DB::table('payments')->count();
# Schema::hasTable('payments');
```

## ⚠️ Troubleshooting

### If migrations still fail:
```bash
# Check Laravel logs
docker exec nrapa-app tail -50 /var/www/html/storage/logs/laravel.log

# Check migration files exist
docker exec nrapa-app ls -la /var/www/html/database/migrations/ | grep 2026_01_29

# Check database connection
docker exec nrapa-app php artisan tinker --execute="DB::connection()->getPdo();"
```

### If seeder fails:
```bash
# Check if seeder class exists
docker exec nrapa-app php artisan tinker --execute="class_exists('Database\Seeders\MembershipConfigurationSeeder');"

# Run seeder with verbose output
docker exec nrapa-app php artisan db:seed --class=MembershipConfigurationSeeder --force -v
```
