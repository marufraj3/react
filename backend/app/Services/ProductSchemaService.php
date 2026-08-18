<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Review;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Google-এর জন্য Product + AggregateRating + Review স্ট্রাকচার্ড ডেটা (JSON-LD)।
 *
 * এতে সার্চ রেজাল্টে দাম, স্টক অবস্থা আর তারকা রেটিং সরাসরি দেখা যায় —
 * বিজ্ঞাপনের খরচ ছাড়াই ক্লিক বাড়ানোর সবচেয়ে সস্তা উপায়।
 *
 * নীতি: এটি কখনোই পেজ ভাঙতে পারবে না। যেকোনো সমস্যায় খালি স্ট্রিং ফেরত যায়।
 */
class ProductSchemaService
{
    /** রিভিউয়ের সর্বোচ্চ কতটি JSON-LD-তে যাবে (পেজ ভারী না করতে) */
    protected const MAX_REVIEWS = 10;

    /**
     * প্রোডাক্ট ডিটেইলস পেজের জন্য সম্পূর্ণ <script> ট্যাগ।
     */
    public function productScript(Product $product, $reviews = null, ?string $url = null): string
    {
        try {
            $data = $this->productSchema($product, $reviews, $url);

            return $data ? $this->wrap($data) : '';
        } catch (\Throwable $e) {
            Log::warning('Product JSON-LD failed: ' . $e->getMessage());

            return '';
        }
    }

    /**
     * ক্যাম্পেইন ল্যান্ডিং পেজের জন্য — ItemList নয়, প্রধান প্রোডাক্টটিকেই
     * পেজের বিষয়বস্তু ধরা হয় (ক্যাম্পেইন = এক প্রোডাক্টের চেকআউট ফ্লো)।
     */
    public function campaignScript($campaign, $products, ?string $url = null): string
    {
        try {
            $product = $products instanceof Product ? $products : collect($products)->first();

            if (!$product) {
                return '';
            }

            $data = $this->productSchema($product, null, $url);

            if (!$data) {
                return '';
            }

            // ক্যাম্পেইনের নিজস্ব নাম/বর্ণনা থাকলে সেটিই বেশি প্রাসঙ্গিক
            if (!empty($campaign->name)) {
                $data['name'] = $this->clean($campaign->name);
            }

            $campaignDescription = strip_tags((string) ($campaign->short_description ?? $campaign->description ?? ''));

            if ($campaignDescription !== '') {
                $data['description'] = $this->clean($campaignDescription, 500);
            }

            return $this->wrap($data);
        } catch (\Throwable $e) {
            Log::warning('Campaign JSON-LD failed: ' . $e->getMessage());

            return '';
        }
    }

    /**
     * Product স্কিমার অ্যারে তৈরি করে।
     */
    public function productSchema(Product $product, $reviews = null, ?string $url = null): array
    {
        $url   = $url ?: url('product/' . $product->slug);
        $price = (float) ($product->new_price ?? $product->old_price ?? 0);

        if ($price <= 0) {
            return []; // দাম ছাড়া Google Product স্কিমা গ্রহণ করে না
        }

        $data = [
            '@context'    => 'https://schema.org/',
            '@type'       => 'Product',
            'name'        => $this->clean($product->name),
            'url'         => $url,
            'description' => $this->clean(strip_tags((string) ($product->meta_description ?? $product->description ?? $product->name)), 500),
        ];

        $image = $this->imageUrl($product);

        if ($image) {
            $data['image'] = [$image];
        }

        // products টেবিলে sku কলাম নেই, product_code আছে — দুটোই দেখি
        $sku = $product->product_code ?? $product->sku ?? null;

        if ($sku) {
            $data['sku'] = (string) $sku;
        }

        $data['mpn'] = (string) $product->id;

        $brand = $this->brandName($product);

        if ($brand) {
            $data['brand'] = ['@type' => 'Brand', 'name' => $brand];
        }

        $data['offers'] = $this->offers($product, $price, $url);

        $rating = $this->aggregateRating($product);

        if ($rating) {
            $data['aggregateRating'] = $rating;
        }

        $reviewNodes = $this->reviewNodes($product, $reviews);

        if ($reviewNodes) {
            $data['review'] = $reviewNodes;
        }

        return $data;
    }

    /**
     * দাম, মুদ্রা, স্টক অবস্থা ও অফারের মেয়াদ।
     */
    protected function offers(Product $product, float $price, string $url): array
    {
        return [
            '@type'           => 'Offer',
            'url'             => $url,
            'priceCurrency'   => 'BDT',
            'price'           => number_format($price, 2, '.', ''),
            // Google-এর সুপারিশ: দাম কতদিন কার্যকর তা জানানো
            'priceValidUntil' => now()->addYear()->format('Y-m-d'),
            'availability'    => $this->availability($product),
            'itemCondition'   => 'https://schema.org/NewCondition',
        ];
    }

