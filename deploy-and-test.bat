@echo off
echo ========================================
echo Firearm Reference System - Deploy & Test
echo ========================================
echo.

cd /d "%~dp0"

REM Try to find PHP in Laragon
set PHP_PATH=
if exist "C:\laragon\bin\php\php-8.3\php.exe" set PHP_PATH=C:\laragon\bin\php\php-8.3\php.exe
if exist "C:\laragon\bin\php\php-8.2\php.exe" set PHP_PATH=C:\laragon\bin\php\php-8.2\php.exe
if exist "C:\laragon\bin\php\php-8.1\php.exe" set PHP_PATH=C:\laragon\bin\php\php-8.1\php.exe
if exist "C:\laragon\bin\php\php-8.0\php.exe" set PHP_PATH=C:\laragon\bin\php\php-8.0\php.exe

REM If not found, try php command
if "%PHP_PATH%"=="" (
    where php >nul 2>&1
    if %errorlevel%==0 (
        set PHP_PATH=php
    ) else (
        echo ERROR: PHP not found!
        echo Please run this script from Laragon terminal.
        pause
        exit /b 1
    )
)

echo Using PHP: %PHP_PATH%
echo.

echo [1/5] Running migrations...
%PHP_PATH% artisan migrate --force
if %errorlevel% neq 0 (
    echo ERROR: Migration failed!
    pause
    exit /b %errorlevel%
)
echo.

echo [2/5] Importing reference data...
%PHP_PATH% artisan nrapa:import-firearm-reference
if %errorlevel% neq 0 (
    echo ERROR: Import failed!
    pause
    exit /b %errorlevel%
)
echo.

echo [3/5] Clearing all caches...
%PHP_PATH% artisan optimize:clear
%PHP_PATH% artisan view:clear
%PHP_PATH% artisan config:clear
%PHP_PATH% artisan route:clear
echo.

echo [4/5] Verifying installation...
%PHP_PATH% artisan tinker --execute="echo 'Calibres: ' . \App\Models\FirearmCalibre::count() . PHP_EOL; echo 'Makes: ' . \App\Models\FirearmMake::count() . PHP_EOL; echo 'Models: ' . \App\Models\FirearmModel::count() . PHP_EOL; echo 'Aliases: ' . \App\Models\FirearmCalibreAlias::count() . PHP_EOL;"
echo.

echo [5/5] Testing component discovery...
%PHP_PATH% artisan livewire:list | findstr "firearm-search-panel"
if %errorlevel%==0 (
    echo Component found in Livewire registry!
) else (
    echo WARNING: Component not found in registry. This may be normal.
)
echo.

echo ========================================
echo Deployment Complete!
echo ========================================
echo.
echo Test URLs:
echo 1. Component Test: http://nrapa.test/test-firearm-panel
echo 2. Endorsement Form: http://nrapa.test/member/endorsements/create
echo 3. Admin Reference: http://nrapa.test/admin/firearm-reference
echo 4. API Test: http://nrapa.test/api/calibres/suggest?query=6.5
echo.
echo Next: Open browser and test the component!
echo.
pause
