# Run Migrations Now

## Quick Command

Open your **Laragon terminal** (or any terminal where PHP is available) and run:

```bash
cd c:\laragon\www\NRAPA
php artisan migrate --force
php artisan nrapa:import-firearm-reference
php artisan optimize:clear
```

## Or Use the Batch Script

Double-click: `deploy-and-test.bat`

This will:
1. ✅ Run all migrations
2. ✅ Import firearm reference data
3. ✅ Clear all caches
4. ✅ Verify installation

## Expected Output

You should see:
```
Migrating: 2026_01_30_100000_create_firearm_calibres_table
Migrated:  2026_01_30_100000_create_firearm_calibres_table
Migrating: 2026_01_30_100001_create_firearm_calibre_aliases_table
Migrated:  2026_01_30_100001_create_firearm_calibre_aliases_table
Migrating: 2026_01_30_100002_create_firearm_makes_table
Migrated:  2026_01_30_100002_create_firearm_makes_table
Migrating: 2026_01_30_100003_create_firearm_models_table
Migrated:  2026_01_30_100003_create_firearm_models_table
Migrating: 2026_01_30_100004_add_firearm_reference_fields_to_firearms_tables
Migrated:  2026_01_30_100004_add_firearm_reference_fields_to_firearms_tables
Migrating: 2026_01_30_200000_backfill_firearm_reference_data
Migrated:  2026_01_30_200000_backfill_firearm_reference_data
```

Then:
```
Starting firearm reference data import...
Importing calibres...
  Processed: 100+ rows
Importing aliases...
  Processed: 70+ rows
Importing makes...
  Processed: 100+ rows
Importing models...
  Processed: 200+ rows
Import completed successfully!
```

## After Running

The admin firearm reference page should now work without errors!

Visit: http://nrapa.test/admin/firearm-reference
