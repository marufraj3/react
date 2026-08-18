@echo off
REM ============================================================
REM  Shop Genie - start the Laravel backend locally
REM  Run setup-local.bat once first.
REM ============================================================
cd /d "%~dp0"

echo.
echo   Starting Laravel backend on http://localhost:8000 ...
echo   Press Ctrl+C to stop.
echo.
php artisan serve --host=0.0.0.0 --port=8000
