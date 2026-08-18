<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Blog;
use App\Models\Category;
use App\Models\Complaint;
use App\Models\ContactMessage;
use App\Models\Coupon;
use App\Models\Customer;
use App\Models\DigitalDownload;
use App\Models\GeneralSetting;
use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Refund;
use App\Models\Review;
use App\Models\Shipping;
use App\Models\ShippingCharge;
use App\Models\Subcategory;
use App\Services\CouponService;
use App\Services\OrderRestrictionService;
use App\Services\StockAlertService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Storefront REST API (headless) for the React frontend.
 *
 * Laravel serves two things to the React app:
 *   1. GET  /api/v1/storefront/bootstrap  -> everything the storefront needs on load
 *   2. A few stateless JSON actions (order, review, complaint, contact, coupon)
 *
 * Responses are shaped to match src/types.ts in the React app so the client
 * stays thin (no DB-column mapping in JS).
 */
class StorefrontApiController extends Controller
{
    /* ------------------------------------------------------------------ *
     * Helpers
     * ------------------------------------------------------------------ */

    /** Pick the first non-null key from an Eloquent model or array. */
    private function pick($row, array $keys, $default = null)
    {
        foreach ($keys as $key) {
            if (is_array($row)) {
                if (array_key_exists($key, $row) && $row[$key] !== null) {
                    return $row[$key];
                }
                continue;
            }

            if ($row !== null && $row->{$key} !== null) {
                return $row->{$key};
            }
        }

        return $default;
    }

