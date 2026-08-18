#!/usr/bin/env bash
# =============================================================================
# Shop Genie — Laravel backend production deploy helper (VPS / non-cPanel).
#
# Usage (run from the repo root, as the deploy user):
#   bash deploy.sh            # first deploy + subsequent updates
#
# What it does:
#   1. composer install (no dev)
#   2. migrate + optimize
#   3. build the React storefront into backend/public/storefront (optional)
#
# Requirements: PHP 8.2+, Composer, Node.js 18+ (only if building React).
# =============================================================================
set -euo pipefail

cd "$(dirname "$0")"

echo "==> [1/6] Installing PHP dependencies (composer install --no-dev)"
composer install --no-dev --optimize-autoloader --no-interaction

echo "==> [2/6] Setting permissions"
chmod -R 775 storage bootstrap/cache
mkdir -p storage/app/private storage/app/public storage/framework/{cache,sessions,views} storage/logs

echo "==> [3/6] Linking public storage (uploads)"
php artisan storage:link || true

echo "==> [4/6] Running migrations"
php artisan migrate --force

echo "==> [5/6] Optimizing"
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> [6/6] Building React storefront"
if [ -f ../package.json ]; then
  (cd .. && npm ci --no-audit --no-fund && npm run build)
  rm -rf public/storefront
  mkdir -p public/storefront
  cp -R ../dist/* public/storefront/
  echo "    React build copied to public/storefront/"
else
  echo "    Skipped (no React package.json at repo root)."
fi

echo ""
echo "✅ Deploy complete."
echo "   Point your web server document root at: $(pwd)/public  (or the app root for shared hosting)"
echo "   Then run the queue worker:  php artisan queue:work --sleep=3 --tries=3"