    /**
     * স্টক অবস্থা — ভ্যারিয়েন্ট থাকলে অন্তত একটিতে স্টক আছে কিনা দেখা হয়।
     */
    protected function availability(Product $product): string
    {
        $inStock = 'https://schema.org/InStock';
        $out     = 'https://schema.org/OutOfStock';

        try {
            if ((int) $product->status !== 1) {
                return $out;
            }

            if ((int) ($product->is_digital ?? 0) === 1) {
                return $inStock; // ডিজিটাল প্রোডাক্ট কখনো ফুরায় না
            }

            $variants = $product->relationLoaded('variantPrices')
                ? $product->variantPrices
                : $product->variantPrices()->get();

            if ($variants && $variants->count() > 0) {
                $any = $variants->first(fn ($v) => $v->stock === null || (int) $v->stock > 0);

                return $any ? $inStock : $out;
            }

            if (!Schema::hasColumn('products', 'stock')) {
                return $inStock;
            }

            return (int) $product->stock > 0 ? $inStock : $out;
        } catch (\Throwable $e) {
            return $inStock;
        }
    }

    /**
     * গড় রেটিং ও মোট রিভিউ সংখ্যা।
     *
     * reviews.ratting কলামটি varchar — তাই SQL AVG-এর বদলে PHP-তে cast করে
     * হিসাব করা হচ্ছে, যাতে '5' বা '4.5' দুটোই ঠিকভাবে গোনা হয়।
     */
    protected function aggregateRating(Product $product): ?array
    {
        try {
            if (!Schema::hasTable('reviews')) {
                return null;
            }

            $ratings = Review::where('product_id', $product->id)
                ->where('status', 'active')
                ->pluck('ratting')
                ->map(fn ($r) => (float) $r)
                ->filter(fn ($r) => $r > 0 && $r <= 5)
                ->values();

            if ($ratings->isEmpty()) {
                return null; // ভুয়া রেটিং দেখানোর চেয়ে কিছু না দেখানোই ভালো
            }

            return [
                '@type'       => 'AggregateRating',
                'ratingValue' => (string) round($ratings->avg(), 1),
                'reviewCount' => (string) $ratings->count(),
                'bestRating'  => '5',
                'worstRating' => '1',
            ];
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * সর্বোচ্চ কয়েকটি রিভিউ Review নোড হিসেবে।
     */
    protected function reviewNodes(Product $product, $reviews = null): array
    {
        try {
            if (!Schema::hasTable('reviews')) {
                return [];
            }

            $collection = $reviews;

            if (!$collection) {
                $collection = Review::where('product_id', $product->id)
                    ->where('status', 'active')
                    ->latest()
                    ->limit(self::MAX_REVIEWS)
                    ->get();
            }

            $nodes = [];

            foreach ($collection as $review) {
                $rating = (float) ($review->ratting ?? 0);
                $body   = trim(strip_tags((string) ($review->review ?? '')));

                if ($rating <= 0 || $rating > 5 || $body === '') {
                    continue;
                }

                $nodes[] = [
                    '@type'         => 'Review',
                    'reviewRating'  => [
                        '@type'       => 'Rating',
                        'ratingValue' => (string) $rating,
                        'bestRating'  => '5',
                        'worstRating' => '1',
                    ],
                    'author'        => [
                        '@type' => 'Person',
                        'name'  => $this->clean($review->name ?: 'ক্রেতা', 60),
                    ],
                    'reviewBody'    => $this->clean($body, 300),
                    'datePublished' => optional($review->created_at)->format('Y-m-d'),
                ];

                if (count($nodes) >= self::MAX_REVIEWS) {
                    break;
                }
            }

            return $nodes;
        } catch (\Throwable $e) {
            return [];
        }
    }

    protected function imageUrl(Product $product): ?string
    {
        $path = optional($product->image)->image ?? null;

        return $path ? asset($path) : null;
    }

    protected function brandName(Product $product): ?string
    {
        try {
            $brand = $product->relationLoaded('brand') ? $product->brand : $product->brand()->first();

            return $brand ? $this->clean($brand->name ?? $brand->brandName ?? '', 60) : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** নিয়ন্ত্রণ-অক্ষর ও অতিরিক্ত স্পেস সরিয়ে দৈর্ঘ্য সীমিত করি */
    protected function clean(?string $value, int $limit = 160): string
    {
        $value = trim(preg_replace('/\s+/u', ' ', (string) $value));

        return Str::limit($value, $limit, '');
    }

    /**
     * JSON_UNESCAPED_UNICODE — বাংলা লেখা যেন \uXXXX না হয়ে পড়ে।
     * JSON_HEX_TAG স্ক্রিপ্ট ইনজেকশন ঠেকায় (</script> এখানে লেখা যাবে না)।
     */
    protected function wrap(array $data): string
    {
        $json = json_encode(
            $data,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
        );

        if ($json === false) {
            return '';
        }

        return '<script type="application/ld+json">' . $json . '</script>';
    }
}
