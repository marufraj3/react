#!/usr/bin/env bash
# =============================================================================
# Shop Genie — container entrypoint for local development/testing.
#   1. Wait for MySQL
#   2. composer install (first run only)
#   3. key:generate + migrate --seed
#   4. serve on :80
# =============================================================================
set -euo pipefail

echo "==> Waiting for MySQL at ${DB_HOST}:${DB_PORT}..."
until mysqladmin ping -h"${DB_HOST}" -P"${DB_PORT}" -u"${DB_USERNAME}" -p"${DB_PASSWORD}" --silent; do
  sleep 2
done
echo "==> MySQL is up."

cd /var/www/html

if [ ! -f .env ]; then
  echo "==> Creating .env from .env.example"
  cp .env.example .env
fi

if [ ! -d vendor ]; then
  echo "==> Running composer install (first run)..."
  composer install --no-interaction --optimize-autoloader
fi

echo "==> Setting application key"
php artisan key:generate --force --no-interaction || true

echo "==> Running migrations + seed"
php artisan migrate --force --no-interaction
php artisan db:seed --force --no-interaction || true

echo "==> Starting Laravel on :80"
exec php artisan serve --host=0.0.0.0 --port=80
