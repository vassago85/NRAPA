@echo off
cd /d "%~dp0"
php artisan route:clear
php artisan optimize:clear
echo Route cache cleared successfully!
