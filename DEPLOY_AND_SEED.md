# NRAPA Deploy & Seed Commands

## 🚀 Production Server (Docker)

### Complete Deploy & Seed (One Command):
```bash
cd /opt/nrapa && git pull && docker build -t nrapa-app:latest . && docker compose down && docker compose up -d && sleep 10 && docker exec nrapa-app php artisan migrate --force && docker exec nrapa-app php artisan db:seed --class=MembershipConfigurationSeeder --force && docker exec nrapa-app php artisan db:seed --class=ActivityConfigurationSeeder --force && docker exec nrapa-app php artisan config:cache && docker exec nrapa-app php artisan route:cache && docker logs nrapa-app --tail 30
```

### Step-by-Step Commands:

#### 1. Pull Latest Code & Rebuild:
```bash
cd /opt/nrapa
git pull
docker build -t nrapa-app:latest .
docker compose down
docker compose up -d
```

#### 2. Wait for Containers to Start:
```bash
sleep 10
```

#### 3. Run Migrations:
```bash
docker exec nrapa-app php artisan migrate --force
```

#### 4. Seed Database:
```bash
# Seed certificate types and membership configuration
docker exec nrapa-app php artisan db:seed --class=MembershipConfigurationSeeder --force

# Seed activity types and tags
docker exec nrapa-app php artisan db:seed --class=ActivityConfigurationSeeder --force

# Seed firearm types and calibres (if needed)
docker exec nrapa-app php artisan db:seed --class=FormDataSeeder --force
```

#### 5. Cache Configuration:
```bash
docker exec nrapa-app php artisan config:cache
docker exec nrapa-app php artisan route:cache
docker exec nrapa-app php artisan view:cache
```

#### 6. Verify:
```bash
docker exec nrapa-app php artisan migrate:status
docker logs nrapa-app --tail 30
```

---

## 💻 Local Development (Laragon/Windows)

### Complete Deploy & Seed (One Command):
```powershell
cd C:\laragon\www\NRAPA; git pull; php artisan migrate; php artisan db:seed --class=MembershipConfigurationSeeder; php artisan db:seed --class=ActivityConfigurationSeeder; php artisan config:clear; php artisan cache:clear; php artisan route:clear; php artisan view:clear
```

### Step-by-Step Commands:

#### 1. Pull Latest Code:
```powershell
cd C:\laragon\www\NRAPA
git pull
```

#### 2. Run Migrations:
```powershell
php artisan migrate
```

#### 3. Seed Database:
```powershell
# Seed certificate types and membership configuration
php artisan db:seed --class=MembershipConfigurationSeeder

# Seed activity types and tags
php artisan db:seed --class=ActivityConfigurationSeeder

# Seed firearm types and calibres (if needed)
php artisan db:seed --class=FormDataSeeder
```

#### 4. Clear Caches:
```powershell
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

---

## 📋 Quick Reference

### Production (Docker):
```bash
# Deploy only
cd /opt/nrapa && git pull && docker build -t nrapa-app:latest . && docker compose down && docker compose up -d

# Migrate only
docker exec nrapa-app php artisan migrate --force

# Seed only
docker exec nrapa-app php artisan db:seed --class=MembershipConfigurationSeeder --force
docker exec nrapa-app php artisan db:seed --class=ActivityConfigurationSeeder --force

# Full deploy + migrate + seed
cd /opt/nrapa && git pull && docker build -t nrapa-app:latest . && docker compose down && docker compose up -d && sleep 10 && docker exec nrapa-app php artisan migrate --force && docker exec nrapa-app php artisan db:seed --class=MembershipConfigurationSeeder --force && docker exec nrapa-app php artisan db:seed --class=ActivityConfigurationSeeder --force
```

### Local (Laragon):
```powershell
# Deploy only
cd C:\laragon\www\NRAPA; git pull

# Migrate only
php artisan migrate

# Seed only
php artisan db:seed --class=MembershipConfigurationSeeder
php artisan db:seed --class=ActivityConfigurationSeeder

# Full deploy + migrate + seed
cd C:\laragon\www\NRAPA; git pull; php artisan migrate; php artisan db:seed --class=MembershipConfigurationSeeder; php artisan db:seed --class=ActivityConfigurationSeeder
```

---

## ✅ Verification Commands

### Check Migration Status:
```bash
# Production
docker exec nrapa-app php artisan migrate:status

# Local
php artisan migrate:status
```

### Check Tables Exist:
```bash
# Production
docker exec nrapa-app php artisan tinker --execute="echo Schema::hasTable('payments') ? 'payments: OK' : 'payments: MISSING';"
docker exec nrapa-app php artisan tinker --execute="echo Schema::hasTable('activity_types') ? 'activity_types: OK' : 'activity_types: MISSING';"

# Local
php artisan tinker --execute="echo Schema::hasTable('payments') ? 'payments: OK' : 'payments: MISSING';"
```

### Check Activity Types Seeded:
```bash
# Production
docker exec nrapa-app php artisan tinker --execute="echo App\Models\ActivityType::where('track', 'sport')->count() . ' sport activity types';"

# Local
php artisan tinker --execute="echo App\Models\ActivityType::where('track', 'sport')->count() . ' sport activity types';"
```

---

## 🔍 Troubleshooting

### If migrations fail:
```bash
# Production
docker exec nrapa-app php artisan migrate:rollback --step=1
docker exec nrapa-app php artisan migrate --force

# Local
php artisan migrate:rollback --step=1
php artisan migrate
```

### If seeders fail:
```bash
# Production
docker exec nrapa-app php artisan db:seed --class=MembershipConfigurationSeeder --force -v

# Local
php artisan db:seed --class=MembershipConfigurationSeeder -v
```

### Clear all caches:
```bash
# Production
docker exec nrapa-app php artisan optimize:clear

# Local
php artisan optimize:clear
```
