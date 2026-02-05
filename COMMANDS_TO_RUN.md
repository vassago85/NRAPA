# Commands to Run Locally

Since PHP isn't available in this environment, please run these commands in **Laragon Terminal**:

## Step-by-Step Commands

```bash
# Navigate to project directory
cd c:\laragon\www\NRAPA

# 1. Run migrations (creates tables)
php artisan migrate --force

# 2. Import reference data (populates calibres, makes, models)
php artisan nrapa:import-firearm-reference

# 3. Clear all caches
php artisan optimize:clear
php artisan view:clear
php artisan config:clear
php artisan route:clear

# 4. Verify installation (optional)
php artisan tinker
# Then type:
\App\Models\FirearmCalibre::count()
\App\Models\FirearmMake::count()
\App\Models\FirearmModel::count()
# Should show: 100+, 100+, 200+
```

## OR Use the Batch Script

Double-click `setup-firearm-reference.bat` in Windows Explorer, or run:

```bash
cd c:\laragon\www\NRAPA
setup-firearm-reference.bat
```

## Expected Results

After running, you should see:
- ✅ **Migrations**: All 5 new tables created
- ✅ **Calibres**: ~100+ imported
- ✅ **Aliases**: ~70+ imported  
- ✅ **Makes**: ~100+ imported
- ✅ **Models**: ~200+ imported

## Test URLs

After setup completes, test these:

1. **Component Test Page**: http://nrapa.test/test-firearm-panel
2. **Endorsement Form**: http://nrapa.test/member/endorsements/create (go to Step 2)
3. **Admin Reference Page**: http://nrapa.test/admin/firearm-reference
4. **API Test**: http://nrapa.test/api/calibres/suggest?query=6.5

## If You Get Errors

Check `storage/logs/laravel.log` for detailed error messages.

Common issues:
- **Database connection**: Check `.env` file
- **CSV files missing**: Ensure files exist in `resources/data/`
- **Permissions**: Ensure Laravel can write to `storage/` and `bootstrap/cache/`
