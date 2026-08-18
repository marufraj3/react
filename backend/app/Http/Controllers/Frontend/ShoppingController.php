<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ProductVariantPrice;
use App\Models\Product;
use App\Models\Size;
use App\Models\Color;
use App\Models\Coupon;
use Toastr;
use Cart;
use DB;
use Carbon\Carbon;
use Session;

class ShoppingController extends Controller
{
    /**
     * 🔹 কার্টে থাকা সব প্রোডাক্ট থেকে মোট Advance Amount বের করবে
     */
    public static function getCartAdvanceAmount()
    {
        $advance = 0;

        // ✅ First collect all product IDs to avoid N+1 query
        $productIds = Cart::instance('shopping')->content()
            ->pluck('id')
            ->unique()
            ->toArray();

        if (empty($productIds)) {
            return $advance;
        }

        // ✅ Load all products in a single query
        $products = Product::whereIn('id', $productIds)
            ->select('id', 'advance_amount')
            ->get()
            ->keyBy('id'); // Key by ID for fast lookup

        // ✅ Now iterate through cart items without additional queries
        foreach (Cart::instance('shopping')->content() as $item) {
            $product = $products->get($item->id);

            if ($product && $product->advance_amount > 0) {
                // Qty অনুযায়ী গুণ করব
                $advance += ($product->advance_amount * $item->qty);
            }
        }

        return $advance;
    }

    /**
     * ⭐ নতুন helper:
     * 🔹 কার্টে অন্তত একটি ডিজিটাল প্রোডাক্ট আছে কি না?
     */
    public static function hasDigitalProductInCart()
    {
        foreach (Cart::instance('shopping')->content() as $item) {
            if (!empty($item->options->is_digital) && $item->options->is_digital == 1) {
                return true;
            }
        }
        return false;
    }

    /**
     * ⭐ নতুন helper:
     * 🔹 কার্টে থাকা সব প্রোডাক্ট free delivery eligible কিনা check করবে
     * যদি সব প্রোডাক্ট free_delivery = 1 হয়, তাহলে shipping charge 0 হবে
     */
    public static function hasAllFreeDeliveryProducts()
    {
        $productIds = Cart::instance('shopping')->content()
            ->pluck('id')
            ->unique()
            ->toArray();

        if (empty($productIds)) {
            return false;
        }

        // Load all products in a single query
        $products = Product::whereIn('id', $productIds)
            ->select('id', 'free_delivery', 'is_digital')
            ->get()
            ->keyBy('id');

        // Check if all physical products have free_delivery = 1
        foreach (Cart::instance('shopping')->content() as $item) {
            $product = $products->get($item->id);
            
            // Digital products don't need shipping, so skip them
            if ($product && $product->is_digital == 1) {
                continue;
            }
            
            // If any physical product doesn't have free_delivery, return false
            if ($product && $product->free_delivery != 1) {
                return false;
            }
        }

        return true;
    }

    // 🟢 Add to cart (GET)
    public function addTocartGet($id, Request $request)
    {
        $qty = 1;
        $productInfo = Product::find($id);

        if (!$productInfo) {
            return response()->json(['error' => 'Product not found']);
        }

        $productImage = DB::table('productimages')
            ->where('product_id', $id)
            ->value('image') ?? 'public/uploads/default.webp';

        $cartinfo = Cart::instance('shopping')->add([
            'id'   => $productInfo->id,
            'name' => $productInfo->name,
            'qty'  => $qty,
            'price'=> (float) ($productInfo->new_price ?? $productInfo->old_price ?? 1),
            'options' => [
                'image'          => $productImage,
                'old_price'      => (float) ($productInfo->old_price ?? 0),
                'slug'           => $productInfo->slug,
                'purchase_price' => (float) ($productInfo->purchase_price ?? 0),

                // 🔥 Advance
                'advance_amount' => (float) ($productInfo->advance_amount ?? 0),

                // 🔥 Digital flag
                'is_digital'     => (int) ($productInfo->is_digital ?? 0),

                // 🔥 Free Delivery flag
                'free_delivery'  => (int) ($productInfo->free_delivery ?? 0),
            ],
        ]);

        return response()->json($cartinfo);
    }

