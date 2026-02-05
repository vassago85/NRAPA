@echo off
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

echo Running migrations...
%PHP_PATH% artisan migrate --force
echo.

echo Importing reference data...
%PHP_PATH% artisan nrapa:import-firearm-reference
echo.

echo Clearing caches...
%PHP_PATH% artisan optimize:clear
echo.

echo Done!
pause
