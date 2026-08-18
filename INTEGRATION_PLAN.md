# Shop Genie — PHP (Laravel) Backend + React Storefront Integration

This repository now combines **two apps** into one project:

| Layer | Location | Stack | Role |
|---|---|---|---|
| **Storefront** | `/` (repo root) | React 19 + Vite + Tailwind | Customer-facing shop (design kept from the original React app) |
| **Backend** | `/backend` | Laravel 12 (PHP 8.2+) | REST API + **full admin panel** (imported from `marufraj3/new`) |

## Architecture

```
Browser
 ├─ React storefront (/)  ── VITE_API_URL ──►  Laravel REST API  (/api/v1/storefront/*)
 └─ Admin panel (/admin)  ──────────────────►  Laravel Blade admin (all 69 modules)
```

- The React app runs in **demo mode** (localStorage seed data) when `VITE_API_URL`
  is empty — this is what the Vite preview shows.
- When `VITE_API_URL` points at a running Laravel app, the storefront **hydrates
  from the API** and **writes back** (orders, reviews, complaints, contact,
  tracking, coupon validation).

## What was imported from `marufraj3/new` (the Laravel app)

- **69 admin controllers**, **88 models**, **185 admin Blade views**,
  **129 migrations**, **15 services** (Gemini, FraudCheck, Coupon, OrderBump,
  StockAlert, CampaignAnalytics, Facebook CAPI, RedX, QuickOrder, …)
- All admin features: Dashboard, POS, Orders (+ Incomplete / status / IP block /
  Refunds), Products (Inhouse / Vendor / Pending / Wholesale), Categories →
  Sub → Child, Brands, Colors, Sizes, Blog, Purchases + Suppliers, **CRM / HR**
  (Employees, Attendance, Leaves, Salaries, Bonuses, Payments), Coupons,
  **Order Bumps**, **Stock Alerts**, Reviews, Campaigns / Landing pages,
  **Manual Fraud + Duplicate-order check**, Custom SMS, Complaints,
  Contact Messages, Newsletter, **Fund / Expenses**, **Vendors** (+ verification,
  wallet, withdrawals), Users / Roles / Permissions / Customers, Site settings,
  Fraud API settings, Order Restriction, API Integration (Payment / SMS /
  Courier), Facebook CAPI, Cron jobs, Pixels & GTM, Live Ads Result, Facebook
  Page Post, Banners / Sliders, Popup Offer, Reports (orders/purchases/expenses/
  stock/profit-loss), SEO, Sitemap, Clear Cache, Error Log, **Reseller/wallet**
  system, **Gemini AI Assistant** + AI product-description generator, and
  ShurjoPay / UddoktaPay / aamarPay / bKash payments.

> Excluded (runtime/generated, intentionally not tracked in git):
> `public/uploads/*` (61 MB of uploaded product images), `error_log`,
> `user.apk` (mobile build), `preview/` design assets, `public/frontEnd/`
> (old Blade storefront theme — replaced by React).

## New storefront REST API (added on top of the Laravel app)

`backend/routes/storefront.php` + `backend/app/Http/Controllers/Api/StorefrontApiController.php`

| Method | Endpoint | Purpose |
|---|---|---|
| GET | `/api/v1/storefront/bootstrap` | settings, categories, subcategories, banners, shipping charges, coupons, products, blogs |
| GET | `/api/v1/storefront/products?category=&sort=&q=` | product catalogue |
| GET | `/api/v1/storefront/products/{idOrSlug}` | product detail + approved reviews |
| GET | `/api/v1/storefront/search?q=` | live search |
| GET | `/api/v1/storefront/blogs` · `blogs/{slug}` | blog list / detail |
| POST | `/api/v1/storefront/coupons/apply` | validate coupon |
| POST | `/api/v1/storefront/orders` | create order (restriction + stock + coupon + shipping logic); returns `redirect_url` for online payments |
| GET | `/api/v1/storefront/orders/track/{invoice}?phone=` | order tracking |
| POST | `/api/v1/storefront/auth/register` | register customer (issues Sanctum token) |
| POST | `/api/v1/storefront/auth/login` | login by phone/email (issues Sanctum token) |
| POST | `/api/v1/storefront/auth/logout` | revoke bearer token |
| GET | `/api/v1/storefront/auth/me` | authenticated customer profile |
| GET | `/api/v1/storefront/auth/orders` | authenticated customer's order history |
| POST | `/api/v1/storefront/auth/profile` | update name/phone/email/address |
| POST | `/api/v1/storefront/auth/password` | change password (old + new) |
| GET | `/api/v1/storefront/auth/downloads` | authenticated customer's digital downloads |
| GET | `/api/v1/storefront/auth/refunds` | authenticated customer's refund requests |
| POST | `/api/v1/storefront/refunds` | create refund request (mirrors Blade RefundController::store) |
| POST | `/api/v1/storefront/reviews` | submit review |
| POST | `/api/v1/storefront/complaints` | submit complaint |
| POST | `/api/v1/storefront/contact` | contact message |

