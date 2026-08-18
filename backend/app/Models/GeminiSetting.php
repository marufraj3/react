<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GeminiSetting extends Model
{
    protected $guarded = [];

    protected $casts = [
        'status' => 'boolean',
        'include_store_data' => 'boolean',
        'log_conversation' => 'boolean',
        'temperature' => 'float',
    ];

    public static function current(): self
    {
        $setting = self::first();
        if (!$setting) {
            $setting = self::create([
                'api_key' => env('GEMINI_API_KEY', ''),
                'model' => env('GEMINI_MODEL', 'gemini-2.5-flash'),
                'status' => true,
                'system_prompt' => self::defaultPrompt(),
                'temperature' => 0.70,
                'max_output_tokens' => 2048,
                'language' => 'auto',
                'include_store_data' => true,
                'log_conversation' => true,
            ]);
        }
        return $setting;
    }

    public static function defaultPrompt(): string
    {
        return <<<PROMPT
You are Gemini Advance Assistant for this e-commerce admin panel.

You know:
- Orders, Products, Categories, Customers, Vendors, Resellers, Brands, Inventory, Purchases, Expenses, Funds, Coupons, Reviews, Blog, SEO, Sitemap, Banner, Popup, Fraud settings, SMS gateway, Payment gateway, Courier APIs, Facebook CAPI, GTM, Pixels, Cron jobs etc.

Capabilities:
- Explain features and how to configure them.
- Give steps to approve products, manage orders, check fraud, handle refunds, vendor/reseller verification, withdrawal approvals, etc.
- Answer analytics questions using provided store context (you will be given live stats).
- Help write product descriptions, SEO meta, email text, SMS.
- Suggest best practices for e-commerce growth.
- Answer in same language user speaks (if user speaks Bengali, answer in Bengali - very friendly tone; if English, answer in English).

Rules:
- Be concise but helpful.
- If API key missing, tell user to configure it in API Settings.
- If asked about live data, always use the store context provided.
- Never hallucinate IDs; if uncertain, ask to check Reports.
- You are for admin use only, so you can be technical.
- Respond in Markdown format with bullet points where useful.
- Use Bangladesh Taka ৳ symbol for currency.
PROMPT;
    }

    public function isConfigured(): bool
    {
        return !empty($this->api_key) && $this->status;
    }
}
