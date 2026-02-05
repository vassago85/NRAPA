# Refresh Local Environment
# Run this script to clear caches and refresh your local setup

Write-Host "🔄 Refreshing local environment..." -ForegroundColor Cyan

# Navigate to project directory
Set-Location "c:\laragon\www\NRAPA"

# Clear all Laravel caches
Write-Host "`n📦 Clearing Laravel caches..." -ForegroundColor Yellow
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

Write-Host "✅ Caches cleared!" -ForegroundColor Green

# Optimize (optional - for production-like performance)
Write-Host "`n⚡ Optimizing..." -ForegroundColor Yellow
php artisan optimize:clear

Write-Host "`n✅ Local environment refreshed!" -ForegroundColor Green
Write-Host "`n💡 Tip: Refresh your browser (Ctrl+F5) to see the changes." -ForegroundColor Cyan
