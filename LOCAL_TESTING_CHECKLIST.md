# Local Testing Checklist

## Pre-Deployment Checks ✅

### 1. Verify All Files Exist

```bash
# Check migrations
ls database/migrations/2026_01_30_*.php

# Check models
ls app/Models/Firearm*.php

# Check CSV data files
ls resources/data/*.csv

# Check component
ls app/Livewire/FirearmSearchPanel.php
ls resources/views/livewire/firearm-search-panel.blade.php
```

### 2. Run Migrations

```bash
cd c:\laragon\www\NRAPA
php artisan migrate --force
```

**Expected Output:**
```
Migrating: 2026_01_30_100000_create_firearm_calibres_table
Migrated:  2026_01_30_100000_create_firearm_calibres_table (XX.XXms)
Migrating: 2026_01_30_100001_create_firearm_calibre_aliases_table
Migrated:  2026_01_30_100001_create_firearm_calibre_aliases_table (XX.XXms)
Migrating: 2026_01_30_100002_create_firearm_makes_table
Migrated:  2026_01_30_100002_create_firearm_makes_table (XX.XXms)
Migrating: 2026_01_30_100003_create_firearm_models_table
Migrated:  2026_01_30_100003_create_firearm_models_table (XX.XXms)
Migrating: 2026_01_30_100004_add_firearm_reference_fields_to_firearms_tables
Migrated:  2026_01_30_100004_add_firearm_reference_fields_to_firearms_tables (XX.XXms)
Migrating: 2026_01_30_200000_backfill_firearm_reference_data
Migrated:  2026_01_30_200000_backfill_firearm_reference_data (XX.XXms)
```

### 3. Import Reference Data

```bash
php artisan nrapa:import-firearm-reference
```

**Expected Output:**
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

### 4. Clear All Caches

```bash
php artisan optimize:clear
php artisan view:clear
php artisan config:clear
php artisan route:clear
```

### 5. Verify Data Imported

```bash
php artisan tinker
```

Then in tinker:
```php
\App\Models\FirearmCalibre::count()
// Should return: 100+

\App\Models\FirearmMake::count()
// Should return: 100+

\App\Models\FirearmModel::count()
// Should return: 200+

\App\Models\FirearmCalibreAlias::count()
// Should return: 70+

// Test a search
\App\Models\FirearmCalibre::search('6.5 Creedmoor')->first()
// Should return a FirearmCalibre model

\App\Models\FirearmMake::search('Glock')->first()
// Should return a FirearmMake model
```

## Testing Checklist

### ✅ Component Loading Test

1. **Visit test page**: http://nrapa.test/test-firearm-panel
   - ✅ Page loads without errors
   - ✅ Component renders
   - ✅ No 404 errors in console

### ✅ Calibre Search Test

1. **Type in calibre search**: "6.5"
   - ✅ Shows suggestions dropdown
   - ✅ Includes "6.5 Creedmoor", "6.5 PRC", etc.
   - ✅ Shows calibre metadata when selected

2. **Test aliases**: Type "6GT"
   - ✅ Finds "6mm GT" via alias

3. **Select a calibre**:
   - ✅ Populates `firearm_calibre_id`
   - ✅ Shows metadata panel (category, family, bullet diameter, etc.)
   - ✅ Can clear selection

### ✅ Make/Model Search Test

1. **Type in make search**: "Glock"
   - ✅ Shows "Glock" in suggestions
   - ✅ Can select make

2. **After selecting make, type in model**: "G17"
   - ✅ Shows models filtered by make
   - ✅ Can select model

3. **Test custom values**:
   - ✅ Can use "Use custom value" for unknown make/model
   - ✅ Stores in override fields

### ✅ SAPS 271 Fields Test

1. **Select firearm type**: "Rifle"
   - ✅ Field updates
   - ✅ Can select "Other" and specify

2. **Select action type**: "Bolt Action"
   - ✅ Field updates
   - ✅ Can select "Other" and specify

3. **Enter serial numbers**:
   - ✅ Barrel serial + make
   - ✅ Frame serial + make
   - ✅ Receiver serial + make
   - ✅ All fields save correctly

### ✅ Endorsement Form Integration Test

1. **Visit**: http://nrapa.test/member/endorsements/create
2. **Go to Step 2** (Firearm Details)
   - ✅ Component loads
   - ✅ Can search and select calibre
   - ✅ Can search and select make/model
   - ✅ All SAPS 271 fields work
   - ✅ Can proceed to next step

3. **Submit endorsement**:
   - ✅ Data saves correctly
   - ✅ `firearm_calibre_id`, `firearm_make_id`, `firearm_model_id` are set
   - ✅ Override fields work if reference not found

### ✅ API Endpoints Test

1. **Test calibre suggest**:
   ```
   http://nrapa.test/api/calibres/suggest?query=6.5
   ```
   - ✅ Returns JSON array of calibres
   - ✅ Includes aliases in search

2. **Test make suggest**:
   ```
   http://nrapa.test/api/makes/suggest?query=Glock
   ```
   - ✅ Returns JSON array of makes

3. **Test calibre resolve**:
   ```
   http://nrapa.test/api/calibres/resolve?query=6.5%20Creedmoor
   ```
   - ✅ Returns full calibre data

### ✅ Admin Reference Page Test

1. **Visit**: http://nrapa.test/admin/firearm-reference
   - ✅ Page loads
   - ✅ Shows calibres tab with data
   - ✅ Shows makes tab with data
   - ✅ Can view reference data

### ✅ Error Handling Test

1. **Test with empty search**:
   - ✅ No errors, just empty results

2. **Test with invalid data**:
   - ✅ Component handles gracefully
   - ✅ Shows appropriate messages

## Common Issues & Fixes

### Issue: "Table doesn't exist"
**Fix**: Run migrations
```bash
php artisan migrate --force
```

### Issue: "No data imported"
**Fix**: Run import command
```bash
php artisan nrapa:import-firearm-reference
```

### Issue: "Component not found"
**Fix**: Clear caches
```bash
php artisan optimize:clear
php artisan view:clear
```

### Issue: "404 on component"
**Fix**: Check component name matches:
- Class: `App\Livewire\FirearmSearchPanel`
- Component: `firearm-search-panel`
- View: `livewire/firearm-search-panel.blade.php`

## Success Criteria

✅ All migrations run successfully  
✅ Reference data imported (100+ calibres, 100+ makes, 200+ models)  
✅ Component loads without errors  
✅ Search functionality works  
✅ Data saves correctly  
✅ API endpoints return data  
✅ Admin page displays reference data  

## Next Steps After Testing

1. Test creating an endorsement with the new component
2. Test editing an existing endorsement
3. Test the backfill migration with existing data
4. Verify SAPS 271 export view works
5. Test API endpoints from frontend
