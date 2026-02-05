@echo off
echo 🔄 Refreshing local environment...

cd c:\laragon\www\NRAPA

echo.
echo 📦 Clearing Laravel caches...
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

echo ✅ Caches cleared!

echo.
echo ⚡ Optimizing...
php artisan optimize:clear

echo.
echo ✅ Local environment refreshed!
echo.
echo 💡 Tip: Refresh your browser (Ctrl+F5) to see the changes.
pause
