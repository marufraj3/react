# 💻 আপনার PC-তে Localhost-এ Test করার নিয়ম (Docker ছাড়া)

Docker **লাগবে না**। নিচে সহজ থেকে কঠিন — **৪টা উপায়**। আপনি যেটা চান সেটা বেছে নিন।

| উপায় | কী লাগবে | কী দেখতে পাবেন |
|---|---|---|
| **A. শুধু storefront (সবচেয়ে সহজ)** | শুধু Node.js | দোকানের ডিজাইন + demo data |
| **B. Laragon (full stack, recommended)** | Laragon (১টা installer) | সত্যিকারের backend + admin + ডেটাবেস |
| C. Docker | Docker Desktop | full stack |
| D. XAMPP | XAMPP | full stack |

---

## ✅ A. সবচেয়ে সহজ — শুধু storefront দেখুন (backend লাগে না)

PHP, MySQL, Composer, Docker — **কিছুই লাগবে না**। শুধু **Node.js LTS** install করুন → https://nodejs.org

```bash
# repo root-এ (react ফোল্ডারে)
npm install
npm run dev
```
খুলুন → **http://localhost:3000**

এটাই পুরো দোকানের ডিজাইন + সব পেজ + demo data (cart, order, admin preview সব কাজ করে — localStorage-এ থাকে)।

> ⚠️ এতে **আসল ডেটাবেস/backend চলে না** — এটা design preview + demo mode।

---

## ✅ B. Full stack — Laragon দিয়ে (recommended, Docker লাগে না)

**Laragon** একটা free installer — একসাথে **PHP + MySQL + Apache + Composer + HeidiSQL** install করে দেয়। XAMPP-এর চেয়ে অনেক সহজ।

### ধাপ ১: Laragon install
1. Download → https://laragon.org/download (Full version)
2. Install → Start **Laragon** → **"Start All"** চাপুন (সবুজ হয়ে যাবে)

### ধাপ ২: Project ফোল্ডার রাখুন
- Repo-টা clone করুন (বা zip নামিয়ে) `C:\laragon\www\` এর ভেতরে:
  ```
  C:\laragon\www\react\   ← পুরো repo এখানে
  ```
  (আগেই repo-টা `marufraj3/react` থেকে GitHub Desktop / `git clone` দিয়ে নামান)

### ধাপ ৩: Backend সেটআপ (এক কমান্ড!)
1. Laragon-এর tray icon-এ **right-click → Terminal** খুলুন
2. লিখুন:
   ```bash
   cd C:\laragon\www\react\backend
   setup-local.bat
   ```
   এটি নিজে থেকে: `composer install` → `.env` বানায় → `key:generate` → **`shopgenie` ডেটাবেস বানায়** → migrate + demo data seed করে।

   > (Terminal-এ `.bat` না চাইলে ম্যানুয়ালি: `composer install` → `copy .env.example .env` → `php artisan key:generate` → `php artisan migrate --seed`)

### ধাপ ৪: Backend চালু করুন
```bash
start-local.bat
```
→ **http://localhost:8000** (admin: **http://localhost:8000/admin**)

**Admin login:** `admin@gmail.com` / `123456`

### ধাপ ৫: React storefront চালান (নতুন terminal)
```bash
cd C:\laragon\www\react
npm install
```
root-এ `.env` ফাইল বানান (নোটপ্যাডে):
```
VITE_API_URL=http://localhost:8000
VITE_BACKEND_PROXY=http://localhost:8000
```
```bash
npm run dev
```
→ **http://localhost:3000** — storefront এখন **আসল backend + ডেটাবেস**-এ চলছে। ✅

### ধাপ ৬ (ঐচ্ছিক): Auto test
Laragon Terminal-এ:
```bash
cd C:\laragon\www\react\backend
bash smoke-test.sh
```
> Windows-এ `bash` না থাকলে Git Bash বা WSL দিয়ে চালান; বা ধাপ ৩-৫ ম্যানুয়ালি test করুন।

---

## C. Docker দিয়ে (যদি কখনও install করেন)

```bash
cd backend
docker compose up -d --build
```
তারপর `npm install` + `.env` set করে `npm run dev`।

---

## D. XAMPP দিয়ে

1. **XAMPP** install → https://www.apachefriends.org
2. XAMPP Control Panel → **MySQL** + **Apache** Start করুন
3. Repo-টা `C:\xampp\htdocs\react\` এ রাখুন
4. phpMyAdmin খুলে (http://localhost/phpmyadmin) **`shopgenie`** নামে database বানান
5. `backend/` ফোল্ডারে terminal খুলে:
   ```bash
   composer install
   copy .env.example .env
   php artisan key:generate
   php artisan migrate --seed
   php artisan serve --host=0.0.0.0 --port=8000
   ```
6. React: root-এ `.env` set করে `npm install && npm run dev`

> XAMPP-তে PHP/Composer **PATH-তে থাকে না** — `setup-local.bat` চলবে না, ম্যানুয়াল কমান্ড দিন (XAMPP-এর shell বাটনে click করে)।

---

## 🔌 React ↔ Laravel কানেকশন

| Setting | কোথায় | মান |
|---|---|---|
| `VITE_API_URL` | React-এর `.env` | `http://localhost:8000` |
| `VITE_BACKEND_PROXY` | React-এর `.env` | `http://localhost:8000` |
| `STORE_URL` | Laravel-এর `.env` | React URL (প্রোডাকশনে দরকার) |

> **VITE_API_URL না দিলে** React demo mode-এ চলে (backend লাগে না)।

---

## ❗ Common সমস্যা

| সমস্যা | সমাধান |
|---|---|
| `vite: not found` | root-এ `npm install` চালান |
| `php not recognized` | Laragon/XAMPP-এর terminal ব্যবহার করুন, বা PHP PATH-এ add করুন |
| `Access denied for user root` | Laragon/XAMPP-এ MySQL root-এর password খালি থাকে; `.env`-এ `DB_PASSWORD=` (খালি) রাখুন |
| ডেটাবেস `shopgenie` নেই | HeidiSQL/phpMyAdmin-এ manually `shopgenie` database বানান |
| লগইন 422 | আগে register করুন (My Account → লগইন/রেজিস্টার) |
| ছবি ভাঙা | `.env`-এ `APP_URL=http://localhost:8000` দিন |

---

## 🎯 সবচেয়ে দ্রুত summary

**শুধু দেখতে চাইলে:**
```bash
npm install && npm run dev        # → http://localhost:3000
```

**আসল backend সহ (Laragon install করার পর):**
```bash
cd C:\laragon\www\react\backend
setup-local.bat
start-local.bat                   # → http://localhost:8000/admin

cd C:\laragon\www\react
npm install
# .env: VITE_API_URL=http://localhost:8000
npm run dev                       # → http://localhost:3000
```