    // 🟢 Apply coupon
    public function applyCoupon(Request $request)
    {
        $request->validate([
            'coupon_code' => 'required',
            'phone'       => 'nullable|string|max:30',
        ]);

        // ⭐ কুপনের "প্রতি কাস্টমার সর্বোচ্চ কতবার" সীমা ফোন নম্বর দিয়ে গোনা হয়।
        // চেকআউট ফর্মে ফোন লেখা থাকলে সেটি পাঠানো হয়; না থাকলে লগইন করা
        // কাস্টমারের নম্বর ব্যবহার করি। কোনোটিই না থাকলে শুধু মোট সীমা প্রযোজ্য
        // হবে — অর্ডার সেভের সময় আসল ফোন দিয়ে আবার যাচাই করা হয়।
        $phone = $request->input('phone');

        if (!$phone && auth()->guard('web')->check()) {
            $phone = auth()->guard('web')->user()->phone ?? null;
        }

        $result = app(\App\Services\CouponService::class)->apply($request->coupon_code, $phone);

        // ক্যাম্পেইন/বিল্ডার পেজ কোনো রিলোড করে না — সেখান থেকে আসা রিকোয়েস্টে
        // JSON + রিফ্রেশ করা কার্ট HTML দুটোই ফেরত দিই, যাতে এক রাউন্ডট্রিপেই
        // টোটাল আপডেট হয়ে যায়।
        if ($this->wantsJson($request)) {
            return response()->json($result + [
                'cart_html' => $this->renderCartPartial(),
            ], $result['ok'] ? 200 : 422);
        }

        $result['ok']
            ? Toastr::success($result['message'], 'Success')
            : Toastr::error($result['message'], 'Error');

        return redirect()->back();
    }

    // 🟢 Remove coupon
    public function removeCoupon(Request $request)
    {
        $result = app(\App\Services\CouponService::class)->remove();

        if ($this->wantsJson($request)) {
            return response()->json($result + [
                'cart_html' => $this->renderCartPartial(),
            ]);
        }

        Toastr::success($result['message'], 'Success');
        return redirect()->back();
    }

    /**
     * ⭐ অর্ডার বাম্প — চেকআউটে দেখানো অ্যাড-অন অফার কার্টে যোগ করে।
     */
    public function addOrderBump(Request $request)
    {
        $request->validate(['bump_id' => 'required|integer']);

        $result = app(\App\Services\OrderBumpService::class)
            ->addToCart((int) $request->bump_id);

        if ($this->wantsJson($request)) {
            // বাম্প যোগ হলে কার্টের অঙ্ক বদলায়, তাই কুপনটাও আবার যাচাই করি।
            app(\App\Services\CouponService::class)->revalidate();

            return response()->json($result + [
                'cart_html' => $this->renderCartPartial(),
            ], $result['ok'] ? 200 : 422);
        }

        $result['ok']
            ? Toastr::success($result['message'], 'Success')
            : Toastr::error($result['message'], 'Error');

        return redirect()->back();
    }

    /**
     * ক্যাম্পেইন পেজের fetch() কল JSON চায়; সাধারণ চেকআউটের ফর্ম সাবমিট চায় redirect।
     */
    protected function wantsJson(Request $request): bool
    {
        return $request->boolean('campaign')
            || $request->header('X-Campaign-Page')
            || $request->expectsJson();
    }

    /**
     * বর্তমান কার্টের HTML স্ট্রিং — AJAX রেসপন্সে সরাসরি বসিয়ে দেওয়ার জন্য।
     */
    protected function renderCartPartial(): string
    {
        $data = Cart::instance('shopping')->content();

        return view(self::cartPartial(), compact('data'))->render();
    }

