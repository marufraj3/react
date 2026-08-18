<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\CouponUsage;
use Carbon\Carbon;
use Cart;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Session;

/**
 * কুপন যাচাই ও প্রয়োগের একক উৎস (single source of truth)।
 *
 * আগে কুপনের লজিক শুধু ShoppingController@applyCoupon-এ ছিল এবং সেটি
 * redirect-ভিত্তিক ছিল, তাই ক্যাম্পেইন/বিল্ডার পেজ (যেখানে কোনো রিলোড হয় না)
 * কুপন ব্যবহার করতে পারত না। এখানে লজিকটা আলাদা করে আনা হয়েছে যাতে
 * সাধারণ চেকআউট (redirect) আর ক্যাম্পেইন চেকআউট (AJAX/JSON) — দুটোই
 * হুবহু একই নিয়ম মেনে চলে।
 */
class CouponService
{
    /**
     * কুপন কোড যাচাই করে সেশনে বসায়।
     *
     * @return array{ok: bool, message: string, discount: float, code: ?string}
     */
    public function apply(?string $code, ?string $phone = null): array
    {
        $code = trim((string) $code);

        if ($code === '') {
            return $this->fail('কুপন কোডটি লিখুন।');
        }

        $coupon = Coupon::where('code', $code)->where('status', 1)->first();

        if (!$coupon) {
            return $this->fail('কুপন কোডটি সঠিক নয়।');
        }

        $today = Carbon::now()->format('Y-m-d');

        if (($coupon->valid_from && $today < $coupon->valid_from)
            || ($coupon->valid_to && $today > $coupon->valid_to)) {
            return $this->fail('কুপনটির মেয়াদ শেষ অথবা এখনো শুরু হয়নি।');
        }

        $subtotal = $this->subtotal();

        if ($coupon->min_purchase && $subtotal < (float) $coupon->min_purchase) {
            return $this->fail(
                'এই কুপন ব্যবহারে কমপক্ষে ৳' . number_format((float) $coupon->min_purchase, 0) . ' এর কেনাকাটা প্রয়োজন।'
            );
        }

        if ($limitMessage = $this->limitViolation($coupon, $phone)) {
            return $this->fail($limitMessage);
        }

        $discount = $this->discountFor($coupon, $subtotal);

        Session::put('coupon_code', $coupon->code);
        Session::put('discount', $discount);

        return [
            'ok'       => true,
            'message'  => 'কুপন প্রয়োগ হয়েছে! আপনি ৳' . number_format($discount, 0) . ' ছাড় পেলেন।',
            'discount' => $discount,
            'code'     => $coupon->code,
        ];
    }

    /**
     * সেশন থেকে কুপন সরিয়ে দেয়।
     *
     * @return array{ok: bool, message: string, discount: float, code: null}
     */
    public function remove(): array
    {
        Session::forget(['coupon_code', 'discount']);

        return [
            'ok'       => true,
            'message'  => 'কুপন সরিয়ে ফেলা হয়েছে।',
            'discount' => 0.0,
            'code'     => null,
        ];
    }

    /**
     * কার্ট বদলালে (qty বাড়া/কমা, প্রোডাক্ট পরিবর্তন) আগের ডিসকাউন্ট আর
     * বৈধ না-ও থাকতে পারে — যেমন min_purchase এর নিচে নেমে যাওয়া, অথবা
     * percent কুপনে অ্যামাউন্ট বদলে যাওয়া। এই মেথড সেশনের কুপনটি আবার
     * যাচাই করে ডিসকাউন্ট ঠিক করে, প্রয়োজনে কুপন বাতিল করে দেয়।
     */
    public function revalidate(): void
    {
        $code = Session::get('coupon_code');

        if (!$code) {
            // কুপন নেই অথচ পুরনো discount সেশনে পড়ে আছে — পরিষ্কার করি।
            if (Session::has('discount')) {
                Session::forget('discount');
            }

            return;
        }

        $coupon = Coupon::where('code', $code)->where('status', 1)->first();
        $today  = Carbon::now()->format('Y-m-d');

        $expired = $coupon
            && (($coupon->valid_from && $today < $coupon->valid_from)
                || ($coupon->valid_to && $today > $coupon->valid_to));

        if (!$coupon || $expired) {
            Session::forget(['coupon_code', 'discount']);

            return;
        }

        $subtotal = $this->subtotal();

        if ($coupon->min_purchase && $subtotal < (float) $coupon->min_purchase) {
            Session::forget(['coupon_code', 'discount']);

            return;
        }

        Session::put('discount', $this->discountFor($coupon, $subtotal));
    }

