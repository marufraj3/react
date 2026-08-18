<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\GeminiSetting;
use App\Models\GeminiChat;
use App\Services\GeminiService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class GeminiAssistantController extends Controller
{
    protected function adminId()
    {
        return Auth::guard('admin')->id();
    }

    protected function sessionId(Request $request)
    {
        // Use admin id based session to keep chat per admin
        // Also allow custom session_id for future multi-convo
        $sid = $request->header('X-Gemini-Session') ?? session('gemini_session_id');
        if (!$sid) {
            $sid = 'admin_' . $this->adminId() . '_' . Str::random(12);
            session(['gemini_session_id' => $sid]);
        }
        return $sid;
    }

    public function index(Request $request)
    {
        $setting = GeminiSetting::current();
        $adminId = $this->adminId();
        $sessionId = $this->sessionId($request);

        $chats = GeminiChat::where('admin_id', $adminId)
            ->where('session_id', $sessionId)
            ->orderBy('created_at', 'asc')
            ->limit(100)
            ->get();

        return view('backEnd.gemini.index', compact('setting', 'chats', 'sessionId'));
    }

    public function chat(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:5000',
        ]);

        $adminId = $this->adminId();
        $sessionId = $this->sessionId($request);
        $userMessage = trim($request->message);

        $setting = GeminiSetting::current();

        if (!$setting->isConfigured()) {
            return response()->json([
                'success' => false,
                'error' => 'no_api_key',
                'message' => 'API Key সেট করা নেই। দয়া করে API Settings এ গিয়ে Gemini API Key সেট করুন।',
                'settings_url' => route('admin.gemini.settings')
            ], 400);
        }

        // Save user message
        if ($setting->log_conversation) {
            GeminiChat::create([
                'admin_id' => $adminId,
                'session_id' => $sessionId,
                'role' => 'user',
                'message' => $userMessage,
            ]);
        }

        // Get history
        $historyRows = GeminiChat::where('admin_id', $adminId)
            ->where('session_id', $sessionId)
            ->orderBy('created_at', 'asc')
            ->get();

        // Convert to Gemini format (exclude last user message we just saved, to avoid duplication? Actually we include all except current, but service will append current. So prepare history excluding current)
        $historyForGemini = [];
        // Last 10 pairs (20 messages) excluding current last
        $historySlice = $historyRows->slice(0, -1)->slice(-20);
        foreach ($historySlice as $h) {
            $historyForGemini[] = [
                'role' => $h->role === 'user' ? 'user' : 'model',
                'message' => $h->message,
            ];
        }

        $service = new GeminiService($setting);
        $storeContext = $service->getStoreContext();

        $result = $service->chat($userMessage, $historyForGemini, $storeContext);

        if (!$result['success']) {
            // Even if failed, save assistant error? No
            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'Unknown error',
                'raw' => $result['raw'] ?? null,
            ], 500);
        }

        $aiMessage = $result['message'];

        // Save AI response
        if ($setting->log_conversation) {
            GeminiChat::create([
                'admin_id' => $adminId,
                'session_id' => $sessionId,
                'role' => 'model',
                'message' => $aiMessage,
                'metadata' => [
                    'usage' => $result['usage'] ?? null,
                    'model' => $setting->model,
                ],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => $aiMessage,
            'usage' => $result['usage'] ?? null,
        ]);
    }

    public function clearChat(Request $request)
    {
        $adminId = $this->adminId();
        $sessionId = $this->sessionId($request);

        GeminiChat::where('admin_id', $adminId)->where('session_id', $sessionId)->delete();

        // Generate new session id
        $newSid = 'admin_' . $adminId . '_' . Str::random(12);
        session(['gemini_session_id' => $newSid]);

        return response()->json(['success' => true, 'message' => 'Chat cleared', 'new_session_id' => $newSid]);
    }

    public function refreshData(Request $request)
    {
        $service = new GeminiService();
        $context = $service->getStoreContext(true); // force refresh

        // also clear cache keys
        Cache::forget('gemini_store_context_v2');

        return response()->json([
            'success' => true,
            'message' => 'Data refreshed',
            'context' => $context,
        ]);
    }

    public function getContext(Request $request)
    {
        $service = new GeminiService();
        $context = $service->getStoreContext($request->boolean('refresh', false));

        return response()->json([
            'success' => true,
            'context' => $context,
            'formatted' => $service->formatContextForPrompt($context),
        ]);
    }

    // Settings pages

    public function settings()
    {
        $setting = GeminiSetting::current();
        return view('backEnd.gemini.settings', compact('setting'));
    }

    public function updateSettings(Request $request)
    {
        $request->validate([
            'api_key' => 'nullable|string|max:500',
            'model' => 'required|string',
            'temperature' => 'required|numeric|min:0|max:2',
            'max_output_tokens' => 'required|integer|min:100|max:8192',
            'system_prompt' => 'nullable|string|max:10000',
            'language' => 'required|in:auto,bn,en',
            'status' => 'nullable|boolean',
            'include_store_data' => 'nullable|boolean',
            'log_conversation' => 'nullable|boolean',
        ]);

        $setting = GeminiSetting::current();
        $setting->update([
            'api_key' => $request->api_key,
            'model' => $request->model,
            'temperature' => $request->temperature,
            'max_output_tokens' => $request->max_output_tokens,
            'system_prompt' => $request->system_prompt,
            'language' => $request->language,
            'status' => $request->has('status') ? (bool)$request->status : true,
            'include_store_data' => $request->has('include_store_data') ? (bool)$request->include_store_data : true,
            'log_conversation' => $request->has('log_conversation') ? (bool)$request->log_conversation : true,
        ]);

        // Clear context cache
        Cache::forget('gemini_store_context_v2');

        return redirect()->route('admin.gemini.settings')->with('success', 'Gemini settings updated successfully');
    }

    public function testSettings(Request $request)
    {
        $setting = GeminiSetting::current();
        // If request has temporary api_key for testing before saving
        if ($request->has('api_key')) {
            $setting->api_key = $request->api_key;
            $setting->model = $request->model ?? $setting->model;
        }

        $service = new GeminiService($setting);
        $result = $service->testConnection();

        return response()->json($result);
    }

    /**
     * Generate product description (optional feature for product page)
     */
    public function generateProductDescription(Request $request)
    {
        $request->validate([
            'product_name' => 'required|string|max:500',
            'category' => 'nullable|string|max:200',
            'keywords' => 'nullable|string|max:1000',
            'language' => 'nullable|in:bn,en',
        ]);

        $setting = GeminiSetting::current();
        if (!$setting->isConfigured()) {
            return response()->json(['success' => false, 'message' => 'API Key not configured'], 400);
        }

        $lang = $request->language ?? 'bn';
        $prompt = "You are ecommerce copywriter. Write an attractive product description";
        if ($lang === 'bn') {
            $prompt .= " in Bengali (Bangla) language";
        } else {
            $prompt .= " in English";
        }
        $prompt .= " for product: \"{$request->product_name}\". ";
        if ($request->category) {
            $prompt .= "Category: {$request->category}. ";
        }
        if ($request->keywords) {
            $prompt .= "Keywords/Features: {$request->keywords}. ";
        }
        $prompt .= " Make it SEO friendly, 3-4 paragraphs, include bullet features, and add call to action. Use taka sign ৳ if price context but don't invent price.";

        $service = new GeminiService($setting);
        $result = $service->generateText($prompt);

        return response()->json($result);
    }

    /**
     * Quick prompts stats endpoint (for suggestion buttons)
     */
    public function quickStats(Request $request)
    {
        $type = $request->query('type');
        $service = new GeminiService();
        $context = $service->getStoreContext();
        $stats = $context['stats'] ?? [];

        $answer = '';
        switch ($type) {
            case 'today_orders':
                $answer = "আজ মোট অর্ডার: {$stats['today_orders']} টি। вчера ছিল {$stats['yesterday_orders']} টি। এই মাসে মোট {$stats['monthly_orders']} টি অর্ডার পেয়েছেন। আজকের রেভিনিউ: ৳{$stats['today_revenue']}";
                break;
            case 'pending_orders':
                $answer = "এখন {$stats['pending_orders']} টি pending order আছে। দ্রুত প্রসেস করুন।";
                break;
            case 'products':
                $answer = "মোট প্রোডাক্ট: {$stats['total_products']}, Active: {$stats['active_products']}, Pending Approval: {$stats['pending_products']}, Low Stock (≤5): {$stats['low_stock_products']}";
                break;
            case 'vendors':
                $answer = "Vendors: {$stats['vendors']}, Pending Verification: {$stats['pending_vendors']}";
                break;
            default:
                $answer = "Quick stats not found";
        }

        return response()->json(['success' => true, 'message' => $answer, 'context' => $stats]);
    }
}