    /**
     * কার্ট বদলানোর পর সবসময় এই রেসপন্সটাই ফেরত যায়।
     *
     * এখানে কুপন revalidate করা জরুরি: qty কমিয়ে min_purchase এর নিচে নামলে
     * কুপনটা আর বৈধ থাকে না, আবার percent কুপনে টাকার অঙ্ক বদলে যায়।
     * আগে এটা কোথাও হতো না, ফলে সেশনে বসে থাকা পুরনো discount টি
     * order_save() পর্যন্ত চলে যেত।
     */
    protected function cartResponse()
    {
        app(\App\Services\CouponService::class)->revalidate();

        $data = Cart::instance('shopping')->content();

        return view(self::cartPartial(), compact('data'));
    }

    // 🟢 Add to cart (POST) with variant support
    public function cart_store(Request $request)
    {
        $product = Product::with('image')->find($request->id);

        if (!$product) {
            Toastr::error('Product not found', 'Error!');
            return redirect()->back();
        }

        $variant = null;
        $variantMatrix = ProductVariantPrice::where('product_id', $product->id);
        if ((clone $variantMatrix)->exists()) {
            $requiresSize = (clone $variantMatrix)->whereNotNull('size_id')->exists();
            $requiresColor = (clone $variantMatrix)->whereNotNull('color_id')->exists();

            if (($requiresSize && !$request->filled('product_size')) || ($requiresColor && !$request->filled('product_color'))) {
                Toastr::error('Please select the required size and color.', 'Error!');
                return redirect()->back()->withInput();
            }

            $variant = (clone $variantMatrix)
                ->when($requiresColor, fn ($query) => $query->where('color_id', $request->product_color))
                ->when($requiresSize, fn ($query) => $query->where('size_id', $request->product_size))
                ->first();

            if (!$variant) {
                Toastr::error('The selected product variant is not available.', 'Error!');
                return redirect()->back()->withInput();
            }

            if ($variant->stock !== null && (int) $variant->stock < max(1, (int) ($request->qty ?? 1))) {
                Toastr::error('The selected product variant is out of stock.', 'Error!');
                return redirect()->back()->withInput();
            }
        }

        $price = $variant && $variant->price > 0
            ? (float) $variant->price
            : (float) ($product->new_price ?? $product->old_price ?? 1);

        $size = $request->filled('product_size') ? Size::find($request->product_size) : null;
        $color = $request->filled('product_color') ? Color::find($request->product_color) : null;
        $sizeName = $size ? ($size->sizeName ?? $size->name ?? null) : null;
        $colorName = $color ? ($color->getDisplayName() ?? $color->colorName ?? null) : null;

        // ✅ Fallback image
        $image = optional($product->image)->image
            ?? DB::table('productimages')->where('product_id', $product->id)->value('image')
            ?? 'public/uploads/default.webp';

        // ✅ Add to cart
        Cart::instance('shopping')->add([
            'id'   => $product->id,
            'name' => $product->name,
            'qty'  => $request->qty ?? 1,
            'price'=> $price,
            'options' => [
                'slug'           => $product->slug,
                'image'          => $image,
                'old_price'      => (float) ($product->old_price ?? 0),
                'purchase_price' => (float) ($product->purchase_price ?? 0),
                'product_size'     => $sizeName,
                'product_color'    => $colorName,
                'size_id'          => $request->product_size ?? null,
                'color_id'         => $request->product_color ?? null,
                'variant_price_id' => $variant->id ?? null,
                'pro_unit'         => $request->pro_unit ?? null,

                // 🔥 Advance
                'advance_amount' => (float) ($product->advance_amount ?? 0),

                // 🔥 Digital flag
                'is_digital'     => (int) ($product->is_digital ?? 0),

                // 🔥 Free Delivery flag
                'free_delivery'  => (int) ($product->free_delivery ?? 0),
            ],
        ]);

        Toastr::success('Product added to cart successfully!', 'Success');

        // ⭐ ক্যাম্পেইন পেজ থেকে কার্টে যোগ হলে সেটা গোনা হয় (funnel: ভিজিট → কার্ট → অর্ডার)
        if ($campaignId = Session::get('active_campaign_id')) {
            app(\App\Services\CampaignAnalyticsService::class)->recordAddToCart($campaignId);
        }

        // যদি ফর্ম থেকে "order_now" ক্লিক করা হয়ে থাকে, সরাসরি checkout
        if ($request->has('order_now')) {
            return redirect()->route('customer.checkout');
        }

        // নরমাল কেসে আগের পেইজে ফিরে যাবে
        return redirect()->back();
    }