    /**
     * Turn a stored image path (e.g. "public/uploads/x.jpg" or an absolute URL)
     * into an absolute, browser-friendly URL.
     */
    private function absUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        $path = trim($path);

        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }

        return rtrim(url('/'), '/').'/'.ltrim($path, '/');
    }

    private function json($data, int $status = 200, string $message = 'ok')
    {
        return response()->json([
            'success' => $status >= 200 && $status < 300,
            'message' => $message,
            'data'    => $data,
        ], $status);
    }

    private function settingsPayload(): array
    {
        $s = GeneralSetting::orderBy('id', 'desc')->first();
        $p = fn (...$keys) => $this->pick($s, $keys);

        // Contact info (hotline, email, address, whatsapp) lives in the
        // `contacts` table, not in general_settings.
        $contact = \App\Models\Contact::where('status', 1)->first();

        return [
            'name'               => $p('name', 'shop_name', 'site_name', 'title') ?? 'Shop Genie',
            'white_logo'         => $this->absUrl($p('white_logo')),
            'dark_logo'          => $this->absUrl($p('dark_logo')),
            'favicon'            => $this->absUrl($p('favicon')),
            'hotline'            => $this->pick($contact, ['hotline', 'phone']),
            'email'              => $this->pick($contact, ['email', 'hotmail']),
            'address'            => $this->pick($contact, ['address']),
            'top_headline'       => $p('top_headline', 'headline'),
            'news_ticker_enabled'=> (bool) $p('news_ticker_enabled', 0),
            'checkout_note'      => $p('checkout_note'),
            'order_policy'       => $p('order_policy', 'policy'),
            'primary_color'      => $p('primary_color', 'theme_color') ?? '#111827',
            // Note: the production DB stores this as `secodery_color` (typo).
            'secondary_color'    => $p('secondary_color', 'secodery_color') ?? '#dc2626',
            'footer_about_text'  => $p('footer_about_text', 'footer_text'),
            'facebook_page'      => $p('facebook_page', 'facebook', 'facebook_link', 'facebook_page_username'),
            'whatsapp_number'    => $this->pick($contact, ['whatsapp']),
            'youtube_link'       => $p('youtube_link', 'youtube'),
            'tiktok_link'        => $p('tiktok_link', 'tiktok'),
            'instagram_link'     => $p('instagram_link', 'instagram'),
            'currency'           => $p('currency', 'currency_symbol') ?? '৳',
            'hot_deal_end_date'  => $p('hot_deal_end_date'),
            'flash_sale_end_date'=> $p('flash_sale_end_date'),
        ];
    }

    private function categoryPayload(Category $c): array
    {
        return [
            'id'            => (int) $c->id,
            'name'          => $c->name,
            'slug'          => $c->slug,
            'image'         => $this->absUrl($c->image ?? null),
            'icon'          => $c->icon ?? null,
            'front_view'    => $c->front_view ?? null,
            'status'        => (int) ($c->status ?? 1),
            'product_count' => $c->product_count ?? null,
        ];
    }

    private function subcategoryPayload($s, int $categoryId): array
    {
        return [
            'id'          => (int) $s->id,
            'category_id' => $categoryId,
            'name'        => $s->subcategoryName ?? $s->name ?? null,
            'slug'        => $s->slug ?? null,
            'status'      => 1,
        ];
    }

    private function bannerPayload(Banner $b): array
    {
        return [
            'id'          => (int) $b->id,
            'category_id' => (int) ($b->category_id ?? 0),
            'title'       => $this->pick($b, ['title', 'headline']),
            'subtitle'    => $this->pick($b, ['subtitle', 'sub_title']),
            'image'       => $this->absUrl($b->image ?? null),
            'link'        => $b->link ?? null,
            'status'      => (int) ($b->status ?? 1),
            // Laravel banners use category_id==1 for the hero slider.
            'position'    => (int) ($b->category_id ?? 0) === 1 ? 'hero' : 'middle_ad',
        ];
    }

    private function productPayload(Product $p): array
    {
        $g = fn (...$keys) => $this->pick($p, $keys);

        $rating = $g('ratting', 'rating');
        if ($rating === null) {
            $rating = (float) ($p->reviews()->where('status', 'approved')->avg('ratting') ?? 0);
        }

        $reviewsCount = (int) ($g('reviews_count') ?? 0);
        if ($reviewsCount <= 0) {
            $reviewsCount = (int) $p->reviews()->where('status', 'approved')->count();
        }

        $gallery = [];
        if ($p->relationLoaded('images')) {
            $gallery = $p->images->pluck('image')->filter()->values()->all();
        }

        $image = $g('image', 'photo') ?: ($gallery[0] ?? null);

        $colors = [];
        if ($p->relationLoaded('colors')) {
            $colors = $p->colors
                ->map(fn ($c) => $c->name ?? $c->colorName ?? $c->color_name ?? null)
                ->filter()->values()->all();
        }

        $sizes = [];
        if ($p->relationLoaded('sizes')) {
            $sizes = $p->sizes
                ->map(fn ($s) => $s->sizeName ?? $s->name ?? $s->size_name ?? null)
                ->filter()->values()->all();
        }

        $isDigital = (bool) $g('is_digital', 0);

        return [
            'id'                  => (int) $p->id,
            'name'                => $p->name,
            'slug'                => $p->slug,
            'category_id'         => (int) ($g('category_id') ?? 0),
            'subcategory_id'      => $g('subcategory_id') !== null ? (int) $g('subcategory_id') : null,
            'brand_id'            => $g('brand_id') !== null ? (int) $g('brand_id') : null,
            'product_code'        => $g('product_code', 'productcode', 'code') ?? ('P'.str_pad((string) $p->id, 4, '0', STR_PAD_LEFT)),
            'purchase_price'      => (float) ($g('purchase_price') ?? 0),
            'old_price'           => $g('old_price', 'previous_price') !== null ? (float) $g('old_price', 'previous_price') : null,
            'new_price'           => (float) ($g('new_price', 'sale_price', 'price') ?? 0),
            'reseller_price'      => $g('reseller_price') !== null ? (float) $g('reseller_price') : null,
            'stock'               => (int) ($g('stock') ?? 0),
            'product_type'        => $isDigital ? 'digital' : 'physical',
            'is_digital'          => $isDigital,
            'free_delivery'       => (bool) $g('free_delivery', 0),
            'digital_file'        => $g('digital_file', 'digital_file_path'),
            'download_limit'      => $g('download_limit'),
            'download_expire_days'=> $g('download_expire_days'),
            'pro_unit'            => $g('pro_unit', 'unit'),
            'pro_video'           => $g('pro_video', 'pro_video_path'),
            'description'         => $g('description') ?? '',
            'image'               => $this->absUrl($image),
            'gallery'             => array_values(array_map(fn ($p) => $this->absUrl($p), $gallery)),
            'colors'              => array_values($colors),
            'sizes'               => array_values($sizes),
            'topsale'             => (bool) $g('topsale', 0),
            'flashsale'           => (bool) $g('flashsale', 0),
            'feature_product'     => (bool) $g('feature_product', 0),
            'ratting'             => (float) $rating,
            'reviews_count'       => $reviewsCount,
            'status'              => (int) ($g('status') ?? 1),
            'sold_count'          => $g('sold_count', 'sell_count') !== null ? (int) $g('sold_count', 'sell_count') : null,
            'created_at'          => $p->created_at ? (string) $p->created_at : null,
        ];
    }

    private function productQuery()
    {
        return Product::query()
            ->where('status', 1)
            ->with(['images', 'colors', 'sizes']);
    }

    /* ------------------------------------------------------------------ *
     * Order / Customer / Auth helpers
     * ------------------------------------------------------------------ */

    private function customerPayload(Customer $c): array
    {
        return [
            'id'       => (int) $c->id,
            'name'     => $c->name,
            'phone'    => $c->phone,
            'email'    => $c->email ?? null,
            'address'  => $c->address ?? null,
            'district' => $c->district ?? null,
            'area'     => $c->area ?? null,
        ];
    }

    private function orderPayload(Order $order): array
    {
        $statusMap = [
            1 => 'pending', 2 => 'confirmed', 3 => 'processing',
            4 => 'courier', 5 => 'delivered', 6 => 'cancelled', 7 => 'returned',
        ];

        $total = (float) $order->amount;
        $discount = (float) ($order->discount ?? 0);
        $shippingFee = (float) ($order->shipping_charge ?? 0);

        return [
            'id'                 => 'ORD-'.$order->invoice_id,
            'customer_name'      => $order->shipping?->name ?? $order->customer?->name ?? null,
            'customer_phone'     => $order->shipping?->phone ?? $order->customer?->phone ?? null,
            'customer_address'   => $order->shipping?->address ?? $order->customer?->address ?? null,
            'payment_method'     => $order->payment?->payment_method ?? 'cod',
            'delivery_charge'    => $shippingFee,
            'subtotal'           => round($total + $discount - $shippingFee, 2),
            'discount'           => $discount,
            'total'              => $total,
            'status'             => $statusMap[(int) $order->order_status] ?? 'pending',
            'courier_name'       => $order->courier_name ?? null,
            'courier_tracking_id'=> $order->courier_tracking_id ?? null,
            'created_at'         => $order->created_at ? (string) $order->created_at : null,
            'items'              => $order->orderdetails->map(fn ($d) => [
                'product_id'    => (int) $d->product_id,
                'product_name'  => $d->product_name,
                'price'         => (float) ($d->sale_price ?? 0),
                'quantity'      => (int) $d->qty,
                'color'         => $d->product_color ?? null,
                'size'          => $d->product_size ?? null,
            ])->values(),
        ];
    }

    /** Resolve the Customer that owns the request's bearer token (if any). */
    private function customerFromToken(Request $request): ?Customer
    {
        $bearer = $request->bearerToken();
        if (! $bearer) {
            return null;
        }

        $token = PersonalAccessToken::findToken($bearer);

        return $token && $token->tokenable instanceof Customer
            ? $token->tokenable
            : null;
    }

    /* ------------------------------------------------------------------ *
     * Endpoints
     * ------------------------------------------------------------------ */

    /** GET /api/v1/storefront/bootstrap */
    public function bootstrap()
    {
        $categories = Category::where('status', 1)
            ->orderBy('id')
            ->get()
            ->map(fn ($c) => $this->categoryPayload($c))
            ->values();

        $subcategories = Subcategory::where('status', 1)
            ->orderBy('id')
            ->get()
            ->map(fn ($s) => $this->subcategoryPayload($s, (int) $s->category_id))
            ->values();

        $banners = Banner::where('status', 1)
            ->orderBy('id')
            ->get()
            ->map(fn ($b) => $this->bannerPayload($b))
            ->values();

        $shippingCharges = ShippingCharge::all()->map(fn ($s) => [
            'id'          => (int) $s->id,
            'name'        => $s->name ?? $s->title ?? null,
            'amount'      => (float) ($s->amount ?? 0),
            'description' => $s->description ?? null,
        ])->values();

        $coupons = Coupon::where('status', 1)->get()->map(fn ($c) => [
            'id'               => (int) $c->id,
            'code'             => $c->code,
            'discount_type'    => $c->type === 'percent' ? 'percent' : 'fixed',
            'discount_amount'  => (float) ($c->value ?? 0),
            'min_purchase'     => (float) ($c->min_purchase ?? 0),
            'max_discount'     => null,
            'expiry_date'      => $c->valid_to ?? null,
            'status'           => 1,
            'used_count'       => 0,
        ])->values();

        $products = $this->productQuery()
            ->orderBy('id', 'desc')
            ->get()
            ->map(fn ($p) => $this->productPayload($p))
            ->values();

        $blogs = Blog::where('status', 1)
            ->orderBy('id', 'desc')
            ->get()
            ->map(fn ($b) => [
                'id'                => (int) $b->id,
                'title'             => $b->title,
                'slug'              => $b->slug,
                'short_description' => $b->short_description ?? null,
                'description'       => $b->description ?? null,
                'image'             => $this->absUrl($b->image ?? null),
                'views'             => (int) ($b->views ?? 0),
                'status'            => 1,
                'created_at'        => $b->created_at ? (string) $b->created_at : null,
            ])
            ->values();

        return $this->json([
            'settings'        => $this->settingsPayload(),
            'categories'      => $categories,
            'subcategories'   => $subcategories,
            'banners'         => $banners,
            'shipping_charges'=> $shippingCharges,
            'coupons'         => $coupons,
            'products'        => $products,
            'blogs'           => $blogs,
        ]);
    }

    /** GET /api/v1/storefront/products?category=&sort=&q= */
    public function products(Request $request)
    {
        $query = $this->productQuery();

        if ($category = $request->query('category')) {
            $query->where('category_id', (int) $category);
        }

        if ($q = trim((string) $request->query('q'))) {
            $like = '%'.$q.'%';
            $query->where(function ($w) use ($like) {
                $w->where('name', 'like', $like)
                  ->orWhere('product_code', 'like', $like)
                  ->orWhere('description', 'like', $like);
            });
        }

        $products = $query->orderBy('id', 'desc')->get()->map(fn ($p) => $this->productPayload($p))->values();

        return $this->json($products);
    }

    /** GET /api/v1/storefront/products/{idOrSlug} */
    public function product(Request $request, $idOrSlug)
    {
        $product = is_numeric($idOrSlug)
            ? Product::where('id', (int) $idOrSlug)
            : Product::where('slug', $idOrSlug);

        $product = $product->with(['images', 'colors', 'sizes'])->first();

        if (! $product) {
            return $this->json(null, 404, 'Product not found');
        }

        $reviews = Review::where('product_id', $product->id)
            ->where('status', 'approved')
            ->orderBy('id', 'desc')
            ->get()
            ->map(fn ($r) => [
                'id'            => (int) $r->id,
                'product_id'    => (int) $r->product_id,
                'customer_name' => $r->name ?? 'Customer',
                'rating'        => (float) ($r->ratting ?? 0),
                'comment'       => $r->review ?? '',
                'status'        => 'approved',
                'created_at'    => $r->created_at ? (string) $r->created_at : null,
            ])
            ->values();

        return $this->json([
            'product' => $this->productPayload($product),
            'reviews' => $reviews,
        ]);
    }

    /** GET /api/v1/storefront/search?q= */
    public function search(Request $request)
    {
        return $this->products($request);
    }

    /** POST /api/v1/storefront/coupons/apply */
    public function applyCoupon(Request $request)
    {
        $code = trim((string) $request->input('code'));
        $phone = (string) $request->input('phone');
        $subtotal = (float) $request->input('subtotal', 0);

        $coupon = Coupon::where('code', $code)->where('status', 1)->first();

        if (! $coupon) {
            return $this->json(null, 422, 'কুপন কোডটি সঠিক নয়।');
        }

        $today = now()->format('Y-m-d');
        if (($coupon->valid_from && $today < $coupon->valid_from)
            || ($coupon->valid_to && $today > $coupon->valid_to)) {
            return $this->json(null, 422, 'কুপনটির মেয়াদ শেষ অথবা এখনো শুরু হয়নি।');
        }

        if ($coupon->min_purchase && $subtotal < (float) $coupon->min_purchase) {
            return $this->json(null, 422, 'এই কুপন ব্যবহারে কমপক্ষে ৳'.number_format((float) $coupon->min_purchase, 0).' এর কেনাকাটা প্রয়োজন।');
        }

        $discount = $coupon->type === 'percent'
            ? $subtotal * ((float) $coupon->value / 100)
            : (float) $coupon->value;
        $discount = round(min($discount, $subtotal), 2);

        return $this->json([
            'code'     => $coupon->code,
            'type'     => $coupon->type,
            'discount' => $discount,
        ], 200, 'কুপন প্রয়োগ হয়েছে!');
    }

    /** POST /api/v1/storefront/orders */
    public function createOrder(Request $request)
    {
        $request->validate([
            'name'    => 'required|string|max:191',
            'phone'   => 'required|string|max:40',
            'address' => 'required|string|max:500',
            'items'   => 'required|array|min:1',
            'items.*.product_id' => 'required|integer',
            'items.*.qty'        => 'required|integer|min:1',
        ]);

        $name = (string) $request->input('name');
        $phone = (string) $request->input('phone');
        $address = (string) $request->input('address');
        $areaId = $request->input('area') ?? $request->input('shipping_area');
        $paymentMethod = $request->input('payment_method', 'cod');
        $couponCode = $request->input('coupon_code');
        $note = $request->input('order_note') ?? $request->input('note');
        $items = $request->input('items');

        // 1) Order restriction (phone based) — same service the Blade checkout uses.
        $restriction = app(OrderRestrictionService::class)->violationMessage($phone);
        if ($restriction) {
            return $this->json(null, 422, $restriction);
        }

        // 2) Resolve products + stock + subtotal.
        $subtotal = 0.0;
        $resolved = [];
        $hasDigital = false;
        $allFreeDelivery = true;

        foreach ($items as $item) {
            $product = Product::find((int) $item['product_id']);
            if (! $product || (int) $product->status !== 1) {
                return $this->json(null, 422, 'একটি পণ্য পাওয়া যায়নি বা বর্তমানে unavailable।');
            }

            $qty = max(1, (int) $item['qty']);

            if (! $product->is_digital && (int) $product->stock < $qty) {
                return $this->json(null, 422, '"'.$product->name.'" এর মাত্র '.max(0, (int) $product->stock).' টি স্টকে আছে।');
            }

            if ($product->is_digital) {
                $hasDigital = true;
            }
            if (! $product->free_delivery) {
                $allFreeDelivery = false;
            }

            $unitPrice = (float) ($item['price'] ?? $product->new_price ?? 0);
            $subtotal += $unitPrice * $qty;

            $resolved[] = [
                'product'  => $product,
                'qty'      => $qty,
                'unitPrice'=> $unitPrice,
                'color'    => $item['color'] ?? null,
                'size'     => $item['size'] ?? null,
            ];
        }

        // 3) Digital products can't be paid with COD.
        if ($hasDigital && $paymentMethod === 'cod') {
            return $this->json(null, 422, 'ডিজিটাল প্রোডাক্টের জন্য Cash On Delivery পাওয়া যায় না। অনলাইন পেমেন্ট সিলেক্ট করুন।');
        }

        // 4) Coupon discount.
        $discount = 0.0;
        if ($couponCode) {
            $coupon = Coupon::where('code', trim($couponCode))->where('status', 1)->first();
            if (! $coupon) {
                return $this->json(null, 422, 'কুপন কোডটি সঠিক নয়।');
            }
            $discount = $coupon->type === 'percent'
                ? $subtotal * ((float) $coupon->value / 100)
                : (float) $coupon->value;
            $discount = round(min($discount, $subtotal), 2);
        }

        // 5) Shipping charge.
        $shippingfee = 0.0;
        $shippingAreaName = null;
        if (! $allFreeDelivery) {
            $area = $areaId ? ShippingCharge::find((int) $areaId) : null;
            if ($area) {
                $shippingfee = (float) $area->amount;
                $shippingAreaName = $area->name ?? null;
            }
        }

        $grandTotal = round($subtotal + $shippingfee - $discount, 2);

        // 6) Customer (reuse by phone, else create).
        $customer = Customer::where('phone', $phone)->first();
        if (! $customer) {
            $customer = new Customer();
            $customer->name = $name;
            $customer->slug = Str::slug($name).'-'.rand(1000, 9999);
            $customer->phone = $phone;
            $customer->password = bcrypt((string) rand(111111, 999999));
            $customer->verify = 1;
            $customer->status = 'active';
            $customer->save();
        }

        // 7) Save Order.
        $isOnlinePayment = in_array($paymentMethod, ['bkash', 'shurjopay', 'uddoktapay', 'aamarpay'], true);

        $order = new Order();
        $order->invoice_id = (string) rand(11111, 99999);
        $order->amount = $grandTotal;
        $order->shipping_charge = $shippingfee;
        $order->customer_id = $customer->id;
        $order->order_status = 1; // pending
        $order->note = $note;
        $order->order_note = $note;
        $order->payment_status = 'pending';
        $order->coupon_code = $couponCode ? trim($couponCode) : null;
        $order->discount = $discount;
        $order->ip_address = $request->ip();

        if ($isOnlinePayment) {
            // Gateway controllers read these when no session amount is present.
            $order->customer_payable_amount = $grandTotal;
            $order->payment_gateway = $paymentMethod;
        }

        $order->save();

        // 8) Shipping + Payment.
        $shipping = new Shipping();
        $shipping->order_id = $order->id;
        $shipping->customer_id = $customer->id;
        $shipping->name = $name;
        $shipping->phone = $phone;
        $shipping->address = $address;
        $shipping->area = $shippingAreaName ?? 'Digital / Free Shipping';
        $shipping->save();

        $payment = new Payment();
        $payment->order_id = $order->id;
        $payment->customer_id = $customer->id;
        $payment->payment_method = $paymentMethod;
        $payment->amount = in_array($paymentMethod, ['bkash', 'shurjopay', 'uddoktapay', 'aamarpay'], true) ? 0 : $grandTotal;
        $payment->payment_status = 'pending';
        $payment->save();

        // 9) Order details + stock reduction + stock alerts.
        foreach ($resolved as $row) {
            /** @var Product $product */
            $product = $row['product'];

            $detail = new OrderDetails();
            $detail->order_id = $order->id;
            $detail->product_id = $product->id;
            $detail->product_name = $product->name;
            $detail->purchase_price = $product->purchase_price ?? null;
            $detail->sale_price = $row['unitPrice'];
            $detail->product_color = $row['color'];
            $detail->product_size = $row['size'];
            $detail->qty = $row['qty'];
            $detail->save();

            if (! $product->is_digital) {
                $product->stock = max(0, (int) $product->stock - (int) $row['qty']);
                $product->save();
            }
        }

        try {
            app(StockAlertService::class)->checkOrderItems($order->orderdetails()->get());
        } catch (\Throwable $e) {
            Log::warning('Stock alert check failed for order '.$order->id.': '.$e->getMessage());
        }

        // 10) Coupon usage + fraud check (non-blocking).
        if ($couponCode) {
            try {
                app(CouponService::class)->recordUsage(
                    trim($couponCode),
                    $phone,
                    $order->id,
                    $customer->id,
                    $discount
                );
            } catch (\Throwable $e) {
                Log::warning('Coupon usage record failed: '.$e->getMessage());
            }
        }

        // 11) Payment gateway redirect URL (online payments).
        $redirectUrl = null;
        if ($isOnlinePayment) {
            switch ($paymentMethod) {
                case 'bkash':
                    $redirectUrl = url('/bkash/checkout-url/create').'?order_id='.$order->id;
                    break;
                case 'shurjopay':
                    $redirectUrl = route('storefront.shurjopay.checkout', ['order' => $order->id]);
                    break;
                case 'uddoktapay':
                    $redirectUrl = route('uddoktapay.checkout', ['order_id' => $order->id]);
                    break;
                case 'aamarpay':
                    $redirectUrl = route('aamarpay.checkout', ['order_id' => $order->id]);
                    break;
            }
        }

        return $this->json([
            'order_id'     => $order->invoice_id,
            'invoice_id'   => $order->invoice_id,
            'total'        => $grandTotal,
            'payment_method' => $paymentMethod,
            'redirect_url' => $redirectUrl,
        ], 201, 'অর্ডার সফলভাবে গৃহীত হয়েছে!');
    }

    /** GET /api/v1/storefront/orders/track/{invoice}?phone= */
    public function trackOrder(Request $request, $invoice)
    {
        $phone = (string) $request->query('phone');

        $order = Order::where('invoice_id', $invoice)
            ->orWhere('id', is_numeric($invoice) ? (int) $invoice : 0)
            ->with(['orderdetails', 'shipping', 'customer', 'payment'])
            ->first();

        if (! $order) {
            return $this->json(null, 404, 'অর্ডার খুঁজে পাওয়া যায়নি।');
        }

        if ($phone) {
            $orderPhone = $order->shipping?->phone ?? $order->customer?->phone ?? null;
            if ($orderPhone && ! Str::endsWith((string) $orderPhone, substr(preg_replace('/[^\d]/', '', $phone), -6))) {
                return $this->json(null, 404, 'অর্ডার খুঁজে পাওয়া যায়নি।');
            }
        }

        return $this->json($this->orderPayload($order));
    }

    /* ------------------------------------------------------------------ *
     * Customer authentication (Sanctum bearer tokens)
     * ------------------------------------------------------------------ */

    /** POST /api/v1/storefront/auth/register */
    public function register(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:191',
            'phone'    => 'required|string|max:40|unique:customers,phone',
            'email'    => 'nullable|email|max:191|unique:customers,email',
            'password' => 'required|string|min:6',
        ]);

        $nextId = (int) (Customer::max('id') ?? 0) + 1;

        $customer = new Customer();
        $customer->name = $request->input('name');
        $customer->slug = strtolower(Str::slug($request->input('name').'-'.$nextId));
        $customer->phone = $request->input('phone');
        $customer->email = $request->input('email');
        $customer->password = Hash::make($request->input('password'));
        $customer->verify = 1;
        $customer->status = 'active';
        $customer->save();

        $token = $customer->createToken('storefront')->plainTextToken;

        return $this->json([
            'token' => $token,
            'user'  => $this->customerPayload($customer),
        ], 201, 'অ্যাকাউন্ট তৈরি হয়েছে!');
    }

    /** POST /api/v1/storefront/auth/login */
    public function login(Request $request)
    {
        $request->validate([
            'login'    => 'required|string',
            'password' => 'required|string',
        ]);

        $login = trim((string) $request->input('login'));
        $password = (string) $request->input('password');
        $isPhone = preg_match('/^[0-9+]+$/', $login) === 1;

        $customer = $isPhone
            ? Customer::where('phone', $login)->first()
            : Customer::where('email', $login)->first();

        if (! $customer || ! Hash::check($password, $customer->password)) {
            return $this->json(null, 422, 'ফোন নম্বর/ইমেইল অথবা পাসওয়ার্ড সঠিক নয়।');
        }

        if ($customer->status !== 'active') {
            return $this->json(null, 403, 'এই অ্যাকাউন্টটি বর্তমানে নিষ্ক্রিয়।');
        }

        $token = $customer->createToken('storefront')->plainTextToken;

        return $this->json([
            'token' => $token,
            'user'  => $this->customerPayload($customer),
        ], 200, 'লগইন সফল হয়েছে!');
    }

    /** POST /api/v1/storefront/auth/logout */
    public function logout(Request $request)
    {
        $bearer = $request->bearerToken();
        if ($bearer) {
            PersonalAccessToken::findToken($bearer)?->delete();
        }

        return $this->json(null, 200, 'লগআউট হয়েছে।');
    }

    /** GET /api/v1/storefront/auth/me */
    public function me(Request $request)
    {
        $customer = $this->customerFromToken($request);

        if (! $customer) {
            return $this->json(null, 401, 'Unauthenticated.');
        }

        return $this->json($this->customerPayload($customer));
    }

    /** GET /api/v1/storefront/auth/orders */
    public function myOrders(Request $request)
    {
        $customer = $this->customerFromToken($request);

        if (! $customer) {
            return $this->json(null, 401, 'Unauthenticated.');
        }

        $orders = Order::where('customer_id', $customer->id)
            ->with(['orderdetails', 'shipping', 'customer', 'payment'])
            ->orderBy('id', 'desc')
            ->get()
            ->map(fn ($o) => $this->orderPayload($o))
            ->values();

        return $this->json($orders);
    }

    /** GET /api/v1/storefront/auth/downloads */
    public function myDownloads(Request $request)
    {
        $customer = $this->customerFromToken($request);

        if (! $customer) {
            return $this->json(null, 401, 'Unauthenticated.');
        }

        $downloads = DigitalDownload::with('product:id,name')
            ->where('customer_id', $customer->id)
            ->orderBy('id', 'desc')
            ->get()
            ->map(fn ($d) => [
                'id'                   => (int) $d->id,
                'order_id'             => (int) $d->order_id,
                'product_id'           => (int) $d->product_id,
                'product_name'         => $d->product?->name ?? null,
                'file_name'            => $d->file_path ? basename($d->file_path) : null,
                'download_url'         => url('/digital-download/'.$d->token),
                'remaining_downloads'  => (int) ($d->remaining_downloads ?? 0),
                'expires_at'           => $d->expires_at ? (string) $d->expires_at : null,
            ])
            ->values();

        return $this->json($downloads);
    }

    /** POST /api/v1/storefront/auth/profile */
    public function updateProfile(Request $request)
    {
        $customer = $this->customerFromToken($request);

        if (! $customer) {
            return $this->json(null, 401, 'Unauthenticated.');
        }

        $request->validate([
            'name'    => 'required|string|max:191',
            'phone'   => 'required|string|max:40|unique:customers,phone,'.$customer->id,
            'email'   => 'nullable|email|max:191|unique:customers,email,'.$customer->id,
            'address' => 'nullable|string|max:500',
            'district'=> 'nullable|string|max:100',
            'area'    => 'nullable|string|max:100',
        ]);

        $customer->name = $request->input('name');
        $customer->phone = $request->input('phone');
        $customer->email = $request->input('email');
        $customer->address = $request->input('address');
        $customer->district = $request->input('district');
        $customer->area = $request->input('area');
        $customer->save();

        return $this->json($this->customerPayload($customer), 200, 'প্রোফাইল সফলভাবে আপডেট হয়েছে।');
    }

    /** POST /api/v1/storefront/auth/password */
    public function changePassword(Request $request)
    {
        $customer = $this->customerFromToken($request);

        if (! $customer) {
            return $this->json(null, 401, 'Unauthenticated.');
        }

        $request->validate([
            'old_password'     => 'required|string',
            'new_password'     => 'required|string|min:6',
            'confirm_password' => 'required|string|same:new_password',
        ]);

        if (! Hash::check($request->input('old_password'), $customer->password)) {
            return $this->json(null, 422, 'পুরনো পাসওয়ার্ডটি সঠিক নয়।');
        }

        $customer->password = Hash::make($request->input('new_password'));
        $customer->save();

        return $this->json(null, 200, 'পাসওয়ার্ড পরিবর্তন হয়েছে।');
    }

    /* ------------------------------------------------------------------ *
     * Refunds
     * ------------------------------------------------------------------ */

    /** GET /api/v1/storefront/auth/refunds */
    public function myRefunds(Request $request)
    {
        $customer = $this->customerFromToken($request);

        if (! $customer) {
            return $this->json(null, 401, 'Unauthenticated.');
        }

        $refunds = Refund::where('customer_id', $customer->id)
            ->orderBy('id', 'desc')
            ->get()
            ->map(fn ($r) => [
                'id'            => (int) $r->id,
                'refund_id'     => $r->refund_id,
                'order_id'      => (int) $r->order_id,
                'order_invoice' => $r->order ? 'ORD-'.$r->order->invoice_id : null,
                'amount'        => (float) ($r->amount ?? 0),
                'shipping_charge'=> (float) ($r->shipping_charge ?? 0),
                'reason'        => $r->reason,
                'status'        => $r->status,
                'refund_method' => $r->refund_method,
                'created_at'    => $r->created_at ? (string) $r->created_at : null,
            ])
            ->values();

        return $this->json($refunds);
    }

    /** POST /api/v1/storefront/refunds */
    public function createRefund(Request $request)
    {
        $customer = $this->customerFromToken($request);

        if (! $customer) {
            return $this->json(null, 401, 'Unauthenticated.');
        }

        $request->validate([
            'order_id'   => 'required|string|max:40',
            'reason'     => 'required|string|max:1000',
            'refund_method' => 'required|in:original_payment,bkash,nagad,bank,manual',
            'refund_account' => 'required|string|max:255',
            'amount'     => 'nullable|numeric|min:0',
            'shipping_charge' => 'nullable|numeric|min:0',
        ]);

        // Accept either the numeric order id or the customer-facing "ORD-12345"
        // invoice reference (the storefront only ever shows the invoice id).
        $orderRef = trim((string) $request->input('order_id'));
        $order = null;

        if (preg_match('/^ORD-(\d+)$/i', $orderRef, $m)) {
            $order = Order::where('invoice_id', $m[1])->where('customer_id', $customer->id)->first();
        } elseif (is_numeric($orderRef)) {
            $order = Order::where('id', (int) $orderRef)->where('customer_id', $customer->id)->first();
        }

        if (! $order) {
            return $this->json(null, 404, 'অর্ডারটি খুঁজে পাওয়া যায়নি।');
        }

        if ((int) $order->order_status === 11) {
            return $this->json(null, 422, 'এই অর্ডারটি ইতিমধ্যে বাতিল করা হয়েছে।');
        }

        $existing = Refund::where('order_id', $order->id)
            ->whereIn('status', ['pending', 'approved'])
            ->exists();

        if ($existing) {
            return $this->json(null, 422, 'এই অর্ডারের জন্য ইতিমধ্যে একটি রিফান্ড আবেদন আছে।');
        }

        $amount = (float) ($request->input('amount') ?? $order->amount);
        $shippingCharge = (float) ($request->input('shipping_charge') ?? 0);

        $maxRefund = (float) $order->amount + (float) ($order->shipping_charge ?? 0);
        if (($amount + $shippingCharge) > $maxRefund) {
            return $this->json(null, 422, 'রিফান্ডের পরিমাণ অর্ডারের মোট টাকার বেশি হতে পারবে না।');
        }

        $refund = new Refund();
        $refund->order_id = $order->id;
        $refund->customer_id = $customer->id;
        $refund->refund_id = Refund::generateRefundId();
        $refund->amount = $amount;
        $refund->shipping_charge = $shippingCharge;
        $refund->reason = $request->input('reason');
        $refund->status = 'pending';
        $refund->refund_method = $request->input('refund_method');
        $refund->refund_account = $request->input('refund_account');
        $refund->refund_account_name = $request->input('refund_account_name');
        $refund->save();

        return $this->json([
            'id'         => (int) $refund->id,
            'refund_id'  => $refund->refund_id,
            'status'     => $refund->status,
        ], 201, 'রিফান্ড আবেদন জমা হয়েছে।');
    }

    /** POST /api/v1/storefront/reviews */
    public function submitReview(Request $request)
    {
        $request->validate([
            'product_id' => 'required|integer',
            'rating'     => 'required|numeric|min:1|max:5',
            'comment'    => 'required|string',
        ]);

        $review = new Review();
        $review->name = $request->input('customer_name', 'Customer');
        $review->email = $request->input('customer_email', 'N/A');
        $review->product_id = (int) $request->input('product_id');
        $review->review = (string) $request->input('comment');
        $review->ratting = (string) round((float) $request->input('rating'));
        $review->status = 'pending';
        $review->save();

        return $this->json(null, 201, 'আপনার মতামতের জন্য ধন্যবাদ!');
    }

    /** POST /api/v1/storefront/complaints */
    public function submitComplaint(Request $request)
    {
        $request->validate([
            'name'    => 'required|string',
            'phone'   => 'required|string',
            'message' => 'required|string',
        ]);

        $complaint = new Complaint();
        $complaint->name = $request->input('name');
        $complaint->phone = $request->input('phone');
        $complaint->order_id = $request->input('order_id');
        $complaint->description = $request->input('message');
        $complaint->status = 'open';
        $complaint->save();

        return $this->json(['token' => 'CMP-'.$complaint->id], 201, 'আপনার অভিযোগটি গৃহীত হয়েছে।');
    }

    /** POST /api/v1/storefront/contact */
    public function submitContact(Request $request)
    {
        $request->validate([
            'name'    => 'required|string',
            'phone'   => 'required|string',
            'message' => 'required|string',
        ]);

        $msg = new ContactMessage();
        $msg->full_name = $request->input('name');
        $msg->mobile = $request->input('phone');
        $msg->email = $request->input('email');
        $msg->subject = $request->input('subject');
        $msg->details = $request->input('message');
        $msg->status = 'unread';
        $msg->save();

        return $this->json(null, 201, 'ধন্যবাদ! আপনার বার্তা আমাদের কাছে পৌঁছেছে।');
    }

    /** GET /api/v1/storefront/blogs */
    public function blogs()
    {
        $blogs = Blog::where('status', 1)
            ->orderBy('id', 'desc')
            ->get()
            ->map(fn ($b) => [
                'id'                => (int) $b->id,
                'title'             => $b->title,
                'slug'              => $b->slug,
                'short_description' => $b->short_description ?? null,
                'description'       => $b->description ?? null,
                'image'             => $this->absUrl($b->image ?? null),
                'views'             => (int) ($b->views ?? 0),
                'status'            => 1,
                'created_at'        => $b->created_at ? (string) $b->created_at : null,
            ])
            ->values();

        return $this->json($blogs);
    }

    /** GET /api/v1/storefront/blogs/{slug} */
    public function blog(Request $request, $slug)
    {
        $blog = Blog::where('slug', $slug)->first();

        if (! $blog) {
            return $this->json(null, 404, 'Blog not found');
        }

        return $this->json([
            'id'                => (int) $blog->id,
            'title'             => $blog->title,
            'slug'              => $blog->slug,
            'short_description' => $blog->short_description ?? null,
            'description'       => $blog->description ?? null,
            'image'             => $this->absUrl($blog->image ?? null),
            'views'             => (int) ($blog->views ?? 0),
            'status'            => 1,
            'created_at'        => $blog->created_at ? (string) $blog->created_at : null,
        ]);
    }
}