The controller reuses the Laravel services (`OrderRestrictionService`,
`StockAlertService`, `CouponService`) and maps DB columns
(`ratting`, `productcode`, `subcategoryName`, …) to the React `src/types.ts`
shape, so the React client stays thin.

## React changes

- `src/api/client.ts` — typed API client (activated by `VITE_API_URL`).
- `src/context/StoreContext.tsx` — hydrates from `bootstrap` and forwards
  orders/reviews/complaints/contact/tracking to the API when enabled;
  otherwise runs unchanged on localStorage. Also holds customer auth state
  (`authUser`, `authToken`, `myOrders`, `login`, `registerUser`, `logout`).
- `src/components/storefront/AuthModal.tsx` — login/register modal.
- `src/components/storefront/Header.tsx` — account menu now reflects the
  logged-in state (login/register vs. orders/downloads/logout). In API mode the
  "অ্যাডমিন প্যানেল" button opens the real Laravel `/admin` in a new tab.
- `src/components/storefront/CustomerAccountPage.tsx` — shows the real order
  history, downloads, refund requests and profile in API mode (with a login
  prompt when signed out).
- `src/components/storefront/OrderTrackingPage.tsx` — uses `trackOrderAsync`.
- `vite.config.ts` — dev proxy for `/api`, `/uploads`, `/storage` →
  `VITE_BACKEND_PROXY` (default `http://localhost:8000`).
- `src/vite-env.d.ts` — Vite client types.
- Fixed 3 pre-existing admin bugs: `updateCoupon`, `updateOrderCourier`,
  `updateReviewStatus` were called but not implemented (caused crashes), plus
  type alignments in `AdminBanners` / `AdminCoupons` / `AdminComplaints`.

## How to run

### 1. Storefront only (demo mode — no PHP needed)
```bash
npm install
npm run dev        # http://localhost:3000 — localStorage demo data
```

### 2. Backend (requires PHP 8.2+, Composer, MySQL)
```bash
cd backend
composer install
cp .env.example .env            # set DB_* and GEMINI_API_KEY
php artisan key:generate
# create DB + import creativedesignbd_myshop1.sql (or run migrations)
php artisan migrate --seed      # if no SQL dump
php artisan serve               # http://localhost:8000  (admin: /admin)
```

### 3. Connect them (development)
```bash
# repo root
cp .env.example .env
VITE_API_URL=http://localhost:8000 VITE_BACKEND_PROXY=http://localhost:8000 npm run dev
```

### 4. Production (shared cPanel)
- Upload `backend/` to the web root (public_html), point the domain to
  `backend/public/`.
- Build the React app (`npm run build`) and serve `dist/` (either from the
  same host or a subdomain/CDN) with `VITE_API_URL=https://your-domain.com`.

## Notes / caveats

- `backend/app/Http/Kernel.php` is ionCube-encoded (leftover from the original
  script). Laravel 12 does **not** use it (`bootstrap/app.php` handles
  everything), so it is harmless — it can be deleted if you prefer.
- The React admin components remain as a **demo-only preview**. In production
  the admin panel is the Laravel Blade one at `/admin` (that is where the full
  69-module feature set lives).