    // 🟢 Update cart (color/size change and authoritative variant price)
    public function cart_update(Request $request)
    {
        $rowId = $request->id;
        $cartItem = Cart::instance('shopping')->get($rowId);

        if (!$cartItem) {
            return response()->json(['message' => 'Cart item not found.'], 404);
        }

        $product = Product::find($cartItem->id);
        if (!$product) {
            return response()->json(['message' => 'Product not found.'], 404);
        }

        // New campaign pages send IDs explicitly. Resolve legacy name-only requests too.
        $sizeId = $request->input('product_size_id', $request->input('size_id'));
        $colorId = $request->input('product_color_id', $request->input('color_id'));
        if (!$sizeId && $request->filled('product_size')) {
            $sizeId = Size::where('sizeName', $request->product_size)->value('id');
        }
        if (!$colorId && $request->filled('product_color')) {
            $colorId = Color::where('colorName', $request->product_color)->value('id');
        }

        $size = $sizeId ? Size::find($sizeId) : null;
        $color = $colorId ? Color::find($colorId) : null;
        $sizeName = $size ? ($size->sizeName ?? $size->name ?? null) : ($request->product_size ?: ($cartItem->options->product_size ?? null));
        $colorName = $color ? ($color->getDisplayName() ?? $color->colorName ?? null) : ($request->product_color ?: ($cartItem->options->product_color ?? null));

        $variant = null;
        $variantMatrix = ProductVariantPrice::where('product_id', $product->id);
        $hasVariantPrices = (clone $variantMatrix)->exists();
        if ($hasVariantPrices) {
            $requiresSize = (clone $variantMatrix)->whereNotNull('size_id')->exists();
            $requiresColor = (clone $variantMatrix)->whereNotNull('color_id')->exists();

            if (($requiresSize && !$sizeId) || ($requiresColor && !$colorId)) {
                return response()->json(['message' => 'প্রয়োজনীয় সাইজ ও কালার নির্বাচন করুন।'], 422);
            }

            $variant = (clone $variantMatrix)
                ->when($requiresSize, fn ($query) => $query->where('size_id', $sizeId))
                ->when($requiresColor, fn ($query) => $query->where('color_id', $colorId))
                ->first();

            if (!$variant) {
                return response()->json(['message' => 'নির্বাচিত ভ্যারিয়েন্টটি পাওয়া যায়নি। অন্য সাইজ বা কালার বেছে নিন।'], 422);
            }
        }

        if ($variant && $variant->stock !== null && (int) $variant->stock < (int) $cartItem->qty) {
            return response()->json(['message' => 'নির্বাচিত ভ্যারিয়েন্টে পর্যাপ্ত স্টক নেই।'], 422);
        }

        $price = $variant && $variant->price > 0
            ? (float) $variant->price
            : (float) ($product->new_price ?? $product->old_price ?? $cartItem->price);

        Cart::instance('shopping')->update($rowId, [
            'price' => $price,
            'options' => [
                'product_size'     => $sizeName,
                'product_color'    => $colorName,
                'size_id'          => $sizeId ?: null,
                'color_id'         => $colorId ?: null,
                'variant_price_id' => $variant->id ?? null,
                'slug'             => $cartItem->options->slug ?? $product->slug,
                'image'            => $cartItem->options->image ?? 'public/uploads/default.webp',
                'old_price'        => $cartItem->options->old_price ?? (float) ($product->old_price ?? 0),
                'purchase_price'   => $cartItem->options->purchase_price ?? (float) ($product->purchase_price ?? 0),
                'pro_unit'         => $cartItem->options->pro_unit ?? ($product->pro_unit ?? null),
                'advance_amount'   => $cartItem->options->advance_amount ?? (float) ($product->advance_amount ?? 0),
                'is_digital'       => $cartItem->options->is_digital ?? (int) ($product->is_digital ?? 0),
                'free_delivery'    => $cartItem->options->free_delivery ?? (int) ($product->free_delivery ?? 0),
            ],
        ]);

        return $this->cartResponse();
    }

