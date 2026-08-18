@echo off
setlocal EnableExtensions
REM ============================================================
REM  Shop Genie - Windows one-click local backend setup
REM  (No Docker needed - requires Laragon or XAMPP installed)
REM
REM  How to use:
REM   1. Install Laragon (https://laragon.org) or XAMPP
REM   2. Start MySQL (Laragon: Start All / XAMPP: start MySQL)
REM   3. Right-click Laragon tray icon -> Terminal, then run this file
REM      OR double-click this file if PHP + Composer are in PATH
REM ============================================================
cd /d "%~dp0"

echo.
echo ============================================================
echo   Shop Genie backend - local setup
echo ============================================================

where php >nul 2>nul
if errorlevel 1 (
    echo.
    echo   [ERROR] PHP not found in PATH.
    echo   Please open this from Laragon's Terminal, or add PHP to PATH.
    echo.
    pause
    exit /b 1
)

where composer >nul 2>nul
if errorlevel 1 (
    echo.
    echo   [ERROR] Composer not found in PATH.
    echo   Install it from https://getcomposer.org  (Laragon includes it).
    echo.
    pause
    exit /b 1
)

echo.
echo   [1/5] Installing PHP packages (composer install)...
composer install --no-interaction --optimize-autoloader

echo.
echo   [2/5] Creating .env from .env.example (if missing)...
if not exist .env copy .env.example .env >nul

echo.
echo   [3/5] Generating application key...
php artisan key:generate --force

echo.
echo   [4/5] Creating database 'shopgenie' if it does not exist...
where mysql >nul 2>nul && (
    mysql -u root -e "CREATE DATABASE IF NOT EXISTS shopgenie CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>nul
    echo        (if this failed, create the DB manually in HeidiSQL/phpMyAdmin)
) || (
    echo        mysql client not in PATH - create DB 'shopgenie' manually in HeidiSQL/phpMyAdmin
)

echo.
echo   [5/5] Running migrations + seed data...
php artisan migrate --force
php artisan db:seed --force

echo.
echo ============================================================
echo   DONE!
echo   Start the server with:  start-local.bat
echo   Storefront API:  http://localhost:8000/api/v1/storefront/bootstrap
echo   Admin panel:     http://localhost:8000/admin
echo   Admin login:     admin@gmail.com / 123456
echo ============================================================
echo.
pause
