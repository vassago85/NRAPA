@echo off
echo ========================================
echo Firearm Reference System Setup
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
        echo Please run this script from Laragon terminal or ensure PHP is in PATH.
        pause
        exit /b 1
    )
)

echo Using PHP: %PHP_PATH%
echo.

echo [1/4] Running migrations...
%PHP_PATH% artisan migrate --force
if %errorlevel% neq 0 (
    echo ERROR: Migration failed!
    pause
    exit /b %errorlevel%
)
echo.

echo [2/4] Importing reference data...
%PHP_PATH% artisan nrapa:import-firearm-reference
if %errorlevel% neq 0 (
    echo ERROR: Import failed!
    pause
    exit /b %errorlevel%
)
echo.

echo [3/4] Clearing caches...
%PHP_PATH% artisan optimize:clear
%PHP_PATH% artisan view:clear
%PHP_PATH% artisan config:clear
%PHP_PATH% artisan route:clear
echo.

echo [4/4] Verifying installation...
%PHP_PATH% artisan tinker --execute="echo 'Calibres: ' . \App\Models\FirearmCalibre::count() . PHP_EOL; echo 'Makes: ' . \App\Models\FirearmMake::count() . PHP_EOL; echo 'Models: ' . \App\Models\FirearmModel::count() . PHP_EOL;"
echo.

echo ========================================
echo Setup Complete!
echo ========================================
echo.
echo Next steps:
echo 1. Test the component at: http://nrapa.test/member/endorsements/create
echo 2. Check admin page: http://nrapa.test/admin/firearm-reference
echo 3. Test API: http://nrapa.test/api/calibres/suggest?query=6.5
echo 4. Test component directly: http://nrapa.test/test-firearm-panel
echo.
pause
