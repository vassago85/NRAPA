# Quick Setup Instructions

## Option 1: Run Batch Script (Easiest)

1. **Open Laragon Terminal**:
   - Right-click Laragon icon → Terminal
   - OR Open Command Prompt/PowerShell in the NRAPA folder

2. **Run the batch script**:
   ```bash
   cd c:\laragon\www\NRAPA
   setup-firearm-reference.bat
   ```

   OR double-click `setup-firearm-reference.bat` in Windows Explorer

## Option 2: Manual Commands

If the batch script doesn't work, run these commands manually in Laragon terminal:

```bash
cd c:\laragon\www\NRAPA

# 1. Run migrations
php artisan migrate --force

# 2. Import reference data
php artisan nrapa:import-firearm-reference

# 3. Clear all caches
php artisan optimize:clear
php artisan view:clear
php artisan config:clear
php artisan route:clear

# 4. Verify (optional)
php artisan tinker
# Then in tinker:
\App\Models\FirearmCalibre::count()
\App\Models\FirearmMake::count()
\App\Models\FirearmModel::count()
```

## Expected Output

After running, you should see:
- ✅ Migrations completed successfully
- ✅ Calibres imported: 100+
- ✅ Makes imported: 100+
- ✅ Models imported: 200+
- ✅ Aliases imported: 70+

## Troubleshooting

### PHP Not Found
If you get "PHP not found":
1. Make sure you're running from Laragon terminal (it has PHP in PATH)
2. OR use full path: `C:\laragon\bin\php\php-8.x\php.exe artisan migrate`

### Migration Errors
If migrations fail:
- Check database connection in `.env`
- Ensure database exists
- Check `storage/logs/laravel.log` for details

### Import Errors
If import fails:
- Check that CSV files exist in `resources/data/`
- Verify CSV format matches expected headers
- Check `storage/logs/laravel.log` for details

## Test After Setup

1. **Test Component**: http://nrapa.test/test-firearm-panel
2. **Test Endorsement Form**: http://nrapa.test/member/endorsements/create
3. **Test Admin Page**: http://nrapa.test/admin/firearm-reference
4. **Test API**: http://nrapa.test/api/calibres/suggest?query=6.5
