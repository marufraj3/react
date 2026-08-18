# 🧪 Testing & Verification — Shop Genie

> 💻 **আপনার PC-তে localhost-এ test করার step-by-step বাংলা গাইড**
> দেখুন → **`../LOCAL_SETUP.md`** (Docker + Manual দুটো উপায়ই আছে)।

Two ways to verify the full stack before (and after) going live:

1. **Local Docker stack** — one command, no PHP/MySQL install needed.
2. **API smoke test** — scripted end-to-end check of every storefront endpoint.

---

## 1. Local Docker stack (recommended)

Requires only **Docker** (with Compose).

```bash
cd backend
docker compose up -d --build
```

What happens automatically on first boot (see `docker/entrypoint.sh`):
- starts **MySQL 8** (db `shopgenie`, user `shopgenie` / `secret`)
- `composer install`
- `php artisan key:generate`
- `php artisan migrate --seed` (includes the new **DemoDataSeeder** → settings,
  contacts, shipping charges, categories, products, banner, coupon)

Then:
| URL | What |
|---|---|
| http://localhost:8000 | Laravel app (API + Blade admin) |
| http://localhost:8000/admin | Admin panel |
| http://localhost:8000/api/v1/storefront/bootstrap | Storefront API |

Stop / reset:
```bash
docker compose down          # stop (keep DB volume)
docker compose down -v       # stop + wipe database
```

### Point the React storefront at it
```bash
# from the repo root
VITE_API_URL=http://localhost:8000 VITE_BACKEND_PROXY=http://localhost:8000 npm run dev
```
Now `npm run dev` proxies `/api` → Laravel, so the storefront runs fully
against the PHP backend (login, orders, payments, downloads, refunds).

---

## 2. API smoke test (scripted end-to-end)

```bash
cd backend
bash smoke-test.sh                          # against localhost:8000
bash smoke-test.sh https://api.example.com  # against any deployed server
```

It exercises, in order:

| # | Endpoint | Expected |
|---|---|---|
| 1 | `GET /bootstrap` | 200 + products |
| 2 | `GET /products` | 200 |
| 3 | `GET /search?q=` | 200 |
| 4 | `GET /products/{id}` | 200 |
| 5 | `GET /blogs` | 200 |
| 6 | `POST /coupons/apply` | 200 |
| 7 | `POST /auth/register` | 201 + token |
| 8 | `GET /auth/me` | 200 |
| 9 | `POST /orders` (COD) | 201 + order_id |
| 10 | `GET /orders/track/{id}` | 200 |
| 11 | `GET /auth/orders` | 200 |
| 12 | `POST /auth/profile` | 200 |
| 13 | `POST /auth/password` | 200 |
| 14 | `POST /refunds` | 201 |
| 15 | `GET /auth/refunds` | 200 |
| 16 | `POST /complaints` | 201 |
| 17 | `POST /contact` | 201 |
| 18 | `POST /reviews` | 201 |

A successful run prints `PASSED: 18 FAILED: 0` and exits 0.

> ⚠️ The smoke test **creates real rows** (customer, order, refund, review…).
> Run it against a test/staging database, not production.

---

## 3. Manual verification checklist (after deploying)

**Storefront (React)**
- [ ] Home page loads categories, banners, products (images resolve)
- [ ] Search returns results
- [ ] Product detail → Add to cart → Quick Order works
- [ ] COD order places → order-success → track by order id
- [ ] Register + login (phone) works, session persists on refresh
- [ ] "My Account" shows orders / downloads / refunds / profile
- [ ] Profile update + password change persist
- [ ] Online payment redirects to gateway and returns (with `STORE_URL` set)
- [ ] Digital product appears under Downloads after a paid order
- [ ] Refund request shows in "My Refunds" + admin panel

**Admin (Blade — `/admin`)**
- [ ] Login works, dashboard loads with stats
- [ ] Orders list shows the new storefront orders
- [ ] Change an order status → storefront tracking reflects it
- [ ] Refunds section shows the submitted refund; approve/process it
- [ ] Products/Categories/Coupons/Banners CRUD works

**Infra**
- [ ] `php artisan migrate` + `db:seed` completed
- [ ] `php artisan storage:link` created
- [ ] Cron scheduler registered (cPanel cron or VPS crontab)
- [ ] Queue worker running (or `QUEUE_CONNECTION=sync`)
- [ ] `.env` not web-accessible (blocked by `.htaccess`/nginx)
- [ ] Logs (`storage/logs/laravel.log`) show no repeated errors

---

## 4. Common issues

| Symptom | Fix |
|---|---|
| `500` on bootstrap | Check `storage/logs/laravel.log`; run `php artisan config:clear` after `.env` edits |
| Images broken (relative paths) | The API prefixes stored paths with `APP_URL` — make sure `APP_URL` is the public URL (with `https://`) |
| Login returns 422 | Phone must be registered; check the customer row exists |
| Payment callback redirects to Blade page | Set `STORE_URL` in `.env` to the React app URL |
| `queue:work` error | `jobs` table migration added — run `php artisan migrate` again |
