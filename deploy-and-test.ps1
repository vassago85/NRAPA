# Firearm Reference System - Deploy & Test (PowerShell)
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Firearm Reference System - Deploy & Test" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Change to script directory
Set-Location $PSScriptRoot

# Try to find PHP in Laragon
$phpPath = $null
$laragonPaths = @(
    "C:\laragon\bin\php\php-8.3\php.exe",
    "C:\laragon\bin\php\php-8.2\php.exe",
    "C:\laragon\bin\php\php-8.1\php.exe",
    "C:\laragon\bin\php\php-8.0\php.exe"
)

foreach ($path in $laragonPaths) {
    if (Test-Path $path) {
        $phpPath = $path
        break
    }
}

# If not found, try php command
if (-not $phpPath) {
    $phpCmd = Get-Command php -ErrorAction SilentlyContinue
    if ($phpCmd) {
        $phpPath = "php"
    } else {
        Write-Host "ERROR: PHP not found!" -ForegroundColor Red
        Write-Host "Please run this script from Laragon terminal." -ForegroundColor Yellow
        Read-Host "Press Enter to exit"
        exit 1
    }
}

Write-Host "Using PHP: $phpPath" -ForegroundColor Green
Write-Host ""

# Step 1: Run migrations
Write-Host "[1/5] Running migrations..." -ForegroundColor Yellow
& $phpPath artisan migrate --force
if ($LASTEXITCODE -ne 0) {
    Write-Host "ERROR: Migration failed!" -ForegroundColor Red
    Read-Host "Press Enter to exit"
    exit $LASTEXITCODE
}
Write-Host ""

# Step 2: Import reference data
Write-Host "[2/5] Importing reference data..." -ForegroundColor Yellow
& $phpPath artisan nrapa:import-firearm-reference
if ($LASTEXITCODE -ne 0) {
    Write-Host "ERROR: Import failed!" -ForegroundColor Red
    Read-Host "Press Enter to exit"
    exit $LASTEXITCODE
}
Write-Host ""

# Step 3: Clear caches
Write-Host "[3/5] Clearing all caches..." -ForegroundColor Yellow
& $phpPath artisan optimize:clear
& $phpPath artisan view:clear
& $phpPath artisan config:clear
& $phpPath artisan route:clear
Write-Host ""

# Step 4: Verify installation
Write-Host "[4/5] Verifying installation..." -ForegroundColor Yellow
$tinkerScript = @"
echo 'Calibres: ' . \App\Models\FirearmCalibre::count() . PHP_EOL;
echo 'Makes: ' . \App\Models\FirearmMake::count() . PHP_EOL;
echo 'Models: ' . \App\Models\FirearmModel::count() . PHP_EOL;
echo 'Aliases: ' . \App\Models\FirearmCalibreAlias::count() . PHP_EOL;
"@
$tinkerScript | & $phpPath artisan tinker --execute
Write-Host ""

# Step 5: Test component discovery
Write-Host "[5/5] Testing component discovery..." -ForegroundColor Yellow
$componentCheck = & $phpPath artisan livewire:list 2>&1 | Select-String "firearm-search-panel"
if ($componentCheck) {
    Write-Host "Component found in Livewire registry!" -ForegroundColor Green
} else {
    Write-Host "WARNING: Component not found in registry. This may be normal." -ForegroundColor Yellow
}
Write-Host ""

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Deployment Complete!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Test URLs:" -ForegroundColor Yellow
Write-Host "1. Component Test: http://nrapa.test/test-firearm-panel" -ForegroundColor White
Write-Host "2. Endorsement Form: http://nrapa.test/member/endorsements/create" -ForegroundColor White
Write-Host "3. Admin Reference: http://nrapa.test/admin/firearm-reference" -ForegroundColor White
Write-Host "4. API Test: http://nrapa.test/api/calibres/suggest?query=6.5" -ForegroundColor White
Write-Host ""
Write-Host "Next: Open browser and test the component!" -ForegroundColor Green
Write-Host ""
Read-Host "Press Enter to exit"
