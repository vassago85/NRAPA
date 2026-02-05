@echo off
REM Local Deployment Script for NRAPA
REM Run this script to deploy all changes locally

echo === NRAPA Local Deployment ===
echo.

REM Find PHP executable
set PHP_PATH=
if exist "C:\laragon\bin\php\php-8.3\php.exe" set PHP_PATH=C:\laragon\bin\php\php-8.3\php.exe
if exist "C:\laragon\bin\php\php-8.2\php.exe" set PHP_PATH=C:\laragon\bin\php\php-8.2\php.exe
if exist "C:\laragon\bin\php\php-8.1\php.exe" set PHP_PATH=C:\laragon\bin\php\php-8.1\php.exe
if exist "C:\laragon\bin\php\php-8.0\php.exe" set PHP_PATH=C:\laragon\bin\php\php-8.0\php.exe

if "%PHP_PATH%"=="" (
    echo ERROR: PHP executable not found in Laragon paths.
    echo Please run this from Laragon Terminal or update the PHP path in this script.
    pause
    exit /b 1
)

echo Using PHP: %PHP_PATH%
echo.

REM Change to project directory
cd /d c:\laragon\www\NRAPA

REM Step 1: Run Migrations
echo Step 1: Running migrations...
"%PHP_PATH%" artisan migrate
if errorlevel 1 (
    echo ERROR: Migrations failed!
    pause
    exit /b 1
)
echo [OK] Migrations completed
echo.

REM Step 2: Update Document Types
echo Step 2: Updating document types...
"%PHP_PATH%" artisan db:seed --class=MembershipConfigurationSeeder
if errorlevel 1 (
    echo ERROR: Seeder failed!
    pause
    exit /b 1
)
echo [OK] Document types updated
echo.

REM Step 3: Clear Caches
echo Step 3: Clearing caches...
"%PHP_PATH%" artisan optimize:clear
"%PHP_PATH%" artisan config:clear
"%PHP_PATH%" artisan route:clear
"%PHP_PATH%" artisan view:clear
"%PHP_PATH%" artisan cache:clear
echo [OK] Caches cleared
echo.

REM Step 4: Rebuild Caches
echo Step 4: Rebuilding caches...
"%PHP_PATH%" artisan config:cache
"%PHP_PATH%" artisan route:cache
"%PHP_PATH%" artisan view:cache
echo [OK] Caches rebuilt
echo.

echo === Deployment Complete ===
echo.
echo Next steps:
echo 1. Visit http://nrapa.test/member/documents to verify document types
echo 2. Visit http://nrapa.test/member/endorsements/create to test calibre requests
echo 3. Check member dashboard for rejected documents feature
echo.
pause