- Digital-product delivery (download tokens), online-payment callbacks
  (bKash/ShurjoPay/UddoktaPay/aamarPay) and SMS notifications are handled by the
  Laravel side; the storefront API currently places COD/online orders and the
  Laravel payment flow completes the rest.

## Digital downloads

- After an online payment succeeds, the existing gateway controllers
  (`BkashController`, `UddoktaPayController`, `AamarPayController`,
  `ShurjopayControllers`) generate `DigitalDownload` rows (UUID token, file
  path, remaining-download limit, expiry) — the same mechanism used by the
  Blade storefront.
- `GET /api/v1/storefront/auth/downloads` lists the authenticated customer's
  downloads; each item carries a `download_url` pointing at
  `GET /digital-download/{token}` (browser-navigable, streamed from the
  `private` disk with limit/expiry enforcement).
- The React "My Account → Downloads" tab renders these in API mode and falls
  back to the demo order-derived list otherwise.
- Added a `digital_downloads` migration so `php artisan migrate` also creates
  the table (the original app only shipped it inside the SQL dump).

## Online payments (bKash / ShurjoPay / UddoktaPay / aamarPay)

- `POST /api/v1/storefront/orders` returns a `redirect_url` for online payment
  methods:
  - bKash → `/bkash/checkout-url/create?order_id=…`
  - ShurjoPay → `/shurjopay/checkout/{order}` (headless bridge route)
  - UddoktaPay → `/uddoktapay/checkout?order_id=…`
  - aamarPay → `/aamarpay/checkout?order_id=…`
- The React checkout redirects the browser (`window.location.href`) to that URL;
  the gateway then calls back into Laravel (verify/ipn/success/callback), which
  marks the payment paid and redirects the user back.
- **Return to React**: set `STORE_URL` (React app URL) in the Laravel `.env`.
  All success callbacks funnel through `customer.order_success`, which (when
  `STORE_URL` is set) redirects to `{STORE_URL}?order={invoice}&payment=success`.
  Cancel/fail endpoints redirect with `payment=cancelled|failed`. The React app
  reads these query params on boot, fetches the order from the API, and shows
  the success page. Without `STORE_URL` the original Blade pages are used.

## Suggested next phases

1. **Migrate admin to React** screen-by-screen if you ever want a unified
   React admin (Blade admin already covers everything today).
2. **Live deploy** — follow `backend/DEPLOYMENT.md` to go to production.

## Deployment

> 💻 **Localhost-এ নিজের PC-তে test করতে চান?** → **`LOCAL_SETUP.md`** (বাংলা;
> Docker ছাড়াই — Node-only demo mode + Laragon/XAMPP + one-click `setup-local.bat`/`start-local.bat`)।

See **`backend/DEPLOYMENT.md`** for the full guide (shared cPanel + VPS) and
**`backend/TESTING.md`** for verification:

- **One-command local test stack**: `docker-compose.yml` + `docker/entrypoint.sh`
  (MySQL 8 + PHP 8.2, auto composer install / migrate / seed).
- **`DemoDataSeeder`** — seeds settings, contacts, shipping, categories,
  products, banner, coupon so a fresh DB is instantly testable.
- **`smoke-test.sh`** — scripted end-to-end test of all 18 storefront endpoints.
- Shared-hosting layout is already in place: `index.php` + root `.htaccess`
  (document root = app root), with sensitive-file protection added.
- `backend/.env.production.example` — production env template
  (`STORE_URL`, payment gateways, `QUEUE_CONNECTION=database`).
- `backend/deploy.sh` — VPS deploy helper (composer install, migrate,
  storage link, optimize, optional React build copy).
- Added `jobs` table migration (for the database queue) and a
  `storage/app/private/.gitkeep` so the digital-download disk exists on clone.

### Production-correctness fixes made during this pass
- `settingsPayload()` now reads hotline/email/address/whatsapp from the
  `contacts` table and maps the `secodery_color` (typo) + `facebook_page_username`
  columns correctly.
- All image URLs (products, gallery, categories, banners, blogs, logos) are
  prefixed with the app base URL so stored `public/uploads/...` paths render.
- `POST /refunds` accepts either the numeric order id **or** the customer-facing
  `ORD-12345` invoice reference.
