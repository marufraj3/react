# 🤖 Gemini Assistant - Admin AI Helper

This feature adds a powerful AI assistant powered by Google Gemini to your e-commerce admin panel.

## 📸 Screenshot Reference
- Sidebar: `Gemini Assistant` menu (red border in user screenshot)
- Main UI: Gradient header with "Gemini Advance Assistant" title
- Buttons: Refresh Data, Clear Chat, API Settings
- Input: "মেসেজ লিখুন... (Enter = পাঠান, Shift+Enter = নতুন লাইন)"
- Floating robot button bottom-right
- Link from screenshot: `/admin/gemini-ai/settings`

## ✨ Features

### 1. Admin Chat Assistant
- **Route**: `/admin/gemini-assistant` or `/admin/gemini-ai`
- **AI understands**: Orders, Products, Categories, Customers, Vendors, Brands, Fund, Expenses, Purchases, Coupons, Reviews, Blog, SEO, Banner, Popup, Fraud API, SMS/Payment/Courier integrations, Facebook CAPI, GTM, Pixels, Cron jobs etc.
- **Live Data**: Shows real stats (today orders, revenue, pending orders, products, vendors etc.) via cached context (5 min TTL, refreshable)
- **Multilingual**: Auto detects Bangla / English. Responds in same language user speaks.
- **Chat History**: Per admin user + session, stored in `gemini_chats` table
- **Markdown Rendering**: AI responses rendered with markdown (bullets, code, etc.)

### 2. Quick Suggestions
- "আজ কত অর্ডার?"
- "Product approve"
- "Fraud check"
- "Gemini API setup"
- "Pending orders"
- "আজকের রেভিনিউ"

### 3. API Settings
- **Route**: `/admin/gemini-ai/settings`
- Configure:
  - API Key (from Google AI Studio)
  - Model (gemini-2.5-flash, gemini-2.5-pro)
  - Temperature, Max Tokens
  - System Prompt (customizable)
  - Language preference
  - Status toggle
- **Test Connection** button with live API call

### 4. Product Description Generator
- **Buttons** in Product Create/Edit pages:
  - `✨ Gemini AI Generate` + language selector (BN/EN)
- Uses same Gemini service to generate SEO-friendly descriptions
- **Endpoint**: `POST /admin/gemini-ai/generate-description`

### 5. Other Endpoints
- `POST /admin/gemini-ai/chat` - chat
- `POST /admin/gemini-ai/clear` - clear chat
- `POST /admin/gemini-ai/refresh` - refresh store data cache
- `GET /admin/gemini-ai/context` - get context JSON
- `GET /admin/gemini-ai/quick-stats?type=today_orders` - quick stats

## 🗃️ Database

### `gemini_settings`
- api_key, model, status, system_prompt, temperature, max_output_tokens, language, include_store_data, log_conversation

### `gemini_chats`
- admin_id, session_id, role (user/model/system), message, metadata (usage, model), timestamps

Migrations:
- `2026_08_15_000002_create_gemini_settings_table.php`
- `2026_08_15_000003_create_gemini_chats_table.php`

Run: `php artisan migrate`

## ⚙️ Configuration

### .env
```env
GEMINI_API_KEY=AIzaSy...
GEMINI_MODEL=gemini-2.5-flash
GEMINI_BASE_URL=https://generativelanguage.googleapis.com/v1beta
```

### config/gemini.php
- models list, temperature defaults, history_limit (20), context_cache_ttl (300 sec)

## 🔧 Service

`App\Services\GeminiService`
- `getStoreContext($forceRefresh=false)` - collects stats from DB (orders, products, etc.) with Cache
- `formatContextForPrompt()` - formats context for AI prompt
- `chat($message, $history, $storeContext)` - calls `https://generativelanguage.googleapis.com/v1beta/models/{model}:generateContent?key=...`
- `generateText($prompt)` - simple text generation
- `testConnection()` - test API

Uses Laravel HTTP client with 30s timeout.

## 🧭 Routes

Added in `routes/web.php`:
```php
Route::prefix('admin')->middleware(['auth:admin', 'admin'])->name('admin.')->group(function () {
    Route::get('/gemini-assistant', [GeminiAssistantController::class, 'index'])->name('gemini.index');
    Route::get('/gemini-ai', [GeminiAssistantController::class, 'index'])->name('gemini.ai');
    Route::post('/gemini-ai/chat', [GeminiAssistantController::class, 'chat'])->name('gemini.chat');
    // ... etc
});
```

## 🎨 Views

- `resources/views/backEnd/gemini/index.blade.php` - Main chat UI (replicates screenshot exactly with gradient header, suggestions, chat bubbles, textarea with Enter=send)
- `resources/views/backEnd/gemini/settings.blade.php` - Settings page with test button
- Sidebar: `resources/views/backEnd/layouts/master.blade.php` adds Gemini menu with AI badge and active gradient style
- Product create/edit: Added AI generate buttons

## 🔐 Security

- Admin guarded (`auth:admin`, `admin` middleware)
- API key stored in DB (encrypted? currently plain - consider encrypting in future)
- Logs conversation if enabled
- Demo mode aware (chat allowed, but settings update blocked by demo_mode UI alert)

## 🚀 How to Get Gemini API Key

1. Go to https://aistudio.google.com/app/apikey
2. Login with Google, Create API Key
3. Paste in Admin > Gemini Assistant > API Settings > Save
4. Test connection
5. Start chatting in Bangla or English

Free tier limits (Gemini 2.5 Flash):
- 15 RPM, 1M tokens/min, 1500 RPD - very generous

## 💡 Example Questions to Ask

- "আজ কত অর্ডার এসেছে? রেভিনিউ কত?"
- "Pending products কিভাবে approve করব?"
- "Vendor verification system বুঝিয়ে বলো"
- "একটা প্রোডাক্টের জন্য SEO-friendly description লিখে দাও: [product name]"
- "Fraud API কাজ করে না, কী করব?"
- "Campaign / Landing page কিভাবে বানাবো?"
- "আমার স্টোরের low stock products কয়টা?"

## 🛠️ Future Improvements

- Stream responses (SSE) for typing effect
- Voice input (Web Speech API)
- Product image generation (Imagen)
- Auto fraud analysis with AI
- Email/SMS drafting with AI
- Chat export
- Multi-admin shared knowledge base

## 📦 Deployment

1. Run migrations
2. Set API key in .env or settings page
3. Clear cache: `/admin/clear-cache`
4. Enjoy AI assistant!

---

Built for CreativeDesign e-commerce system. Language: Bangla + English support, friendly tone, technical but helpful.
