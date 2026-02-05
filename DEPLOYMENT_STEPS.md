# Deployment Steps - Firearm Reference System

## Quick Deploy Script

**Double-click**: `deploy-and-test.bat`

OR run manually:

```bash
cd c:\laragon\www\NRAPA
php artisan migrate --force
php artisan nrapa:import-firearm-reference
php artisan optimize:clear
php artisan view:clear
php artisan config:clear
php artisan route:clear
```

## Step-by-Step Deployment

### 1. Run Migrations ✅

```bash
php artisan migrate --force
```

**Creates:**
- `firearm_calibres` table
- `firearm_calibre_aliases` table
- `firearm_makes` table
- `firearm_models` table
- Adds reference fields to `user_firearms` and `endorsement_firearms`

### 2. Import Reference Data ✅

```bash
php artisan nrapa:import-firearm-reference
```

**Imports:**
- ~100+ calibres (6mm GT, 22 Creedmoor, 6.5 Creedmoor, 300 PRC, 338 Lapua, etc.)
- ~70+ calibre aliases (6GT, 9mm, .308 Win, etc.)
- ~100+ firearm makes (Glock, Howa, Tikka, CZ, Bergara, etc.)
- ~200+ firearm models (G17, T3x, 1500, etc.)

### 3. Clear Caches ✅

```bash
php artisan optimize:clear
php artisan view:clear
php artisan config:clear
php artisan route:clear
```

### 4. Verify Installation ✅

```bash
php artisan tinker
```

```php
\App\Models\FirearmCalibre::count()      // Should be 100+
\App\Models\FirearmMake::count()        // Should be 100+
\App\Models\FirearmModel::count()       // Should be 200+
\App\Models\FirearmCalibreAlias::count() // Should be 70+
```

## Testing URLs

After deployment, test these:

1. **Component Test**: http://nrapa.test/test-firearm-panel
   - Should load without errors
   - Should show search inputs

2. **Endorsement Form**: http://nrapa.test/member/endorsements/create
   - Go to Step 2
   - Component should load
   - Can search calibres/makes/models

3. **Admin Reference**: http://nrapa.test/admin/firearm-reference
   - Should show calibres and makes tabs
   - Should display imported data

4. **API Test**: http://nrapa.test/api/calibres/suggest?query=6.5
   - Should return JSON with calibre suggestions

## Expected Results

✅ All 5 migrations run successfully  
✅ Reference data imported (100+ calibres, 100+ makes, 200+ models)  
✅ Component loads without 404 errors  
✅ Search functionality works  
✅ No database errors  
✅ API endpoints return data  

## Troubleshooting

### "Table doesn't exist" Error
**Fix**: Run migrations
```bash
php artisan migrate --force
```

### "No data" in component
**Fix**: Run import
```bash
php artisan nrapa:import-firearm-reference
```

### "404" Error
**Fix**: Clear caches
```bash
php artisan optimize:clear
php artisan view:clear
```

### Component not found
**Fix**: Check component is registered
```bash
php artisan livewire:list | findstr firearm
```

## Files Created

- ✅ 5 migrations
- ✅ 4 models (FirearmCalibre, FirearmCalibreAlias, FirearmMake, FirearmModel)
- ✅ 4 CSV seed files
- ✅ Import command
- ✅ Livewire component + view
- ✅ API controller
- ✅ Admin management page
- ✅ SAPS 271 export view
- ✅ Tests

## Next Steps

1. Test component functionality
2. Test endorsement creation with new component
3. Test API endpoints
4. Verify data saves correctly
5. Test admin reference page
