# 🚀 Deployment Guide — Shop Genie (React storefront + Laravel backend)

This project ships as two parts in one repo:

| Part | Path | Tech |
|---|---|---|
| **Storefront** | `/` (repo root) | React 19 + Vite |
| **Backend + Admin** | `/backend` | Laravel 12 (PHP 8.2+) |

The React storefront talks to the Laravel app over its REST API
(`/api/v1/storefront/*`). The Laravel app also hosts the **Blade admin panel**
(`/admin`) and the payment-gateway callbacks.

---

## 1. Prerequisites

- PHP **8.2+** with extensions: `bcmath ctype fileinfo json mbstring openssl pdo
  pdo_mysql tokenizer xml curl gd zip`
- MySQL 5.7+ / MariaDB 10.4+
- Composer 2
- Node.js 18+ (only to build the React storefront)
- A domain (or subdomain) with HTTPS

> ⚠️ `backend/app/Http/Kernel.php` is an ionCube-encoded leftover. Laravel 12
> does **not** use it (`bootstrap/app.php` handles everything). It is safe to
> delete it, and on hosts without the ionCube loader it must be removed so PHP
> doesn't error when the file is scanned.

---

## 2. Shared hosting (cPanel) — recommended for Bangladesh

The app is already laid out for shared hosting: `index.php` and `.htaccess`
live at the app root, so the **document root can be the app root**
(`public_html/`).

### 2.1 Upload
1. Upload the **contents of `backend/`** into `public_html/` (or a subfolder).
2. **Exclude** `vendor/`, `node_modules/`, `storage/logs/*`.
3. Restore your `public/uploads/` folder (product images) if you have a backup.

### 2.2 Install dependencies
On cPanel, open **Terminal** (or ask support to enable shell) and run:
```bash
cd ~/public_html
composer install --no-dev --optimize-autoloader
```
If shell is unavailable, run `composer install` **locally**, then upload the
generated `vendor/` folder together with the app.

### 2.3 Environment
```bash
cp .env.production.example .env
php artisan key:generate
```
Edit `.env` and set at minimum:
```
APP_NAME="Shop Genie"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com       # where the Laravel app lives
STORE_URL=https://shop.your-domain.com # where the React storefront lives
DB_*                                  # your MySQL credentials
QUEUE_CONNECTION=database
```

### 2.4 Database
Two options:
- **Import the SQL dump** (fastest, matches the original app):
  ```bash
  mysql -u USER -p DBNAME < creativedesignbd_myshop1.sql
  ```
- **Or run migrations** (clean install):
  ```bash
  php artisan migrate --seed
  ```

### 2.5 Storage + permissions
```bash
php artisan storage:link
mkdir -p storage/app/private storage/app/public storage/framework/{cache,sessions,views} storage/logs
chmod -R 775 storage bootstrap/cache
```

### 2.6 Build & deploy the React storefront
The storefront can live on the **same domain** (any subfolder, e.g.
`/storefront`) or a **separate subdomain**. Build it with the API URL baked in:

```bash
# from the repo root
npm ci
VITE_API_URL=https://your-domain.com npm run build
```
Upload the `dist/` contents to wherever you want to serve the storefront
(subdomain web root, or `public_html/storefront/`).

### 2.7 Cron job (cPanel → Cron Jobs)
Add a cron entry to run Laravel's scheduler every minute:
```
* * * * * cd /home/USER/public_html && /usr/local/bin/php artisan schedule:run >> /dev/null 2>&1
```
The scheduler runs the courier status check and vendor wallet init commands.

### 2.8 Queue worker (SMS, Facebook CAPI, fraud check)
On shared hosting a long-running worker usually isn't allowed. Two options:
- Keep `QUEUE_CONNECTION=sync` (works — the heavy calls use
  `register_shutdown_function`, so they don't block the customer), **or**
- Use the cron scheduler to drain the queue:
  ```
  * * * * * cd /home/USER/public_html && /usr/local/bin/php artisan queue:work --stop-when-empty --tries=3 >> /dev/null 2>&1
  ```

---

## 3. VPS (Nginx/Apache + PHP-FPM)

```bash
cd /var/www
git clone https://github.com/marufraj3/react.git shopgenie
cd shopgenie/backend
cp .env.production.example .env && php artisan key:generate
composer install --no-dev --optimize-autoloader
php artisan migrate            # or import the SQL dump
php artisan storage:link
```

### Nginx (document root = backend root for this shared-hosting layout)
```nginx
server {
    listen 80;
    server_name api.your-domain.com;
    root /var/www/shopgenie/backend;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
    location ~ /\.(?!well-known).* { deny all; }
    # Protect sensitive files (important for the shared-hosting layout!)
    location ~* \.(env|log|sql|md|lock|json|sh)$ { deny all; }
    location ~ /(storage|vendor|app|config|routes|database|resources)/ { deny all; }
}
```
> ⚠️ Because the document root is the app root, **block direct web access to
> `storage/`, `vendor/`, `app/`, `routes/`, `database/`, `resources/` and any
> `.env`/`.sql` files.** The `location` rules above do this. On cPanel the
> `backend/.htaccess` plus `.user.ini`/`php.ini` already guard most of it, but
> verify `.env` is not downloadable.

### Queue worker + scheduler (VPS)
```bash
sudo systemctl edit shopgenie-queue
# ExecStart=/usr/bin/php /var/www/shopgenie/backend/artisan queue:work --sleep=3 --tries=3
sudo systemctl enable --now shopgenie-queue

# cron (root)
* * * * * cd /var/www/shopgenie/backend && php artisan schedule:run >> /dev/null 2>&1
```

---

## 4. Connect the two apps

1. In the **React** build, set `VITE_API_URL=https://your-laravel-domain`.
2. In the **Laravel** `.env`, set `STORE_URL=https://your-react-domain` so
   payment callbacks return to the React storefront.

| Setting | Where | Purpose |
|---|---|---|
| `VITE_API_URL` | React build env | Base URL of the Laravel API |
| `STORE_URL` | Laravel `.env` | Payment callback return target (React app) |
| `APP_URL` | Laravel `.env` | Laravel's own public URL (callback URLs, assets) |

---

## 5. Payment gateway callback URLs

Configure these in each gateway's dashboard (and credentials in
**Admin → API Integration → Payment Gateway**):

| Gateway | Callback / Webhook URL |
|---|---|
| bKash | `{APP_URL}/bkash/checkout-url/callback` |
| UddoktaPay | Verify `{APP_URL}/uddoktapay/verify`, IPN `{APP_URL}/uddoktapay/ipn` |
| aamarPay | Success `{APP_URL}/aamarpay/success`, Fail `{APP_URL}/aamarpay/fail` |
| ShurjoPay | `{APP_URL}/payment-success`, `{APP_URL}/payment-cancel` |

---

## 6. Production checklist

- [ ] `.env` → `APP_DEBUG=false`, `APP_KEY` set, DB + `STORE_URL` set
- [ ] `php artisan migrate` (or SQL import) done, `storage:link` done
- [ ] React built with the correct `VITE_API_URL`
- [ ] HTTPS enabled on both domains
- [ ] Cron scheduler registered
- [ ] Queue worker running (or `QUEUE_CONNECTION=sync`)
- [ ] `storage/app/private` writable (digital downloads)
- [ ] Payment credentials + callback URLs configured
- [ ] Gemini API key set (admin AI assistant)
- [ ] Test: COD order → track → online payment → callback → download

---

## 7. Rollback / updates

```bash
cd /path/to/backend
git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
# rebuild + redeploy the React dist/ with the same VITE_API_URL
```
