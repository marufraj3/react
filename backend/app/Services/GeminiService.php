<?php

namespace App\Services;

use App\Models\GeminiSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class GeminiService
{
    protected GeminiSetting $setting;
    protected string $apiKey;
    protected string $model;
    protected string $baseUrl;

    public function __construct(?GeminiSetting $setting = null)
    {
        $this->setting = $setting ?? GeminiSetting::current();
        $this->apiKey = $this->setting->api_key ?: config('gemini.api_key');
        $this->model = $this->setting->model ?: config('gemini.model', 'gemini-2.5-flash');

        // Google retired the 1.5 series on the v1beta API ("not found" errors).
        // If the stored model is deprecated/unknown, auto-fall back to the default.
        $availableModels = array_keys(config('gemini.models', []));
        if ($availableModels && !in_array($this->model, $availableModels, true)) {
            Log::warning('Gemini: model "' . $this->model . '" is not available, falling back to "' . config('gemini.model', 'gemini-2.5-flash') . '"');
            $this->model = config('gemini.model', 'gemini-2.5-flash');
        }

        $this->baseUrl = config('gemini.base_url', 'https://generativelanguage.googleapis.com/v1beta');
    }

    public function isConfigured(): bool
    {
        return !empty($this->apiKey) && $this->setting->status;
    }

    /**
     * Get comprehensive store context for AI
     */
    public function getStoreContext(bool $forceRefresh = false): array
    {
        $cacheKey = 'gemini_store_context_v2';

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, config('gemini.context_cache_ttl', 300), function () {
            try {
                $today = Carbon::today();
                $yesterday = Carbon::yesterday();
                $thisMonth = Carbon::now()->startOfMonth();

                // Orders
                $totalOrders = DB::table('orders')->count();
                $todayOrders = DB::table('orders')->whereDate('created_at', $today)->count();
                $yesterdayOrders = DB::table('orders')->whereDate('created_at', $yesterday)->count();
                $pendingOrders = DB::table('orders')->whereIn('order_status', [1])->count(); // assuming 1 = pending
                $monthlyOrders = DB::table('orders')->where('created_at', '>=', $thisMonth)->count();
                $todayRevenue = DB::table('orders')->whereDate('created_at', $today)->sum('total') ?? 0;
                $monthlyRevenue = DB::table('orders')->where('created_at', '>=', $thisMonth)->sum('total') ?? 0;

                // Order status breakdown
                $orderStatusStats = [];
                try {
                    $statuses = DB::table('order_statuses')->get();
                    foreach ($statuses as $st) {
                        $count = DB::table('orders')->where('order_status', $st->id)->count();
                        $orderStatusStats[] = $st->name . ': ' . $count;
                    }
                } catch (\Exception $e) {
                    // order_statuses table may not exist or different
                }

                // Products
                $totalProducts = DB::table('products')->count();
                $activeProducts = DB::table('products')->where('status', 1)->count();
                $pendingProducts = 0;
                try {
                    $pendingProducts = DB::table('products')->where('approval_status', 'pending')->count();
                } catch (\Exception $e) {}
                $lowStock = DB::table('products')->where('stock', '<=', 5)->where('status', 1)->count();

                // Categories & Brands
                $categories = DB::table('categories')->count();
                $brands = DB::table('brands')->count();

                // Customers
                $customers = DB::table('customers')->count() ?? 0;
                $users = DB::table('users')->count();

                // Vendors
                $vendors = 0;
                $pendingVendors = 0;
                try {
                    $vendors = DB::table('vendors')->count();
                    $pendingVendors = DB::table('vendors')->where('verification_status', 'pending')->count();
                } catch (\Exception $e) {}

                // Reviews
                $pendingReviews = 0;
                try {
                    $pendingReviews = DB::table('reviews')->where('status', 'pending')->count();
                } catch (\Exception $e) {}

                // Incomplete orders
                $incompleteOrders = 0;
                try {
                    $incompleteOrders = DB::table('incomplete_orders')->count();
                } catch (\Exception $e) {}

                // Fund
                $fundBalance = 0;
                try {
                    $fundBalance = DB::table('fund_transactions')->sum('amount') ?? 0;
                } catch (\Exception $e) {}

                // Recent orders (last 5)
                $recentOrders = DB::table('orders')->latest()->limit(5)->get(['invoice_id', 'total', 'order_status', 'created_at'])->map(function($o){
                    return $o->invoice_id . ' - ৳' . $o->total . ' (' . $o->created_at . ')';
                })->toArray();

                return [
                    'date' => now()->toDateTimeString(),
                    'today' => $today->toDateString(),
                    'stats' => [
                        'total_orders' => $totalOrders,
                        'today_orders' => $todayOrders,
                        'yesterday_orders' => $yesterdayOrders,
                        'pending_orders' => $pendingOrders,
                        'monthly_orders' => $monthlyOrders,
                        'today_revenue' => $todayRevenue,
                        'monthly_revenue' => $monthlyRevenue,
                        'total_products' => $totalProducts,
                        'active_products' => $activeProducts,
                        'pending_products' => $pendingProducts,
                        'low_stock_products' => $lowStock,
                        'categories' => $categories,
                        'brands' => $brands,
                        'customers' => $customers,
                        'users' => $users,
                        'vendors' => $vendors,
                        'pending_vendors' => $pendingVendors,
                        'pending_reviews' => $pendingReviews,
                        'incomplete_orders' => $incompleteOrders,
                        'fund_balance_approx' => $fundBalance,
                    ],
                    'order_status_breakdown' => $orderStatusStats,
                    'recent_orders' => $recentOrders,
                ];
            } catch (\Exception $e) {
                Log::error('Gemini store context error: ' . $e->getMessage());
                return [
                    'date' => now()->toDateTimeString(),
                    'error' => 'Unable to fetch full store data: ' . $e->getMessage(),
                    'stats' => [],
                ];
            }
        });
    }

    public function formatContextForPrompt(array $context): string
    {
        $stats = $context['stats'] ?? [];
        $text = "Current Date/Time: " . ($context['date'] ?? now()) . "\n";
        $text .= "Store Live Data:\n";
        foreach ($stats as $k => $v) {
            $label = str_replace('_', ' ', ucwords($k, '_'));
            $text .= "- $label: $v\n";
        }
        if (!empty($context['order_status_breakdown'])) {
            $text .= "- Order Status Breakdown: " . implode(', ', $context['order_status_breakdown']) . "\n";
        }
        if (!empty($context['recent_orders'])) {
            $text .= "- Recent 5 Orders: " . implode(' | ', $context['recent_orders']) . "\n";
        }
        $text .= "\nAdmin Panel Features Knowledge:\n";
        $text .= "- POS System: /admin/order/create\n";
        $text .= "- Orders: /admin/order/all, pending etc\n";
        $text .= "- Products: Inhouse, Vendor, Pending, Wholesale\n";
        $text .= "- Vendor Verification: /admin/vendor-verifications\n";
        $text .= "- Fraud Check: /admin/manual-fraud-check\n";
        $text .= "- Settings: General, Site, API Integration, Email, etc\n";
        $text .= "- Fund/Account, Expenses, Purchases, Suppliers\n";
        $text .= "- Marketing: Coupons, Campaign/Landing Page, Banner, Popup, Blog\n";
        $text .= "- Analytics: Live Ads Result (Facebook, Google, TikTok), Facebook Page Post, GTM, Pixels\n";
        $text .= "- Support: Complaints, Contact Messages, Reviews\n";
        $text .= "- Tools: Cron Job, Clear Cache, Sitemap, Error Log, SEO Settings\n";

        return $text;
    }

    /**
     * Main chat method
     */
    public function chat(string $userMessage, array $history = [], ?array $storeContext = null): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'Gemini API key not configured. Please set it in API Settings.',
                'error_code' => 'no_api_key'
            ];
        }

        $storeContext = $storeContext ?? $this->getStoreContext();
        $contextText = $this->formatContextForPrompt($storeContext);

        $systemPrompt = $this->setting->system_prompt ?: GeminiSetting::defaultPrompt();

        // Build contents array for Gemini
        $contents = [];

        // System instruction via first user message (Gemini doesn't have system role in same way, we prepend)
        // We'll send system + context as first user part and instruct model
        $fullSystem = $systemPrompt . "\n\n--- LIVE STORE CONTEXT ---\n" . $contextText . "\n--- END CONTEXT ---\n\nAnswer based on above context when relevant. User Language: Auto detect Bengali/English.";

        // Add history (last N)
        $historyLimit = config('gemini.history_limit', 20);
        $history = array_slice($history, -$historyLimit);

        foreach ($history as $chat) {
            // $chat expected ['role' => 'user'|'model', 'message' => '...']
            $role = $chat['role'] === 'user' ? 'user' : 'model';
            $contents[] = [
                'role' => $role,
                'parts' => [['text' => $chat['message']]]
            ];
        }

        // Add current user message
        $combinedUserMessage = $fullSystem . "\n\nUser Question: " . $userMessage;

        // If history empty, first message should include system, else last user message is new
        if (empty($contents)) {
            $contents[] = [
                'role' => 'user',
                'parts' => [['text' => $combinedUserMessage]]
            ];
        } else {
            // Add system as separate context if needed - we put it as user before latest
            // For simplicity, append user message alone, because system already injected via previous? 
            // We'll inject system in latest user message to keep fresh context
            $contents[] = [
                'role' => 'user',
                'parts' => [['text' => "--- LIVE STORE CONTEXT (Updated) ---\n" . $contextText . "\n\nUser Question: " . $userMessage]]
            ];
        }

        // If first message and we added combined, we need to adjust: if we had empty, we used combined, else we used latest with context
        // For first message with empty history, we already used combined which includes systemPrompt

        // Prepend system instruction as separate if model supports systemInstruction
        // Gemini 1.5 supports systemInstruction field

        $payload = [
            'contents' => $contents,
            'systemInstruction' => [
                'parts' => [['text' => $systemPrompt]]
            ],
            'generationConfig' => [
                'temperature' => (float) ($this->setting->temperature ?? config('gemini.temperature', 0.7)),
                'maxOutputTokens' => (int) ($this->setting->max_output_tokens ?? config('gemini.max_output_tokens', 2048)),
                'topP' => config('gemini.top_p', 0.95),
                'topK' => config('gemini.top_k', 64),
            ]
        ];

        $url = rtrim($this->baseUrl, '/') . '/models/' . $this->model . ':generateContent?key=' . $this->apiKey;

        try {
            $response = Http::timeout(30)->post($url, $payload);

            if (!$response->successful()) {
                $errorBody = $response->json();
                $errorMsg = $errorBody['error']['message'] ?? $response->body();
                Log::error('Gemini API Error', ['status' => $response->status(), 'body' => $errorBody]);

                return [
                    'success' => false,
                    'message' => 'Gemini API Error: ' . $errorMsg,
                    'status' => $response->status(),
                    'raw' => $errorBody
                ];
            }

            $data = $response->json();

            $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;

            if (!$text) {
                // Try alternative parsing
                $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? 'No response';
            }

            // Usage metadata
            $usage = $data['usageMetadata'] ?? null;

            return [
                'success' => true,
                'message' => $text,
                'usage' => $usage,
                'raw' => $data
            ];

        } catch (\Exception $e) {
            Log::error('Gemini Exception: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return [
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Simple text generation (without chat history) - useful for product descriptions
     */
    public function generateText(string $prompt, ?array $context = null): array
    {
        // reuse chat with empty history
        return $this->chat($prompt, [], $context);
    }

    public function testConnection(): array
    {
        return $this->chat('Hello! Just testing connection. Reply with "Connection OK" in same language.', [], $this->getStoreContext());
    }
}
