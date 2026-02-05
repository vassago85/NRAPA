# Firearm Reference System - Local Setup Instructions

## Step 1: Run Migrations

Open your terminal in Laragon and navigate to the NRAPA directory, then run:

```bash
cd c:\laragon\www\NRAPA
php artisan migrate
```

This will create the following tables:
- `firearm_calibres`
- `firearm_calibre_aliases`
- `firearm_makes`
- `firearm_models`
- Add reference fields to `user_firearms` and `endorsement_firearms`

## Step 2: Import Reference Data

After migrations complete, import the comprehensive reference data:

```bash
php artisan nrapa:import-firearm-reference
```

This will import:
- ~100+ calibres (including 6mm GT, 22 Creedmoor, 6.5 Creedmoor, 300 PRC, 338 Lapua, etc.)
- Calibre aliases (e.g., "6GT", "9mm", ".308 Win")
- Firearm makes (Glock, Howa, Tikka, CZ, Bergara, etc.)
- Firearm models (G17, T3x, 1500, etc.)

Expected output:
```
Starting firearm reference data import...
Importing calibres...
  Processed: 100+ rows
  Created: 100+
  Updated: 0
Importing aliases...
  Processed: 70+ rows
  Created: 70+
Importing makes...
  Processed: 100+ rows
  Created: 100+
Importing models...
  Processed: 200+ rows
  Created: 200+
Import completed successfully!
```

## Step 3: Backfill Existing Data (Optional)

If you have existing firearm records, the backfill migration will attempt to resolve them:

```bash
php artisan migrate
```

The migration `2026_01_30_200000_backfill_firearm_reference_data.php` will:
- Try to match existing calibre strings to reference IDs
- Try to match existing make/model strings to reference IDs
- Store unmatched values in override fields

## Step 4: Verify Installation

Check that data was imported:

```bash
php artisan tinker
```

Then in tinker:
```php
\App\Models\FirearmCalibre::count()
\App\Models\FirearmMake::count()
\App\Models\FirearmModel::count()
\App\Models\FirearmCalibreAlias::count()
```

You should see:
- Calibres: 100+
- Makes: 100+
- Models: 200+
- Aliases: 70+

## Step 5: Test the Component

1. Navigate to: `http://nrapa.test/member/endorsements/create`
2. Go to Step 2 (Firearm Details)
3. You should see the new `FirearmSearchPanel` component with:
   - Calibre search with typeahead
   - Make/Model search with typeahead
   - SAPS 271 fields (type, action, serial numbers)
   - Auto-populated calibre metadata

## Troubleshooting

### Migration Errors

If you get foreign key errors, ensure tables are created in order:
1. `firearm_calibres`
2. `firearm_calibre_aliases` (depends on calibres)
3. `firearm_makes`
4. `firearm_models` (depends on makes)
5. Then add fields to `user_firearms` and `endorsement_firearms`

### Import Errors

If CSV import fails:
- Check that CSV files exist in `resources/data/`
- Verify CSV format matches expected headers
- Check file permissions

### Component Not Showing

- Clear cache: `php artisan cache:clear`
- Clear view cache: `php artisan view:clear`
- Check browser console for JavaScript errors

## Next Steps

After setup is complete:
1. Test creating an endorsement request with the new component
2. Test the admin reference data page: `/admin/firearm-reference`
3. Test API endpoints: `/api/calibres/suggest?query=6.5`
4. Refactor other forms (armoury, activities) to use the new component