    // 🟢 Remove from cart
    public function cart_remove(Request $request)
    {
        Cart::instance('shopping')->update($request->id, 0);
        return $this->cartResponse();
    }

    // 🟢 Increment quantity
    public function cart_increment(Request $request)
    {
        $item = Cart::instance('shopping')->get($request->id);
        if (!$item) {
            return response()->json(['message' => 'Cart item not found.'], 404);
        }

        $qty = $item->qty + 1;
        $variantId = $item->options->variant_price_id ?? null;
        if ($variantId) {
            $variant = ProductVariantPrice::find($variantId);
            if ($variant && $variant->stock !== null && (int) $variant->stock < $qty) {
                return response()->json(['message' => 'নির্বাচিত ভ্যারিয়েন্টে আর স্টক নেই।'], 422);
            }
        }

        Cart::instance('shopping')->update($request->id, $qty);

        return $this->cartResponse();
    }

    // 🟢 Decrement quantity
    public function cart_decrement(Request $request)
    {
        $item = Cart::instance('shopping')->get($request->id);
        if (!$item) {
            return response()->json(['message' => 'Cart item not found.'], 404);
        }
        $qty  = max(1, $item->qty - 1); // ১ এর নিচে নামবে না

        Cart::instance('shopping')->update($request->id, $qty);

        return $this->cartResponse();
    }

    // 🟢 Cart count (header)
    public function cart_count(Request $request)
    {
        $data = Cart::instance('shopping')->count();
        return view('frontEnd.layouts.ajax.cart_count', compact('data'));
    }

    // 🟢 Mobile cart count
    public function mobilecart_qty(Request $request)
    {
        $data = Cart::instance('shopping')->count();
        return view('frontEnd.layouts.ajax.mobilecart_qty', compact('data'));
    }

    // 🟢 Sidebar cart (for floating cart drawer)
    public function sidebarCart(Request $request)
    {
        $generalsetting = \App\Models\GeneralSetting::where('status', 1)->first();
        return view('frontEnd.layouts.ajax.sidebar-cart', compact('generalsetting'));
    }

