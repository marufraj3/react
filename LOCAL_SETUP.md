# 💻 আপনার PC-তে Localhost-এ Test করার নিয়ম

এই প্রজেক্টের **২টা অংশ**:

| অংশ | Folder | কী চালায় |
|---|---|---|
| React storefront (দোকানের সামনের অংশ) | `/` (root) | Node.js + Vite |
| Laravel backend + Admin panel | `/backend` | PHP 8.2 + MySQL |

দুটো আলাদা সার্ভারে চলে:
- React → `http://localhost:3000`
- Laravel → `http://localhost:8000`

React Laravel-এর API-কে call করে। নিচে **দুটো উপায়** দেওয়া হলো — আপনার PC-তে যা আছে সেটা বেছে নিন।

---

## 🐳 Option A: Docker (সবচেয়ে সহজ — recommended)

শুধু **Docker Desktop** install থাকলেই হবে (PHP/MySQL/Composer আলাদা লাগবে না)।

### ধাপ ১: Backend চালু করুন
```bash
cd backend
docker compose up -d --build
```
প্রথমবার বিল্ড হতে ৩-৫ মিনিট লাগতে পারে। নিজে থেকেই:
- MySQL 8 চালু করে
- `composer install` করে
- `APP_KEY` generate করে
- database migrate + **demo data seed** করে

তারপর ব্রাউজারে খুলুন:
| URL | কী |
|---|---|
| http://localhost:8000 | Laravel backend |
| http://localhost:8000/api/v1/storefront/bootstrap | Storefront API (JSON দেখাবে) |
| http://localhost:8000/admin | Admin panel |

**Admin login:** `admin@gmail.com` / `123456`

### ধাপ ২: React storefront চালু করুন (নতুন terminal)
```bash
cd ..                # repo root-এ ফিরে আসুন
npm install
```
তারপর `.env` ফাইল বানান (root-এ):
```bash
VITE_API_URL=http://localhost:8000
VITE_BACKEND_PROXY=http://localhost:8000
```
এখন চালান:
```bash
npm run dev
```
খুলুন → http://localhost:3000 — storefront এখন **সত্যিকারের Laravel backend**-এ চলছে (লগইন, অর্ডার, পেমেন্ট, ডাউনলোড, রিফান্ড — সব persistent)।

> ⚠️ **শুধু design preview দেখতে** `.env` বাদ দিয়ে `npm run dev` চালালেই হবে (demo data + localStorage mode)।

### ধাপ ৩ (ঐচ্ছিক): Auto end-to-end test
```bash
cd backend
bash smoke-test.sh              # ১৮টা API endpoint auto test করবে
```

### বন্ধ / মুছতে
```bash
docker compose down             # বন্ধ (ডেটা থাকবে)
docker compose down -v          # বন্ধ + ডেটাবেস মুছে ফেলবে
```

---

## 🖥️ Option B: Manual (XAMPP / Laragon / LAMP)

আপনার PC-তে থাকতে হবে: **PHP 8.2+**, **Composer**, **MySQL**, **Node.js 18+**।

### ধাপ ১: Backend সেটআপ
```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
```
`.env` ফাইলে DB বদলান:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=shopgenie
DB_USERNAME=root
DB_PASSWORD=          # আপনার MySQL password
```

### ধাপ ২: Database তৈরি + migrate
phpMyAdmin (বা CLI) দিয়ে `shopgenie` database বানান, তারপর:
```bash
php artisan migrate --seed
```
> এটি সব table + **demo data** (product, category, admin user) বানাবে।
> চাইলে original data পেতে: `mysql -u root -p shopgenie < creativedesignbd_myshop1.sql`

### ধাপ ৩: Backend চালান
```bash
php artisan serve --host=0.0.0.0 --port=8000
```
**Admin login:** `admin@gmail.com` / `123456`

### ধাপ ৪: React storefront চালান
```bash
cd ..                 # repo root
npm install
```
`.env` ফাইল (root-এ):
```bash
VITE_API_URL=http://localhost:8000
VITE_BACKEND_PROXY=http://localhost:8000
```
```bash
npm run dev           # → http://localhost:3000
```

### (XAMPP/Laragon-এ Apache দিয়ে চালাতে চাইলে)
- `backend/` ফোল্ডারটা `htdocs`/`www`-এ copy করুন (document root = app root, কারণ `index.php` root-এ আছে)
- Apache-এ rewrite enable করুন, তারপর → `http://localhost/backend/`

---

## 🔌 React ↔ Laravel কানেকশন (মনে রাখুন)

| Setting | কোথায় | মান |
|---|---|---|
| `VITE_API_URL` | React-এর `.env` | Laravel-এর base URL (`http://localhost:8000`) |
| `VITE_BACKEND_PROXY` | React-এর `.env` | dev-এ `/api` proxy কোথায় যাবে (CORS এড়ায়) |
| `STORE_URL` | Laravel-এর `.env` | পেমেন্ট callback কোথায় ফিরবে (React URL, প্রোডাকশনে দরকার) |

> **VITE_API_URL খালি থাকলে** React **demo mode**-এ চলে (localStorage data) — কোনো backend লাগে না।

---

## ❗ Common সমস্যা ও সমাধান

| সমস্যা | কারণ / সমাধান |
|---|---|
| `vite: not found` | `npm install` চালাননি — root-এ `npm install` দিন |
| `bootstrap` 500 error | `backend/storage/logs/laravel.log` দেখুন; `.env` বদলে থাকলে `php artisan config:clear` চালান |
| লগইন 422 | ফোন নম্বর আগে register করা লাগবে (My Account → লগইন/রেজিস্টার) |
| ছবি ভাঙা | `APP_URL` সঠিক base URL দিন (http://localhost:8000) |
| পেমেন্ট callback Blade page-এ যায় | `STORE_URL` Laravel `.env`-এ set করুন |
| Docker build fail (COPY) | repo latest pull করুন — build context fix করা আছে |
| Linux-এ `vendor` root-owned | `sudo chown -R $USER:$USER backend/vendor` চালান |

---

## 🎯 সংক্ষিপ্ত quick start (Docker)

```bash
# terminal 1
cd backend && docker compose up -d --build

# terminal 2
npm install
echo "VITE_API_URL=http://localhost:8000
VITE_BACKEND_PROXY=http://localhost:8000" > .env
npm run dev
```
তারপর: storefront → http://localhost:3000, admin → http://localhost:8000/admin (admin@gmail.com / 123456)
