# Local Deployment Script for NRAPA
# Run this script to deploy all changes locally

Write-Host "=== NRAPA Local Deployment ===" -ForegroundColor Cyan
Write-Host ""

# Find PHP executable (Laragon versioned folders: php-8.3.28-Win32-..., php-8.4.12-nts-..., etc.)
$phpPath = $null
$phpDir = "C:\laragon\bin\php"
if (Test-Path $phpDir) {
    $found = Get-ChildItem -Path $phpDir -Filter "php.exe" -Recurse -ErrorAction SilentlyContinue | Select-Object -First 1
    if ($found) { $phpPath = $found.FullName }
}
$possiblePaths = @(
    "C:\laragon\bin\php\php-8.3\php.exe",
    "C:\laragon\bin\php\php-8.2\php.exe",
    "C:\laragon\bin\php\php-8.1\php.exe",
    "C:\laragon\bin\php\php-8.0\php.exe"
)
if (-not $phpPath) {
    foreach ($path in $possiblePaths) {
        if (Test-Path $path) { $phpPath = $path; break }
    }
}

if (-not $phpPath) {
    Write-Host "ERROR: PHP executable not found in Laragon paths." -ForegroundColor Red
    Write-Host "Please run this from Laragon Terminal or update the PHP path in this script." -ForegroundColor Yellow
    exit 1
}

Write-Host "Using PHP: $phpPath" -ForegroundColor Green
Write-Host ""

# Change to project directory
$projectDir = "c:\laragon\www\NRAPA"
Set-Location $projectDir

# Step 0: Ensure .env exists and has key
if (-not (Test-Path ".env")) {
    Write-Host "Step 0: Creating .env from .env.example..." -ForegroundColor Yellow
    Copy-Item ".env.example" ".env"
    & $phpPath artisan key:generate --no-interaction
    Write-Host "✓ .env created and key generated" -ForegroundColor Green
    Write-Host ""
}

# Step 1: Run Migrations
Write-Host "Step 1: Running migrations..." -ForegroundColor Yellow
& $phpPath artisan migrate
if ($LASTEXITCODE -ne 0) {
    Write-Host "ERROR: Migrations failed!" -ForegroundColor Red
    exit 1
}
Write-Host "✓ Migrations completed" -ForegroundColor Green
Write-Host ""

# Step 2: Update Document Types
Write-Host "Step 2: Updating document types..." -ForegroundColor Yellow
& $phpPath artisan db:seed --class=MembershipConfigurationSeeder
if ($LASTEXITCODE -ne 0) {
    Write-Host "ERROR: Seeder failed!" -ForegroundColor Red
    exit 1
}
Write-Host "✓ Document types updated" -ForegroundColor Green
Write-Host ""

# Step 3: Clear Caches
Write-Host "Step 3: Clearing caches..." -ForegroundColor Yellow
& $phpPath artisan optimize:clear
& $phpPath artisan config:clear
& $phpPath artisan route:clear
& $phpPath artisan view:clear
& $phpPath artisan cache:clear
Write-Host "✓ Caches cleared" -ForegroundColor Green
Write-Host ""

# Step 4: Rebuild Caches
Write-Host "Step 4: Rebuilding caches..." -ForegroundColor Yellow
& $phpPath artisan config:cache
& $phpPath artisan route:cache
& $phpPath artisan view:cache
Write-Host "✓ Caches rebuilt" -ForegroundColor Green
Write-Host ""

# Step 5: Verify
Write-Host "Step 5: Verifying deployment..." -ForegroundColor Yellow
Write-Host "Checking document types..." -ForegroundColor Gray
$tinkerCode = 'echo App\Models\DocumentType::active()->count() . '' active document types'';'
$result = & $phpPath artisan tinker --execute=$tinkerCode
Write-Host $result -ForegroundColor Cyan

Write-Host ""
Write-Host "=== Deployment Complete ===" -ForegroundColor Green
Write-Host ""

# Step 6: Start development server (if Laragon isn't serving nrapa.test)
$startServer = $env:NRAPA_START_SERVER -eq "1"
if ($startServer) {
    Write-Host "Step 6: Starting development server..." -ForegroundColor Yellow
    Start-Process -FilePath $phpPath -ArgumentList "artisan","serve" -WorkingDirectory $projectDir -NoNewWindow
    Write-Host "✓ Server starting at http://127.0.0.1:8000" -ForegroundColor Green
} else {
    Write-Host "To start the dev server run: php artisan serve" -ForegroundColor Gray
    Write-Host "Or use Laragon: ensure NRAPA is in www and open http://nrapa.test" -ForegroundColor Gray
}

Write-Host ""
Write-Host "Next steps:" -ForegroundColor Yellow
Write-Host "1. Visit http://nrapa.test or http://127.0.0.1:8000" -ForegroundColor White
Write-Host "2. Visit http://nrapa.test/member/documents to verify document types" -ForegroundColor White
Write-Host "3. Visit http://nrapa.test/member/endorsements/create to test calibre requests" -ForegroundColor White
Write-Host ""
