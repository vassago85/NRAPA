# ⚠️ Migration Required

## Error
The component is trying to access `firearm_calibres` table which doesn't exist yet.

## Solution

You need to run the migrations first. Run these commands in **Laragon Terminal**:

```bash
cd c:\laragon\www\NRAPA

# 1. Run migrations (creates the tables)
php artisan migrate --force

# 2. Import reference data (populates the tables)
php artisan nrapa:import-firearm-reference

# 3. Clear caches
php artisan optimize:clear
php artisan view:clear
```

## OR Use the Batch Script

Double-click `setup-firearm-reference.bat` in Windows Explorer.

## What Gets Created

The migrations will create:
- ✅ `firearm_calibres` table
- ✅ `firearm_calibre_aliases` table  
- ✅ `firearm_makes` table
- ✅ `firearm_models` table
- ✅ Add reference fields to `user_firearms` and `endorsement_firearms`

## After Migrations

Once migrations complete, the component will work and you'll be able to:
- Search for calibres
- Search for makes/models
- Use the comprehensive firearm reference system
