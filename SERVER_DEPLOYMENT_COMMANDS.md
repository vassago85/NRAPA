# Corrected Server Deployment Commands

## Issues Fixed

1. ✅ Migration now checks columns before adding (prevents duplicate column error)
2. ✅ Assets are pre-built in Dockerfile (npm not needed at runtime)
3. ✅ Correct service/container names

## Corrected Deployment Steps

**On your server, run:**

```bash
cd /opt/nrapa

# 1. Pull latest code
git pull origin main

# 2. Rebuild Docker image (includes npm build step)
docker build -t nrapa-app:latest .

# 3. Restart containers with new image
docker compose down
docker compose up -d

# 4. Run migrations (after containers are up)
docker exec nrapa-app php artisan migrate --force

# 5. Clear all caches
docker exec nrapa-app php artisan optimize:clear
docker exec nrapa-app php artisan config:clear
docker exec nrapa-app php artisan route:clear
docker exec nrapa-app php artisan view:clear
docker exec nrapa-app php artisan cache:clear

# 6. Verify deployment
docker logs nrapa-app --tail 30
```

## Quick One-Liner

```bash
cd /opt/nrapa && git pull origin main && docker build -t nrapa-app:latest . && docker compose down && docker compose up -d && sleep 5 && docker exec nrapa-app php artisan migrate --force && docker exec nrapa-app php artisan optimize:clear
```

## Important Notes

- **Assets are built during `docker build`** - The Dockerfile runs `npm install` and `npm run build` during image build
- **No need to run npm in container** - Assets are already compiled in the image
- **Service name is `app`** but container name is `nrapa-app`
- **Migration fix** - Now checks if columns exist before adding them

## If Migration Still Fails

If you still get the duplicate column error, you can skip that specific migration:

```bash
# Mark migration as run without executing
docker exec nrapa-app php artisan migrate --pretend
# Or manually mark it:
docker exec nrapa-app php artisan db:seed --class=DatabaseSeeder  # if you have a seeder that marks migrations
```

Or fix it manually in the database:

```bash
# Connect to database
docker exec -it nrapa-db mysql -u nrapa -p nrapa

# Check if column exists
SHOW COLUMNS FROM activity_types LIKE 'track';

# If it exists, the migration will skip it now with the fix
```
