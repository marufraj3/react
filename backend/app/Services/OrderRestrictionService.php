<?php

namespace App\Services;

use App\Models\GeneralSetting;
use App\Models\Order;
use App\Models\OrderRestrictionWhitelist;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * অর্ডার রেস্ট্রিকশন v2।
 *
 * v1 ফোন নম্বরের পাশাপাশি IP ঠিকানা দিয়েও গণনা করত। বাংলাদেশে মোবাইল
 * অপারেটরের CGNAT এবং অফিস/হোস্টেলের শেয়ার্ড ওয়াইফাইয়ে বহু আলাদা কাস্টমার
 * একই পাবলিক IP থেকে আসে — ফলে একজনের অর্ডার সম্পূর্ণ অপরিচিত আরেকজনের
 * অর্ডার আটকে দিত। তাই v2-তে IP ম্যাচিং পুরোপুরি বাদ।
 *
 * v2-তে যা যোগ হলো:
 *   • শুধু ফোন নম্বর দিয়ে গণনা
 *   • স্পষ্ট অন/অফ সুইচ (general_settings.order_limit_enabled) — ডিফল্ট বন্ধ
 *   • হোয়াইটলিস্ট — পাইকারি ক্রেতার নম্বরে কোনো সীমা নেই
 *   • বাতিল হওয়া অর্ডার গণনায় ধরা হয় না
 */
class OrderRestrictionService
{
    /** বাতিল/রিটার্ন হওয়া অর্ডার সীমার হিসাবে ধরা হবে না */
    protected const EXCLUDED_ORDER_STATUSES = [5, 6, 7];

    /**
     * সীমা অতিক্রম হলে বাংলা এরর মেসেজ, নাহলে null।
     *
     * দ্বিতীয় প্যারামিটারটি v1-এর সাথে সামঞ্জস্য রাখতে রয়ে গেছে কিন্তু
     * ইচ্ছাকৃতভাবে ব্যবহার করা হয় না।
     */
    public function violationMessage(?string $phone, ?string $ipAddress = null): ?string
    {
        try {
            [$hours, $maxOrders] = $this->limits();

            if ($hours === null || $maxOrders === null) {
                return null; // ফিচার বন্ধ
            }

            $phone = trim((string) $phone);

            if ($phone === '') {
                return null; // ফোন ছাড়া গণনার উপায় নেই
            }

            if ($this->isWhitelisted($phone)) {
                return null;
            }

            $count = $this->recentOrderCount($phone, $hours);

            return $count >= $maxOrders ? $this->message($maxOrders, $hours) : null;
        } catch (\Throwable $e) {
            // চেক ব্যর্থ হলে অর্ডার আটকানো যাবে না — বিক্রি বন্ধ হওয়ার চেয়ে
            // একটা বাড়তি অর্ডার নেওয়াই ভালো।
            Log::error('Order restriction check failed: ' . $e->getMessage());

            return null;
        }
    }

    /** ফিচারটি এই মুহূর্তে চালু আছে কিনা */
    public function isEnabled(): bool
    {
        [$hours, $maxOrders] = $this->limits();

        return $hours !== null && $maxOrders !== null;
    }

    public function isWhitelisted(?string $phone): bool
    {
        try {
            if (!Schema::hasTable('order_restriction_whitelists')) {
                return false;
            }

            return OrderRestrictionWhitelist::has($phone);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * নির্দিষ্ট সময়সীমার মধ্যে এই নম্বর থেকে কতগুলো কার্যকর অর্ডার হয়েছে।
     */
    public function recentOrderCount(string $phone, int $hours): int
    {
        $since      = Carbon::now()->subHours($hours);
        $normalized = OrderRestrictionWhitelist::normalize($phone);

        $query = Order::where('created_at', '>=', $since)
            ->whereHas('shipping', function ($q) use ($phone, $normalized) {
                $q->where('phone', $phone);

                // +88 সহ/ছাড়া লেখা নম্বরও একই কাস্টমার হিসেবে গোনা হয়
                if ($normalized !== '') {
                    $q->orWhere('phone', 'like', '%' . $normalized);
                }
            });

        if (Schema::hasColumn('orders', 'order_status')) {
            $query->whereNotIn('order_status', self::EXCLUDED_ORDER_STATUSES);
        }

        return $query->count();
    }

    /**
     * @return array{0: int|null, 1: int|null} [hours, maxOrders]
     */
    private function limits(): array
    {
        $setting = GeneralSetting::where('status', 1)->first() ?: GeneralSetting::first();

        if (!$setting) {
            return [null, null];
        }

        // মাস্টার সুইচ — কলামটি থাকলে সেটিই চূড়ান্ত।
        // কলাম না থাকলে (মাইগ্রেশন চলেনি) ফিচারটি বন্ধ ধরি, কারণ পুরনো
        // ইনস্টলে ডিফল্ট 48 ঘণ্টা / 2 অর্ডার সেট করা থাকে — না বুঝে হঠাৎ
        // enforcement চালু হয়ে গেলে বিক্রি আটকে যাবে।
        if (!Schema::hasColumn('general_settings', 'order_limit_enabled')) {
            return [null, null];
        }

        if (!(bool) ($setting->order_limit_enabled ?? false)) {
            return [null, null];
        }

        $hours     = (int) ($setting->order_limit_time ?? 0);
        $maxOrders = (int) ($setting->order_limit_qty ?? 0);

        if ($hours < 1 || $maxOrders < 1) {
            return [null, null];
        }

        return [$hours, $maxOrders];
    }

    private function message(int $maxOrders, int $hours): string
    {
        return 'আপনি ইতিমধ্যে গত ' . $this->bn($hours) . ' ঘণ্টায় ' . $this->bn($maxOrders)
            . 'টি অর্ডার করেছেন। নতুন অর্ডারের জন্য কিছুক্ষণ পরে চেষ্টা করুন অথবা আমাদের সাথে যোগাযোগ করুন।';
    }

    private function bn(int $number): string
    {
        return str_replace(
            ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'],
            ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'],
            (string) $number
        );
    }
}
