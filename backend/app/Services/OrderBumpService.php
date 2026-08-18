<?php

namespace App\Services;

use App\Models\OrderBump;
use Cart;
use Illuminate\Support\Facades\Log;

/**
 * চেকআউটে কোন অর্ডার বাম্প দেখানো হবে সেই সিদ্ধান্ত ও কার্টে যোগ করার লজিক।
 */
class OrderBumpService
{
    /**
     * কার্টে যোগ হওয়া বাম্প আইটেম চেনার জন্য cart option key।
     */
    public const OPTION_KEY = 'order_bump_id';

    /**
     * এই মুহূর্তে দেখানোর মতো বাম্পগুলো।
     *
     * বাদ দেওয়ার নিয়ম:
     *  - প্রোডাক্টটি ইতিমধ্যে কার্টে থাকলে (নিজের জিনিস নিজেকে বেচার মানে নেই)
     *  - কার্টের সাবটোটাল min_cart_amount এর কম হলে
     *  - প্রোডাক্ট ডিলিট/নিষ্ক্রিয় হয়ে গেলে
     */
    public function availableFor(?int $campaignId = null, int $limit = 2)
    {
        try {
            $cartProductIds = Cart::instance('shopping')->content()->pluck('id')->all();
            $subtotal = (float) preg_replace('/[^\d.]/', '', Cart::instance('shopping')->subtotal());

            return OrderBump::with('product.image')
                ->where('status', 1)
                // null campaign_id = সব পেজের জন্য, নাহলে শুধু এই ক্যাম্পেইনে
                ->where(function ($query) use ($campaignId) {
                    $query->whereNull('campaign_id');
                    if ($campaignId) {
                        $query->orWhere('campaign_id', $campaignId);
                    }
                })
                ->whereNotIn('product_id', $cartProductIds ?: [0])
                ->where(function ($query) use ($subtotal) {
                    $query->whereNull('min_cart_amount')
                        ->orWhere('min_cart_amount', '<=', $subtotal);
                })
                ->orderBy('sort_order')
                ->orderByDesc('id')
                ->limit($limit)
                ->get()
                // প্রোডাক্ট ডিলিট হয়ে গেলে বাম্পটাও অচল
                ->filter(fn ($bump) => $bump->product !== null)
                ->values();
        } catch (\Throwable $e) {
            // বাম্প দেখানো ঐচ্ছিক — কোনো কারণে ব্যর্থ হলে চেকআউট থামবে না।
            Log::warning('Order bump lookup failed: ' . $e->getMessage());

            return collect();
        }
    }

    /**
     * বাম্প প্রোডাক্টটি ছাড়ের দামে কার্টে যোগ করে।
     *
     * @return array{ok: bool, message: string}
     */
    public function addToCart(int $bumpId): array
    {
        $bump = OrderBump::with('product.image')->where('status', 1)->find($bumpId);

        if (!$bump || !$bump->product) {
            return ['ok' => false, 'message' => 'অফারটি এখন আর পাওয়া যাচ্ছে না।'];
        }

        $alreadyInCart = Cart::instance('shopping')->content()
            ->contains(fn ($item) => (int) $item->id === (int) $bump->product_id);

        if ($alreadyInCart) {
            return ['ok' => false, 'message' => 'পণ্যটি ইতিমধ্যে আপনার কার্টে আছে।'];
        }

        $product = $bump->product;

        Cart::instance('shopping')->add([
            'id'    => $product->id,
            'name'  => $product->name,
            'qty'   => 1,
            'price' => $bump->offerPrice(),
            'options' => [
                'slug'           => $product->slug,
                'image'          => optional($product->image)->image ?? 'public/uploads/default.webp',
                'old_price'      => (float) ($product->old_price ?? 0),
                'purchase_price' => (float) ($product->purchase_price ?? 0),
                'product_size'   => null,
                'product_color'  => null,
                'size_id'        => null,
                'color_id'       => null,
                'variant_price_id' => null,
                'pro_unit'       => $product->pro_unit ?? null,
                'advance_amount' => (float) ($product->advance_amount ?? 0),
                'is_digital'     => (int) ($product->is_digital ?? 0),
                'free_delivery'  => (int) ($product->free_delivery ?? 0),

                // এই আইটেমটি অর্ডার বাম্প থেকে এসেছে — রিপোর্টিং ও
                // ডুপ্লিকেট বাম্প এড়ানোর জন্য চিহ্ন রাখি।
                self::OPTION_KEY => $bump->id,
            ],
        ]);

        $bump->increment('conversions');

        return [
            'ok'      => true,
            'message' => 'অফারটি যোগ হয়েছে! আপনি ৳' . number_format($bump->savings(), 0) . ' সাশ্রয় করলেন।',
        ];
    }

    /**
     * ইম্প্রেশন গোনা — কোন অফার কতবার দেখানো হলো।
     * ব্যর্থ হলে চুপচাপ পাশ কাটিয়ে যায়, এটি নিছক পরিসংখ্যান।
     */
    public function recordImpressions($bumps): void
    {
        try {
            $ids = collect($bumps)->pluck('id')->filter()->all();

            if ($ids) {
                OrderBump::whereIn('id', $ids)->increment('impressions');
            }
        } catch (\Throwable $e) {
            Log::warning('Order bump impression tracking failed: ' . $e->getMessage());
        }
    }
}
