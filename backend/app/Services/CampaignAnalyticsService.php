<?php

namespace App\Services;

use App\Models\CampaignStat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Session;

/**
 * ক্যাম্পেইন পেজের পারফরম্যান্স হিসাব।
 *
 * অর্ডার বাম্পের impressions/conversions যেভাবে গোনা হয় সেই একই ধরন
 * অনুসরণ করা হয়েছে — atomic increment, আলাদা কোনো কুয়েরি-ভারী রিপোর্ট
 * টেবিল নয়।
 *
 * নীতি: অ্যানালিটিক্স কখনোই কাস্টমারের পেজ ভাঙবে না। তাই সব কিছু
 * try/catch-এ মোড়া — হিসাব রাখতে ব্যর্থ হলে চুপচাপ লগ হবে, পেজ চলবে।
 */
class CampaignAnalyticsService
{
    /** এক ভিজিটরকে কত সময় পর্যন্ত "একই ভিজিট" ধরা হবে (মিনিট) */
    protected const VISIT_WINDOW_MINUTES = 30;

    /**
     * ক্যাম্পেইন পেজ ভিউ গোনে।
     *
     * unique_visits শুধু তখনই বাড়ে যখন এই সেশনে ক্যাম্পেইনটি আগে দেখা হয়নি —
     * রিফ্রেশ করলে বা ফিরে এলে conversion rate ফুলে যাওয়া ঠেকাতে।
     */
    public function recordVisit($campaignId): void
    {
        $campaignId = (int) $campaignId;

        if ($campaignId < 1) {
            return;
        }

        $sessionKey = 'campaign_seen_' . $campaignId;
        $isUnique   = !Session::has($sessionKey);

        if ($isUnique) {
            Session::put($sessionKey, now()->timestamp);
        }

        $this->increment($campaignId, [
            'visits'        => 1,
            'unique_visits' => $isUnique ? 1 : 0,
        ]);
    }

    /** ক্যাম্পেইন পেজ থেকে কার্টে যোগ */
    public function recordAddToCart($campaignId): void
    {
        $this->increment((int) $campaignId, ['add_to_carts' => 1]);
    }

    /** চেকআউট ফর্ম শুরু */
    public function recordCheckout($campaignId): void
    {
        $this->increment((int) $campaignId, ['checkouts' => 1]);
    }

    /** সফল অর্ডার + বিক্রির টাকা */
    public function recordOrder($campaignId, float $revenue = 0): void
    {
        $this->increment((int) $campaignId, [
            'orders'  => 1,
            'revenue' => $revenue,
        ]);
    }

