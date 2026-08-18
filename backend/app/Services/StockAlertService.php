<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariantPrice;
use App\Models\StockAlert;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * স্টক শেষ হয়ে গেলে অ্যাডমিনকে জানানো এবং প্রোডাক্ট স্বয়ংক্রিয়ভাবে
 * আউট-অফ-স্টক করে দেওয়া।
 *
 * নীতি: এই সার্ভিস কখনোই অর্ডার প্রক্রিয়া থামাবে না। অর্ডার সেভ হওয়ার
 * পরে ডাকা হয়, এবং সবকিছু try/catch-এ মোড়া — অ্যালার্ট তৈরি করতে ব্যর্থ
 * হলে শুধু লগ হবে।
 */
class StockAlertService
{
    /** ডিফল্ট থ্রেশহোল্ড — প্রোডাক্টে আলাদা করে সেট না থাকলে এটি ব্যবহার হয় */
    public const DEFAULT_LOW_STOCK_THRESHOLD = 3;

    /**
     * একটি অর্ডারের সব আইটেমের স্টক পরীক্ষা করে প্রয়োজনে অ্যালার্ট তৈরি করে।
     *
     * @param  iterable  $details  OrderDetails কালেকশন
     */
    public function checkOrderItems($details): void
    {
        try {
            if (!Schema::hasTable('stock_alerts')) {
                return;
            }

            foreach ($details as $row) {
                $variant = $row->variant_price_id
                    ? ProductVariantPrice::with(['size', 'color'])->find($row->variant_price_id)
                    : null;

                if ($variant) {
                    $this->checkVariant($variant);
                    continue;
                }

                if ($row->product_id) {
                    $this->checkProduct($row->product_id);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Stock alert check failed: ' . $e->getMessage());
        }
    }

    /**
     * এক ভ্যারিয়েন্টের স্টক দেখে অ্যালার্ট তৈরি/সমাধান করে।
     */
    public function checkVariant(ProductVariantPrice $variant): void
    {
        try {
            if ($variant->stock === null) {
                return; // স্টক ট্র্যাক করা হয় না এমন ভ্যারিয়েন্ট
            }

            $stock   = (int) $variant->stock;
            $product = $variant->product ?: Product::find($variant->product_id);

            if (!$product) {
                return;
            }

            $label = $this->variantLabel($variant);

            if ($stock < 1) {
                $this->raise($product, $variant->id, $label, 'out_of_stock', $stock);

                // সব ভ্যারিয়েন্ট শূন্য হলে প্রোডাক্টটাই আউট-অফ-স্টক
                $this->maybeDisableProduct($product);

                return;
            }

            if ($stock <= $this->thresholdFor($product)) {
                $this->raise($product, $variant->id, $label, 'low_stock', $stock);

                return;
            }

            // স্টক আবার ভরা হয়েছে — খোলা অ্যালার্ট বন্ধ করি
            $this->resolve($product->id, $variant->id);
        } catch (\Throwable $e) {
            Log::warning('Stock alert (variant) failed: ' . $e->getMessage());
        }
    }

    /**
     * ভ্যারিয়েন্টবিহীন সাধারণ প্রোডাক্টের স্টক পরীক্ষা।
     */
    public function checkProduct($product): void
    {
        try {
            $product = $product instanceof Product ? $product : Product::find($product);

            if (!$product || !Schema::hasColumn('products', 'stock')) {
                return;
            }

            $stock = (int) $product->stock;

            if ($stock < 1) {
                $this->raise($product, null, null, 'out_of_stock', $stock);
                $this->maybeDisableProduct($product);

                return;
            }

            if ($stock <= $this->thresholdFor($product)) {
                $this->raise($product, null, null, 'low_stock', $stock);

                return;
            }

            $this->resolve($product->id, null);
        } catch (\Throwable $e) {
            Log::warning('Stock alert (product) failed: ' . $e->getMessage());
        }
    }

    /**
     * অ্যালার্ট তৈরি করে — তবে একই ভ্যারিয়েন্টের খোলা অ্যালার্ট থাকলে
     * নতুন রো না বানিয়ে পুরনোটাই আপডেট করে। নাহলে একটা প্রোডাক্ট নিয়ে
     * শত শত ডুপ্লিকেট নোটিফিকেশন জমে যেত।
     */
    protected function raise(Product $product, $variantId, ?string $label, string $type, int $stock): void
    {
        $existing = StockAlert::open()
            ->where('product_id', $product->id)
            ->where('variant_price_id', $variantId)
            ->first();

        if ($existing) {
            // low_stock → out_of_stock এ গেলে আপগ্রেড করি, উল্টোটা নয়
            $upgrade = $existing->type === 'low_stock' && $type === 'out_of_stock';

            $existing->stock_left = $stock;

            if ($upgrade) {
                $existing->type    = $type;
                $existing->is_read = false; // নতুন করে নজরে আনি
            }

            $existing->save();

            return;
        }

        StockAlert::create([
            'product_id'       => $product->id,
            'variant_price_id' => $variantId,
            'product_name'     => $product->name,
            'variant_label'    => $label,
            'type'             => $type,
            'stock_left'       => $stock,
            'is_read'          => false,
        ]);
    }

    /** স্টক ফিরে এলে খোলা অ্যালার্ট বন্ধ করে */
    protected function resolve($productId, $variantId): void
    {
        StockAlert::open()
            ->where('product_id', $productId)
            ->where('variant_price_id', $variantId)
            ->update(['resolved_at' => now()]);
    }

    /**
     * সব ভ্যারিয়েন্টের স্টক শূন্য হলে প্রোডাক্টটি নিষ্ক্রিয় করে দেয়।
     *
     * ভ্যারিয়েন্ট না থাকলে products.stock দেখে সিদ্ধান্ত নেয়।
     * এতে কাস্টমার আর এমন কিছু অর্ডার করতে পারবে না যা ডেলিভারি দেওয়া যাবে না।
     */
    public function maybeDisableProduct(Product $product): void
    {
        try {
            $hasVariants = ProductVariantPrice::where('product_id', $product->id)->exists();

            if ($hasVariants) {
                $anyInStock = ProductVariantPrice::where('product_id', $product->id)
                    ->where(function ($q) {
                        $q->whereNull('stock')->orWhere('stock', '>', 0);
                    })
                    ->exists();

                if ($anyInStock) {
                    return; // অন্তত একটা অপশন এখনো পাওয়া যাচ্ছে
                }
            } elseif (!Schema::hasColumn('products', 'stock') || (int) $product->stock > 0) {
                return;
            }

            if ((int) $product->status === 1) {
                $product->status = 0;
                $product->save();

                Log::info('Product auto-disabled (out of stock): #' . $product->id . ' ' . $product->name);
            }
        } catch (\Throwable $e) {
            Log::warning('Auto out-of-stock failed: ' . $e->getMessage());
        }
    }

    /** খোলা ও অপঠিত অ্যালার্টের সংখ্যা — অ্যাডমিন সাইডবারের ব্যাজের জন্য */
    public function unreadCount(): int
    {
        try {
            if (!Schema::hasTable('stock_alerts')) {
                return 0;
            }

            return StockAlert::open()->where('is_read', false)->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    protected function thresholdFor(Product $product): int
    {
        $threshold = Schema::hasColumn('products', 'low_stock_threshold')
            ? (int) ($product->low_stock_threshold ?? 0)
            : 0;

        return $threshold > 0 ? $threshold : self::DEFAULT_LOW_STOCK_THRESHOLD;
    }

    /** "লাল / XL" ধরনের পাঠযোগ্য লেবেল */
    protected function variantLabel(ProductVariantPrice $variant): ?string
    {
        $parts = [];

        if ($variant->color) {
            $parts[] = $variant->color->getDisplayName() ?? $variant->color->colorName ?? null;
        }

        if ($variant->size) {
            $parts[] = $variant->size->sizeName ?? $variant->size->name ?? null;
        }

        $parts = array_filter($parts);

        return $parts ? implode(' / ', $parts) : ($variant->sku ?: null);
    }
}