    /**
     * ব্যবহারের সীমা অতিক্রম হয়েছে কিনা — হলে বাংলা মেসেজ, নাহলে null।
     *
     * দুই ধরনের সীমা:
     *  - usage_limit: কুপনটি সব মিলিয়ে কতবার ব্যবহার করা যাবে
     *  - usage_limit_per_customer: একই ফোন নম্বর কতবার ব্যবহার করতে পারবে
     *
     * null বা 0 মানে সীমা নেই (পুরনো কুপনগুলোর আচরণ অপরিবর্তিত থাকে)।
     */
    protected function limitViolation(Coupon $coupon, ?string $phone): ?string
    {
        try {
            $totalLimit = (int) ($coupon->usage_limit ?? 0);

            if ($totalLimit > 0 && (int) ($coupon->used_count ?? 0) >= $totalLimit) {
                return 'এই কুপনটির ব্যবহারের সীমা শেষ হয়ে গেছে।';
            }

            $perCustomer = (int) ($coupon->usage_limit_per_customer ?? 0);
            $phone       = $this->normalizePhone($phone);

            if ($perCustomer > 0 && $phone !== null && Schema::hasTable('coupon_usages')) {
                $used = CouponUsage::where('coupon_id', $coupon->id)
                    ->where('phone', $phone)
                    ->count();

                if ($used >= $perCustomer) {
                    return 'আপনি এই কুপনটি ইতিমধ্যে ব্যবহার করেছেন।';
                }
            }

            return null;
        } catch (\Throwable $e) {
            // সীমা যাচাই ব্যর্থ হলে কুপন আটকাবো না — বিক্রি হারানোর চেয়ে
            // একটা বাড়তি ছাড় দেওয়াই ভালো। কিন্তু লগে রেখে দিই।
            Log::warning('Coupon limit check failed: ' . $e->getMessage());

            return null;
        }
    }

    /**
     * অর্ডার সম্পন্ন হলে কুপনের ব্যবহার গোনা হয়।
     *
     * গুরুত্বপূর্ণ: গণনা এখানে হয়, apply()-তে নয়। কেউ কুপন বসিয়ে অর্ডার
     * না করলে সেটা "ব্যবহার" নয় — নাহলে কার্টে কুপন বসিয়ে রেখে দিলেই
     * সীমা ফুরিয়ে যেত।
     */
    public function recordUsage(?string $code, ?string $phone, $orderId = null, $customerId = null, float $discount = 0): void
    {
        try {
            $code = trim((string) $code);

            if ($code === '') {
                return;
            }

            $coupon = Coupon::where('code', $code)->first();

            if (!$coupon) {
                return;
            }

            // race condition এড়াতে atomic increment
            DB::table('coupons')->where('id', $coupon->id)->increment('used_count');

            if (Schema::hasTable('coupon_usages')) {
                CouponUsage::create([
                    'coupon_id'   => $coupon->id,
                    'code'        => $coupon->code,
                    'phone'       => $this->normalizePhone($phone),
                    'order_id'    => $orderId,
                    'customer_id' => $customerId,
                    'discount'    => $discount,
                ]);
            }
        } catch (\Throwable $e) {
            // অর্ডার হয়ে গেছে — শুধু হিসাব রাখতে ব্যর্থ হয়েছি বলে
            // কাস্টমারকে এরর দেখানোর কোনো মানে নেই।
            Log::warning('Coupon usage recording failed: ' . $e->getMessage());
        }
    }

    /**
     * ফোন নম্বর একরকম করে রাখি, নাহলে "01712-345678" আর "8801712345678"
     * আলাদা কাস্টমার হিসেবে গোনা হবে এবং প্রতি-কাস্টমার সীমা ফাঁকি দেওয়া যাবে।
     */
    protected function normalizePhone(?string $phone): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $phone);

        if ($digits === '') {
            return null;
        }

        // 8801712345678 / 8801712345678 → 01712345678
        if (strlen($digits) > 11 && str_starts_with($digits, '880')) {
            $digits = '0' . substr($digits, 3);
        }

        return $digits;
    }

    /**
     * ডিসকাউন্ট কখনোই সাবটোটালের চেয়ে বেশি হবে না — না হলে গ্র্যান্ড টোটাল
     * ঋণাত্মক হয়ে যেতে পারে।
     */
    protected function discountFor(Coupon $coupon, float $subtotal): float
    {
        $discount = $coupon->type === 'percent'
            ? $subtotal * ((float) $coupon->value / 100)
            : (float) $coupon->value;

        return round(min($discount, $subtotal), 2);
    }

    /**
     * Cart::subtotal() ফরম্যাট করা স্ট্রিং দেয় (যেমন "1,200.00")।
     */
    public function subtotal(): float
    {
        return (float) preg_replace('/[^\d.]/', '', Cart::instance('shopping')->subtotal());
    }

    /**
     * @return array{ok: bool, message: string, discount: float, code: null}
     */
    protected function fail(string $message): array
    {
        return [
            'ok'       => false,
            'message'  => $message,
            'discount' => 0.0,
            'code'     => null,
        ];
    }
}