    /**
     * আজকের রো-তে কাউন্টারগুলো বাড়ায়।
     *
     * updateOrInsert + increment ব্যবহার করা হয়েছে যাতে একসাথে অনেক
     * ভিজিট এলে রেস কন্ডিশনে গণনা হারিয়ে না যায় (Eloquent-এ read-modify-write
     * করলে যেটা হতো)।
     */
    protected function increment(int $campaignId, array $counters): void
    {
        if ($campaignId < 1) {
            return;
        }

        try {
            if (!Schema::hasTable('campaign_stats')) {
                return;
            }

            $today = now()->toDateString();

            // রো না থাকলে তৈরি করি। unique index থাকায় দুটি রিকোয়েস্ট
            // একসাথে এলে দ্বিতীয়টি duplicate key পাবে — সেটা উপেক্ষা করাই ঠিক।
            try {
                DB::table('campaign_stats')->insertOrIgnore([
                    'campaign_id' => $campaignId,
                    'stat_date'   => $today,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            } catch (\Throwable $e) {
                // insertOrIgnore ব্যর্থ হলেও নিচের increment চেষ্টা করা যায়
            }

            $query = DB::table('campaign_stats')
                ->where('campaign_id', $campaignId)
                ->where('stat_date', $today);

            $updates = [];

            foreach ($counters as $column => $amount) {
                if ($amount > 0) {
                    $updates[$column] = DB::raw('`' . $column . '` + ' . (float) $amount);
                }
            }

            if ($updates) {
                $updates['updated_at'] = now();
                $query->update($updates);
            }
        } catch (\Throwable $e) {
            Log::warning('Campaign analytics increment failed: ' . $e->getMessage());
        }
    }

    /**
     * অ্যাডমিন রিপোর্টের জন্য একটি ক্যাম্পেইনের সারসংক্ষেপ।
     *
     * @param  int  $days  গত কত দিনের হিসাব
     */
    public function summary($campaignId, int $days = 30): array
    {
        $empty = [
            'visits' => 0, 'unique_visits' => 0, 'add_to_carts' => 0,
            'checkouts' => 0, 'orders' => 0, 'revenue' => 0.0,
            'conversion_rate' => 0.0, 'cart_rate' => 0.0, 'aov' => 0.0,
        ];

        try {
            if (!Schema::hasTable('campaign_stats')) {
                return $empty;
            }

            $row = DB::table('campaign_stats')
                ->where('campaign_id', (int) $campaignId)
                ->where('stat_date', '>=', now()->subDays($days)->toDateString())
                ->selectRaw('
                    COALESCE(SUM(visits),0) as visits,
                    COALESCE(SUM(unique_visits),0) as unique_visits,
                    COALESCE(SUM(add_to_carts),0) as add_to_carts,
                    COALESCE(SUM(checkouts),0) as checkouts,
                    COALESCE(SUM(orders),0) as orders,
                    COALESCE(SUM(revenue),0) as revenue
                ')
                ->first();

            if (!$row) {
                return $empty;
            }

            return $this->withRates([
                'visits'        => (int) $row->visits,
                'unique_visits' => (int) $row->unique_visits,
                'add_to_carts'  => (int) $row->add_to_carts,
                'checkouts'     => (int) $row->checkouts,
                'orders'        => (int) $row->orders,
                'revenue'       => (float) $row->revenue,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Campaign analytics summary failed: ' . $e->getMessage());

            return $empty;
        }
    }

    /**
     * সব ক্যাম্পেইনের সারসংক্ষেপ একসাথে — লিস্ট পেজে N+1 কুয়েরি এড়াতে।
     *
     * @return array<int, array>  campaign_id => summary
     */
    public function summaryForAll(int $days = 30): array
    {
        try {
            if (!Schema::hasTable('campaign_stats')) {
                return [];
            }

            $rows = DB::table('campaign_stats')
                ->where('stat_date', '>=', now()->subDays($days)->toDateString())
                ->groupBy('campaign_id')
                ->selectRaw('
                    campaign_id,
                    COALESCE(SUM(visits),0) as visits,
                    COALESCE(SUM(unique_visits),0) as unique_visits,
                    COALESCE(SUM(add_to_carts),0) as add_to_carts,
                    COALESCE(SUM(checkouts),0) as checkouts,
                    COALESCE(SUM(orders),0) as orders,
                    COALESCE(SUM(revenue),0) as revenue
                ')
                ->get();

            $out = [];

            foreach ($rows as $row) {
                $out[(int) $row->campaign_id] = $this->withRates([
                    'visits'        => (int) $row->visits,
                    'unique_visits' => (int) $row->unique_visits,
                    'add_to_carts'  => (int) $row->add_to_carts,
                    'checkouts'     => (int) $row->checkouts,
                    'orders'        => (int) $row->orders,
                    'revenue'       => (float) $row->revenue,
                ]);
            }

            return $out;
        } catch (\Throwable $e) {
            Log::warning('Campaign analytics bulk summary failed: ' . $e->getMessage());

            return [];
        }
    }

    /** দিন-ভিত্তিক সারি (চার্ট/টেবিলের জন্য) */
    public function daily($campaignId, int $days = 30)
    {
        try {
            if (!Schema::hasTable('campaign_stats')) {
                return collect();
            }

            return CampaignStat::where('campaign_id', (int) $campaignId)
                ->where('stat_date', '>=', now()->subDays($days)->toDateString())
                ->orderBy('stat_date')
                ->get();
        } catch (\Throwable $e) {
            return collect();
        }
    }

    /**
     * কনভার্শন রেট ইউনিক ভিজিটের উপর হিসাব করা হয় — মোট ভিজিটে করলে
     * রিফ্রেশের কারণে রেট কৃত্রিমভাবে কমে যেত। ইউনিক শূন্য হলে (পুরনো
     * ডেটা) মোট ভিজিটে ফিরে যাই।
     */
    protected function withRates(array $s): array
    {
        $base = $s['unique_visits'] > 0 ? $s['unique_visits'] : $s['visits'];

        $s['conversion_rate'] = $base > 0 ? round(($s['orders'] / $base) * 100, 2) : 0.0;
        $s['cart_rate']       = $base > 0 ? round(($s['add_to_carts'] / $base) * 100, 2) : 0.0;
        $s['aov']             = $s['orders'] > 0 ? round($s['revenue'] / $s['orders'], 2) : 0.0;

        return $s;
    }
}