    // 🟢 Change product from campaign or offers (supports size/color variant selection)
    public function changeProduct(Request $request)
    {
        $productId = $request->input('id');
        $product   = Product::with('image')->find($productId);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found.',
            ]);
        }

        $sizeId  = $request->input('product_size') ?: null;
        $colorId = $request->input('product_color') ?: null;
        $qty     = max(1, (int) $request->input('qty', 1));

        // ভ্যারিয়েন্ট রিজলভ (size/color আইডি থেকে) + স্টক ভ্যালিডেশন
        $variant = null;
        $variantRows = ProductVariantPrice::where('product_id', $product->id)->get();
        if ($variantRows->isNotEmpty() && ($sizeId || $colorId)) {
            // exact match আগে, তারপর null-tolerant match (কিছু row-তে color/size null থাকতে পারে)
            $variant = $variantRows
                ->filter(function ($row) use ($sizeId, $colorId) {
                    $sizeOk  = !$sizeId  || $row->size_id === null  || (string) $row->size_id === (string) $sizeId;
                    $colorOk = !$colorId || $row->color_id === null || (string) $row->color_id === (string) $colorId;
                    return $sizeOk && $colorOk;
                })
                ->sortByDesc(function ($row) use ($sizeId, $colorId) {
                    // বেশি নির্দিষ্ট row আগে (size+color উভয়ে মিললে সর্বোচ্চ স্কোর)
                    $score = 0;
                    if ($sizeId && (string) $row->size_id === (string) $sizeId) $score += 2;
                    if ($colorId && (string) $row->color_id === (string) $colorId) $score += 2;
                    return $score;
                })
                ->first();

            if (!$variant) {
                return response()->json(['message' => 'নির্বাচিত সাইজ/কালারটি পাওয়া যায়নি।'], 422);
            }

            if ($variant->stock !== null && (int) $variant->stock < $qty) {
                return response()->json(['message' => 'নির্বাচিত সাইজ/কালারটি স্টকে নেই।'], 422);
            }
        }

        $size  = $sizeId ? Size::find($sizeId) : null;
        $color = $colorId ? Color::find($colorId) : null;
        $sizeName  = $size ? ($size->sizeName ?? $size->name ?? null) : null;
        $colorName = $color ? ($color->getDisplayName() ?? $color->colorName ?? null) : null;

        $price = $variant && $variant->price > 0
            ? (float) $variant->price
            : (float) ($product->new_price ?? $product->old_price ?? 1);

        Cart::instance('shopping')->destroy();

        Cart::instance('shopping')->add([
            'id'   => $product->id,
            'name' => $product->name,
            'qty'  => $qty,
            'price'=> $price,
            'options' => [
                'slug'           => $product->slug,
                'image'          => optional($product->image)->image ?? 'public/uploads/default.webp',
                'old_price'      => (float) ($product->old_price ?? 0),
                'purchase_price' => (float) ($product->purchase_price ?? 0),

                // 🔥 Variant info (size & color)
                'product_size'     => $sizeName,
                'product_color'    => $colorName,
                'size_id'          => $sizeId,
                'color_id'         => $colorId,
                'variant_price_id' => $variant->id ?? null,

                // 🔥 Advance
                'advance_amount' => (float) ($product->advance_amount ?? 0),

                // 🔥 Digital flag
                'is_digital'     => (int) ($product->is_digital ?? 0),

                // 🔥 Free Delivery flag
                'free_delivery'  => (int) ($product->free_delivery ?? 0),
            ],
        ]);

        return $this->cartResponse();
    }

    /**
     * Full cart page (route: cart.show)
     */
    public function cart_show(Request $request)
    {
        $data = Cart::instance('shopping')->content();

        // Parity with checkout: digital-only carts and all-free-delivery
        // carts ship free — clear any stale shipping charge.
        $requiresShipping = false;
        foreach ($data as $item) {
            $product = \App\Models\Product::find($item->id);
            if ($product && $product->is_digital != 1) {
                $requiresShipping = true;
                break;
            }
        }
        if (!$requiresShipping || self::hasAllFreeDeliveryProducts()) {
            Session::put('shipping', 0);
        }

        return view('frontEnd.layouts.pages.cart', compact('data'));
    }

    /**
     * Campaign landing pages need a script-free cart table that keeps
     * the same columns as the checkout UI. The main store cart partial
     * injects jQuery handlers and a delete column, which breaks those pages.
     */
    public static function cartPartial(): string
    {
        $referer = (string) request()->headers->get('referer', '');
        $isCampaign = request()->boolean('campaign')
            || request()->header('X-Campaign-Page')
            || str_contains($referer, '/campaign/');

        return $isCampaign
            ? 'frontEnd.layouts.ajax.campaign-cart'
            : 'frontEnd.layouts.ajax.cart';
    }
}
