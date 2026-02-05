# Fix Missing Firearm Reference Tables

## Problem
The `/armoury/add` page is blank because the firearm reference tables don't exist:
- `firearm_calibres` - MISSING
- `firearm_makes` - MISSING  
- `firearm_models` - MISSING

## Solution: Run Migrations

### Step 1: Check Migration Status

```bash
docker exec nrapa-app php artisan migrate:status | grep firearm
```

### Step 2: Run Firearm Reference Migrations

```bash
# Run all firearm reference migrations in order
docker exec nrapa-app php artisan migrate --path=database/migrations/2026_01_30_100000_create_firearm_calibres_table.php --force
docker exec nrapa-app php artisan migrate --path=database/migrations/2026_01_30_100001_create_firearm_calibre_aliases_table.php --force
docker exec nrapa-app php artisan migrate --path=database/migrations/2026_01_30_100002_create_firearm_makes_table.php --force
docker exec nrapa-app php artisan migrate --path=database/migrations/2026_01_30_100003_create_firearm_models_table.php --force
docker exec nrapa-app php artisan migrate --path=database/migrations/2026_01_30_100004_add_firearm_reference_fields_to_firearms_tables.php --force
docker exec nrapa-app php artisan migrate --path=database/migrations/2026_01_30_400000_add_ignition_to_firearm_calibres.php --force
```

### Step 3: Or Run All Pending Migrations

```bash
# Simpler - just run all pending migrations
docker exec nrapa-app php artisan migrate --force
```

### Step 4: Import Reference Data (Optional but Recommended)

After migrations, you may want to import the firearm reference data:

```bash
docker exec nrapa-app php artisan nrapa:import-firearm-reference
```

### Step 5: Verify Tables Created

```bash
docker exec nrapa-app php artisan tinker --execute="
use Illuminate\Support\Facades\Schema;
echo 'Firearm Calibres: ' . (Schema::hasTable('firearm_calibres') ? 'EXISTS' : 'MISSING') . PHP_EOL;
echo 'Firearm Makes: ' . (Schema::hasTable('firearm_makes') ? 'EXISTS' : 'MISSING') . PHP_EOL;
echo 'Firearm Models: ' . (Schema::hasTable('firearm_models') ? 'EXISTS' : 'MISSING') . PHP_EOL;
"
```

### Step 6: Clear Caches and Restart

```bash
docker exec nrapa-app php artisan optimize:clear
docker exec nrapa-app php artisan view:clear
docker compose restart app
```

## Quick One-Liner

```bash
docker exec nrapa-app php artisan migrate --force && docker exec nrapa-app php artisan optimize:clear && docker exec nrapa-app php artisan view:clear && docker compose restart app
```
