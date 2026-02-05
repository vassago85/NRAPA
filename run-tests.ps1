# Test Runner Script for NRAPA
# Run this script to execute all tests

Write-Host "=== Running NRAPA Tests ===" -ForegroundColor Cyan
Write-Host ""

# Find PHP executable
$phpPath = $null
$possiblePaths = @(
    "C:\laragon\bin\php\php-8.3\php.exe",
    "C:\laragon\bin\php\php-8.2\php.exe",
    "C:\laragon\bin\php\php-8.1\php.exe",
    "C:\laragon\bin\php\php-8.0\php.exe"
)

foreach ($path in $possiblePaths) {
    if (Test-Path $path) {
        $phpPath = $path
        break
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

# Run tests using Pest
Write-Host "Running tests with Pest..." -ForegroundColor Yellow
Write-Host ""

& $phpPath vendor\bin\pest.bat

$exitCode = $LASTEXITCODE

Write-Host ""
if ($exitCode -eq 0) {
    Write-Host "=== All Tests Passed ===" -ForegroundColor Green
} else {
    Write-Host "=== Some Tests Failed ===" -ForegroundColor Red
}

exit $exitCode
