<?php

namespace App\Http\Controllers\Frontend;

use shurjopayv2\ShurjopayLaravelPackage8\Http\Controllers\ShurjopayController;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Brian2694\Toastr\Facades\Toastr;
use App\Models\Category;
use App\Models\Subcategory;
use App\Models\Childcategory;
use App\Models\Product;
use App\Models\ProductVariantPrice;
use App\Models\Size;
use App\Models\Color;
use App\Models\District;
use App\Models\CreatePage;
use App\Models\Campaign;
use App\Models\TiktokPixel;
use App\Models\Banner;
use App\Models\ShippingCharge;
use App\Models\Productcolor;
use App\Models\Productsize;
use App\Models\Customer;
use App\Models\OrderDetails;
use App\Models\Payment;
use App\Models\Order;
use App\Models\Review;
use App\Models\Contact;
use App\Models\GeneralSetting;
use App\Models\IncompleteOrder;
use Session;
use Cart;
use Auth;
use Illuminate\Support\Facades\DB;
use App\Mail\ContactMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Helpers\OrderHelper;
use App\Models\Brand;
use App\Models\Blog;
use App\Models\Vendor;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;




class FrontendController extends Controller
{
    
   

    protected function sendUnauthorizedReport($domain, $reason)
    {
        try {
            \Illuminate\Support\Facades\Http::timeout(4)->post('https://www.creativedesign.com.bd/api/log-unauthorized', [
                'domain' => $domain,
                'ip'     => request()->ip(),
                'reason' => $reason,
                'url'    => request()->fullUrl(),
                'time'   => now()->toDateTimeString()
            ]);
        } catch (\Exception $e) {}
    }
    
    public function index()
    {
        // ✅ Homepage cache (5 min) - reduces DB load on high traffic
        $cacheKey = 'frontend_homepage_v4';
        $data = Cache::remember($cacheKey, 300, function () {
            return $this->getHomepageData();
        });
        return view('frontEnd.layouts.pages.index', $data);
    }

    /**
     * Homepage data (used for cache)
     */
    protected function getHomepageData()
    {
        $generalsetting = Cache::remember('general_setting', 1800, function () {
            return GeneralSetting::where('status', 1)->first();
        });
        $seo = Cache::remember('seo_settings_row', 1800, fn () => DB::table('seo_settings')->first());
        $menucategories = Cache::get('menu_categories_nav');
        if (!$menucategories) {
            $menucategories = Category::where('status', 1)
                ->where('parent_id', 0)
                ->select('id', 'name', 'slug', 'icon', 'image')
                ->with(['subcategories.childcategories'])
                ->orderBy('id', 'ASC')
                ->get();
        }

        $frontcategory = Category::where(['status' => 1, 'parent_id' => 0])
            ->select('id', 'name', 'image', 'icon', 'slug', 'status')
            ->orderBy('id', 'ASC')
            ->limit(16)
            ->get();

        // Banners
        $sliders = Banner::where(['status' => 1, 'category_id' => 1])
            ->select('id', 'image', 'link')
            ->get();
$brands = Brand::where('status', 1)
    ->select('id', 'name', 'slug', 'image')
	 ->limit(12) 
    ->get();
    $blogs = Blog::where('status', 1)
        ->latest()
        ->limit(3)
        ->get();
        $campaognads = Banner::where(['status' => 1, 'category_id' => 7])
            ->select('id', 'image', 'link')
            ->limit(1)
            ->get();

        $sliderbottomads = Banner::where(['status' => 1, 'category_id' => 5])
            ->select('id', 'image', 'link')
            ->limit(3)
            ->get();

        $footertopads = Banner::where(['status' => 1, 'category_id' => 6])
            ->select('id', 'image', 'link')
            ->limit(3)
            ->get();

        $homepageads = Banner::where(['status' => 1, 'category_id' => 10])
            ->select('id', 'image', 'link')
            ->limit(1)
            ->get();

        $homepageads2 = Banner::where(['status' => 1, 'category_id' => 11])
            ->select('id', 'image', 'link')
            ->limit(1)
            ->get();

        $hitdealsbaner = Banner::where(['status' => 1, 'category_id' => 9])
            ->select('id', 'image', 'link')
            ->limit(1)
            ->get();

        // Flash sale – image + reviews eager load
        $productCardRelations = ['prosizes', 'procolors', 'image', 'reviews', 'variantPrices.color', 'variantPrices.size'];

        $flas_sales = Product::where(['status' => 1, 'approval_status' => 'approved', 'flashsale' => 1])
            ->orderBy('id', 'DESC')
            ->select('id', 'name', 'slug', 'new_price', 'old_price', 'sold', 'stock')
            ->with($productCardRelations)
            ->limit(12)
            ->get();

        // Hot deal top – image + reviews + variant stock/price
        $hotdeal_top = Product::where(['status' => 1, 'approval_status' => 'approved', 'topsale' => 1])
            ->orderBy('id', 'DESC')
            ->select('id', 'name', 'slug', 'new_price', 'old_price', 'stock')
            ->with($productCardRelations)
            ->limit(12)
            ->get();

        $hotdeal_bottom = Product::where(['status' => 1, 'approval_status' => 'approved', 'topsale' => 1])
            ->select('id', 'name', 'slug', 'new_price', 'old_price', 'stock')
            ->with($productCardRelations)
            ->skip(12)
            ->limit(12)
            ->get();

        // Category wise home products – products এর image + reviews eager load
        if ($generalsetting && $generalsetting->show_category_wise_products) {
            $homeproducts = Category::where(['front_view' => 1, 'status' => 1])
                ->orderBy('id', 'ASC')
                ->with([
                    'products' => function ($q) {
                        $q->select('id', 'name', 'slug', 'new_price', 'old_price', 'category_id')
                            ->where('status', 1)
                            ->where('approval_status', 'approved')
                            ->with(['image', 'prosizes', 'procolors', 'variantPrices.color', 'variantPrices.size', 'reviews']);
                    }
                ])
                ->get()
                ->map(function ($query) {
                    // প্রতি ক্যাটাগরিতে ১২টা প্রোডাক্ট দেখাবো
                    $query->setRelation('products', $query->products->take(12));
                    return $query;
                });
        } else {
            $homeproducts = null;
        }

        $reviews = Banner::where(['status' => 1, 'category_id' => 8])
            ->select('id', 'image', 'link')
            ->limit(3)
            ->get();

        // All products – image + reviews eager load (যদি হোমে দরকার হয়)
        if ($generalsetting && $generalsetting->show_all_products) {
            $all_products = Product::where(['status' => 1, 'approval_status' => 'approved'])
                ->inRandomOrder()
                ->select('id', 'name', 'slug', 'new_price', 'old_price', 'sold', 'stock')
                ->with(['prosizes', 'procolors', 'variantPrices.color', 'variantPrices.size', 'image', 'reviews'])
                ->limit(12)
                ->get();
        } else {
            $all_products = null;
        }

        // Active Vendors with shop info - for shop cards display
        $vendors = Vendor::where('status', 1)
            ->where('verification_status', 'approved')
            ->select('id', 'shop_name', 'slug', 'logo', 'banner', 'status')
            ->withCount(['products' => function($query) {
                $query->where('status', 1)->where('approval_status', 'approved');
            }])
            ->having('products_count', '>', 0) // Only show vendors with at least one approved product
            ->orderBy('id', 'DESC')
            ->limit(12)
            ->get();

        // ✅ Performance: Single query instead of N+1 for vendor review stats
        $vendorIds = $vendors->pluck('id')->toArray();
        $vendorReviewStats = DB::table('reviews')
            ->join('products', 'reviews.product_id', '=', 'products.id')
            ->whereIn('products.vendor_id', $vendorIds)
            ->where('products.status', 1)
            ->where('products.approval_status', 'approved')
            ->where('reviews.status', 'active')
            ->selectRaw('products.vendor_id, COUNT(*) as total_reviews, AVG(reviews.ratting) as avg_rating')
            ->groupBy('products.vendor_id')
            ->get()
            ->keyBy('vendor_id');

        foreach ($vendors as $vendor) {
            $stats = $vendorReviewStats->get($vendor->id);
            $vendor->total_reviews = $stats ? (int) $stats->total_reviews : 0;
            $vendor->average_rating = $stats && $stats->total_reviews > 0 ? round((float) $stats->avg_rating, 1) : 0;
        }

        return compact(
            'seo',
            'generalsetting',
            'menucategories',
            'sliders',
            'brands',
            'blogs',
            'frontcategory',
            'hotdeal_top',
            'hotdeal_bottom',
            'homeproducts',
            'sliderbottomads',
            'footertopads',
            'homepageads2',
            'hitdealsbaner',
            'homepageads',
            'flas_sales',
            'campaognads',
            'reviews',
            'all_products',
            'vendors'
        );
    }

    // ===========================
    // Add to cart with variant + stock check
    // ===========================
    public function cartStore(Request $request)
    {
        $request->validate([
            'id'            => 'required|integer',
            'qty'           => 'nullable|integer|min:1',
            'product_color' => 'nullable|integer',
            'product_size'  => 'nullable|integer',
        ]);
        
        // =========================================================
        // [START] এডমিন প্যানেল থেকে সেট করা ডাইনামিক লিমিট লজিক
        // =========================================================
        
        // ১. ডাটাবেস থেকে সেটিং লোড করা
        $setting = GeneralSetting::select('order_limit_time', 'order_limit_qty')->first();
        
        // যদি সেটিং না পায় বা ভ্যালু না থাকে, তবে ডিফল্ট হিসেবে ৪৮ ঘন্টা এবং ২ বার ধরবে
        $limitHours = $setting->order_limit_time ?? 48; 
        $limitQty   = $setting->order_limit_qty ?? 2;

        $productId = $request->id;
        // ডাইনামিক সময় ক্যালকুলেশন
        $timeLimit = Carbon::now()->subHours($limitHours); 
        $currentIp = $request->ip();

        // কুয়েরি তৈরি
        $query = DB::table('orders')
            ->join('order_details', 'orders.id', '=', 'order_details.order_id')
            ->where('order_details.product_id', $productId)
            ->where('orders.created_at', '>=', $timeLimit);

        // ইউজার বা আইপি চেক
        if (Auth::guard('customer')->check()) {
            $customerId = Auth::guard('customer')->user()->id;
            $query->where('orders.customer_id', $customerId);
        } else {
            // [সতর্কতা] আপনার ডাটাবেসে কলামের নাম 'ip_address' না 'ip' সেটা নিশ্চিত হয়ে নিবেন
            $query->where('orders.ip_address', $currentIp); 
        }

        // মোট কতবার অর্ডার করেছে তা গণনা
        $orderCount = $query->count();

        // যদি লিমিটের সমান বা বেশি হয়, তবে আটকাবে
        if ($orderCount >= $limitQty) {
           if ($request->ajax() || $request->wantsJson()) {
               return response()->json(['success' => false, 'message' => 'Order limit exceeded']);
           }
           return redirect()->back()->with('show_order_limit_modal', true);
        }
        
        // =========================================================
        // [END] লজিক শেষ
        // =========================================================

        $product = Product::with('image')->findOrFail($request->id);

        // Variant stock is authoritative when a size/color is selected.
        $selectedVariant = ProductVariantPrice::where('product_id', $product->id)
            ->when($request->product_color, fn ($q) => $q->where('color_id', $request->product_color))
            ->when($request->product_size, fn ($q) => $q->where('size_id', $request->product_size))
            ->first();

        // 1) Product stock, overridden by the selected size/color stock
        $availableStock = $selectedVariant && $selectedVariant->stock !== null
            ? (int) $selectedVariant->stock
            : $this->getAvailableStock($product);
        $requestedQty   = max(1, (int)($request->qty ?? 1));

        // যদি স্টকের কোন কলামই না থাকে (stock/qty/quantity নেই), তখন স্টক চেক স্কিপ করবে
        if ($availableStock !== null) {

            // স্টক ০ বা কম হলে সরাসরি ব্লক
            if ($availableStock <= 0) {
                Toastr::error('এই পণ্যটি বর্তমানে স্টক আউট, অর্ডার করা যাবে না।', 'স্টক আউট!');
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => 'স্টক আউট']);
                }
                return redirect()->back()->withInput();
            }

            // কার্টে আগে থেকে একই প্রোডাক্ট (একই ভ্যারিয়েন্ট) কত qty আছে, সেটা বের করি
            $alreadyInCart = Cart::instance('shopping')
                ->search(function ($cartItem, $rowId) use ($product, $request) {
                    if ($cartItem->id != $product->id) {
                        return false;
                    }

                    $colorId = $request->product_color ?? null;
                    $sizeId  = $request->product_size ?? null;

                    return ($cartItem->options->color_id ?? null) == $colorId
                        && ($cartItem->options->size_id ?? null) == $sizeId;
                })
                ->sum('qty');

            $totalRequested = $alreadyInCart + $requestedQty;

            // স্টকের চেয়ে বেশি চাইলে error
            if ($totalRequested > $availableStock) {
                Toastr::error(
                    'স্টকে যত আছে তার বেশি অর্ডার করা যাবে না। সর্বোচ্চ ' . $availableStock . ' টি নিতে পারবেন।',
                    'স্টক সীমা!'
                );
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => 'স্টক সীমা']);
                }
                return redirect()->back()->withInput();
            }
        }

        // Variant price খুঁজে বের করো
        $variantPrice = ProductVariantPrice::where('product_id', $product->id)
            ->when($request->product_color, function ($q) use ($request) {
                $q->where('color_id', $request->product_color);
            })
            ->when($request->product_size, function ($q) use ($request) {
                $q->where('size_id', $request->product_size);
            })
            ->first();

        // এখন price ঠিকভাবে fallback করো
        if ($variantPrice && $variantPrice->price > 0) {
            $finalPrice = $variantPrice->price;
        } elseif (!empty($product->new_price) && $product->new_price > 0) {
            $finalPrice = $product->new_price;
        } else {
            $finalPrice = 1; // fallback price
        }

        // সাইজ ও কালারের নাম (চেকআউট ও অর্ডার ডিসপ্লে এর জন্য)
        $sizeName = null;
        $colorName = null;
        if ($request->product_size) {
            $size = Size::find($request->product_size);
            $sizeName = $size ? ($size->sizeName ?? $size->size_name ?? null) : null;
        }
        if ($request->product_color) {
            $color = Color::find($request->product_color);
            $colorName = $color ? ($color->getDisplayName() ?? $color->colorName ?? $color->color_name ?? null) : null;
        }

        // সব ঠিক থাকলে এখন কার্টে Add করো
        Cart::instance('shopping')->add([
            'id'    => $product->id,
            'name'  => $product->name,
            'qty'   => $requestedQty,
            'price' => $finalPrice,
            'options' => [
                'color_id'         => $request->product_color ?? null,
                'size_id'          => $request->product_size ?? null,
                'product_size'     => $sizeName,
                'product_color'    => $colorName,
                'variant_price_id' => $variantPrice->id ?? null,
                'image'            => $product->image->image ?? null,
                'slug'             => $product->slug,
                'purchase_price'   => $product->purchase_price ?? null,
            ],
        ]);

        Toastr::success('Product added to cart successfully', 'Success!');

        // AJAX এর জন্য JSON রিটার্ন
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        // order_now থাকলে checkout এ পাঠাও
        if ($request->has('order_now')) {
            return redirect()->route('customer.checkout');
        }

        return redirect()->back();
    }

    // ===========================
    // Rest of original controller methods
    // ===========================
	
	
	
	
	
	public function brand($slug, Request $request)
{
    $brand = Brand::where('slug', $slug)
        ->where('status', 1)
        ->firstOrFail();

    $products = Product::where('brand_id', $brand->id)
        ->where('status', 1)
        ->where('approval_status', 'approved')
        ->select('id', 'name', 'slug', 'new_price', 'old_price', 'stock')
        ->with(['image', 'reviews', 'prosizes', 'procolors', 'variantPrices.color', 'variantPrices.size']);

    // sorting (same pattern as category/shop)
    if ($request->sort == 1) {
        $products = $products->orderBy('created_at', 'desc');
    } elseif ($request->sort == 2) {
        $products = $products->orderBy('created_at', 'asc');
    } elseif ($request->sort == 3) {
        $products = $products->orderBy('new_price', 'desc');
    } elseif ($request->sort == 4) {
        $products = $products->orderBy('new_price', 'asc');
    } elseif ($request->sort == 5) {
        $products = $products->orderBy('name', 'asc');
    } elseif ($request->sort == 6) {
        $products = $products->orderBy('name', 'desc');
    } else {
        $products = $products->latest();
    }

    $min_price = $products->min('new_price');
    $max_price = $products->max('new_price');

    if ($request->min_price && $request->max_price) {
        $products = $products->whereBetween('new_price', [
            $request->min_price,
            $request->max_price
        ]);
    }

    $products = $products->paginate(24);

    return view('frontEnd.layouts.pages.brand', compact(
        'brand',
        'products',
        'min_price',
        'max_price'
    ));
}

    public function vendorShop($slug, Request $request)
    {
        $vendor = Vendor::where('slug', $slug)
            ->where('status', 1)
            ->where('verification_status', 'approved')
            ->firstOrFail();

        // Get vendor products
        $products = Product::where('vendor_id', $vendor->id)
            ->where('status', 1)
            ->where('approval_status', 'approved')
            ->select('id', 'name', 'slug', 'new_price', 'old_price', 'stock', 'sold')
            ->with(['image', 'reviews', 'prosizes', 'procolors', 'variantPrices.color', 'variantPrices.size']);

        // Sorting
        if ($request->sort == 1) {
            $products = $products->orderBy('created_at', 'desc');
        } elseif ($request->sort == 2) {
            $products = $products->orderBy('created_at', 'asc');
        } elseif ($request->sort == 3) {
            $products = $products->orderBy('new_price', 'desc');
        } elseif ($request->sort == 4) {
            $products = $products->orderBy('new_price', 'asc');
        } else {
            $products = $products->latest();
        }

        $products = $products->paginate(24);

        // Calculate vendor stats
        $vendorProducts = Product::where('vendor_id', $vendor->id)
            ->where('status', 1)
            ->where('approval_status', 'approved')
            ->pluck('id');
        
        $reviews = Review::whereIn('product_id', $vendorProducts)
            ->where('status', 'active')
            ->get();
        
        $vendor->total_reviews = $reviews->count();
        $vendor->average_rating = $reviews->count() > 0 
            ? round($reviews->avg('ratting'), 1) 
            : 0;
        $vendor->total_products = $vendorProducts->count();

        // General setting
        $generalsetting = GeneralSetting::where('status', 1)->limit(1)->first();
        $seo = DB::table('seo_settings')->first();

        return view('frontEnd.layouts.pages.vendor-shop', compact(
            'vendor',
            'products',
            'generalsetting',
            'seo'
        ));
    }
	
    /**
     * অ্যাবানডনড কার্ট (ইনকমপ্লিট অর্ডার) সেভ।
     *
     * আগে এই ফাংশনের কোনো রুট ছিল না, আর চেকআউট পেজ যে রুটে POST করত সেটি
     * ['auth:admin','admin'] middleware-এর পেছনে থাকায় কাস্টমারের প্রতিটি
     * রিকোয়েস্ট ফেল করত। এখন এটিই একমাত্র ইমপ্লিমেন্টেশন এবং
     * route('incomplete.order.store') সরাসরি এখানে আসে।
     */
    public function storeIncompleteOrder(Request $request)
    {
        try {
            $validated = $request->validate([
                'name'          => 'nullable|string|max:255',
                'phone'         => 'nullable|string|max:55',
                'address'       => 'nullable|string|max:1000',
                'items'         => 'nullable|array',
                'items.*.id'    => 'nullable',
                'items.*.name'  => 'nullable|string|max:255',
                'items.*.qty'   => 'nullable|numeric',
                'items.*.price' => 'nullable|numeric',
                'items.*.image' => 'nullable|string|max:1000',
                'items.*.link'  => 'nullable|string|max:1000',
                'product_image' => 'nullable|string|max:1000',
                'product_link'  => 'nullable|string|max:1000',
                'total_amount'  => 'nullable|numeric',
                'source'        => 'nullable|string|max:100',
            ]);

            $phone = preg_replace('/[^0-9+]/', '', (string) ($validated['phone'] ?? ''));
            $items = $validated['items'] ?? [];

            // ফোন নম্বর ছাড়া লিডটির কোনো মূল্য নেই — ফলো-আপ করা যাবে না।
            if ($phone === '' || strlen($phone) < 6) {
                return response()->json([
                    'status'  => 'ignore',
                    'message' => 'Phone number required to save an incomplete order.',
                ]);
            }

            if (empty($items)) {
                return response()->json([
                    'status'  => 'ignore',
                    'message' => 'No items in cart, skip saving.',
                ]);
            }

            $total = isset($validated['total_amount']) ? (float) $validated['total_amount'] : 0;

            if ($total <= 0) {
                $total = collect($items)->sum(function ($item) {
                    return (float) ($item['price'] ?? 0) * max(1, (int) ($item['qty'] ?? 1));
                });
            }

            $firstItem = collect($items)->first();

            // Keep one evolving lead per phone and campaign, rather than creating a row
            // on every keystroke. Different campaign variants remain independently measurable.
            $campaignId = Session::get('active_campaign_id');
            $userAgent = mb_substr((string) $request->userAgent(), 0, 2000);
            [$deviceType, $deviceName] = $this->detectCheckoutDevice($userAgent);

            // The server owns IP/device metadata. Never trust a browser-supplied IP.
            // Keep the first checkout-entry timestamp while refreshing last activity.
            $incomplete = IncompleteOrder::firstOrNew([
                'phone' => $phone,
                'campaign_id' => $campaignId,
            ]);
            if (!$incomplete->exists || !$incomplete->checkout_started_at) {
                $incomplete->checkout_started_at = now();
            }
            $incomplete->fill([
                'name'          => $validated['name'] ?? null,
                'address'       => $validated['address'] ?? null,
                'items'         => $items,
                'product_image' => $validated['product_image'] ?? ($firstItem['image'] ?? null),
                'product_link'  => $validated['product_link'] ?? ($firstItem['link'] ?? null),
                'total_amount'  => $total,
                'campaign_id'   => $campaignId,
                'source'        => $validated['source'] ?? 'checkout',
                'device_type'   => $deviceType,
                'device_name'   => $deviceName,
                'ip_address'    => $request->ip(),
                'user_agent'    => $userAgent,
                'last_activity_at' => now(),
            ]);
            $incomplete->save();

            return response()->json([
                'status'  => 'success',
                'message' => 'Incomplete order saved successfully.',
                'data'    => ['id' => $incomplete->id],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation failed.',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Incomplete order save failed: ' . $e->getMessage());

            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to save incomplete order.',
            ], 500);
        }
    }

    /** @return array{0:string,1:string} */
    private function detectCheckoutDevice(string $userAgent): array
    {
        $type = preg_match('/tablet|ipad|playbook|silk|android(?!.*mobile)/i', $userAgent)
            ? 'tablet'
            : (preg_match('/mobile|iphone|ipod|android.*mobile|windows phone/i', $userAgent) ? 'mobile' : 'desktop');

        $platform = match (true) {
            preg_match('/iphone|ipad|ipod/i', $userAgent) === 1 => 'iOS',
            preg_match('/android/i', $userAgent) === 1 => 'Android',
            preg_match('/windows/i', $userAgent) === 1 => 'Windows',
            preg_match('/macintosh|mac os x/i', $userAgent) === 1 => 'macOS',
            preg_match('/linux/i', $userAgent) === 1 => 'Linux',
            default => 'Unknown OS',
        };

        $browser = match (true) {
            preg_match('/edg\//i', $userAgent) === 1 => 'Edge',
            preg_match('/opr\//i', $userAgent) === 1 => 'Opera',
            preg_match('/chrome\//i', $userAgent) === 1 => 'Chrome',
            preg_match('/safari\//i', $userAgent) === 1 => 'Safari',
            preg_match('/firefox\//i', $userAgent) === 1 => 'Firefox',
            default => 'Unknown browser',
        };

        return [$type, $platform . ' · ' . $browser];
    }

    public function hotdeals(Request $request)
    {
        $products = Product::where(['status' => 1, 'approval_status' => 'approved', 'topsale' => 1])
            ->select('id', 'name', 'slug', 'new_price', 'old_price','stock')
            ->with(['image', 'reviews', 'prosizes', 'procolors', 'variantPrices.color', 'variantPrices.size']);

        if ($request->sort == 1) {
            $products = $products->orderBy('created_at', 'desc');
        } elseif ($request->sort == 2) {
            $products = $products->orderBy('created_at', 'asc');
        } elseif ($request->sort == 3) {
            $products = $products->orderBy('new_price', 'desc');
        } elseif ($request->sort == 4) {
            $products = $products->orderBy('new_price', 'asc');
        } elseif ($request->sort == 5) {
            $products = $products->orderBy('name', 'asc');
        } elseif ($request->sort == 6) {
            $products = $products->orderBy('name', 'desc');
        } else {
            $products = $products->latest();
        }

        $min_price = $products->min('new_price');
        $max_price = $products->max('new_price');
        if($request->min_price && $request->max_price){
            $products = $products->where('new_price','>=',$request->min_price);
            $products = $products->where('new_price','<=',$request->max_price);
        }
        $products = $products->paginate(36);
        return view('frontEnd.layouts.pages.hotdeals', compact('products'));
    }

    public function sellers(Request $request)
    {
        $generalSetting = GeneralSetting::where('status', 1)->first();
        if (!$generalSetting || ($generalSetting->vendor_enabled ?? 1) != 1) {
            abort(404);
        }

        // Get all active and verified vendors
        $vendors = Vendor::where('status', 1)
            ->where('verification_status', 'approved')
            ->select('id', 'shop_name', 'slug', 'logo', 'banner', 'status', 'verification_status')
            ->withCount(['products' => function($query) {
                $query->where('status', 1)->where('approval_status', 'approved');
            }])
            ->having('products_count', '>', 0) // Only show vendors with at least one approved product
            ->orderBy('id', 'DESC');

        // Search functionality
        if ($request->keyword) {
            $vendors->where('shop_name', 'like', '%' . $request->keyword . '%');
        }

        $vendors = $vendors->paginate(24);

        // Calculate average rating for each vendor
        foreach ($vendors as $vendor) {
            $vendorProducts = Product::where('vendor_id', $vendor->id)
                ->where('status', 1)
                ->where('approval_status', 'approved')
                ->pluck('id');
            
            $reviews = Review::whereIn('product_id', $vendorProducts)
                ->where('status', 'active')
                ->get();
            
            $vendor->total_reviews = $reviews->count();
            $vendor->average_rating = $reviews->count() > 0 
                ? round($reviews->avg('ratting'), 1) 
                : 0;
        }

        // General setting
        $generalsetting = GeneralSetting::where('status', 1)->limit(1)->first();
        $seo = DB::table('seo_settings')->first();

        return view('frontEnd.layouts.pages.sellers', compact(
            'vendors',
            'generalsetting',
            'seo'
        ));
    }

    public function shop(Request $request)
    {
        $products = Product::where(['status' => 1, 'approval_status' => 'approved'])
            ->select('id', 'name', 'slug', 'new_price', 'old_price','stock')
            ->with(['image', 'reviews', 'prosizes', 'procolors', 'variantPrices.color', 'variantPrices.size']);

        if ($request->sort == 1) {
            $products = $products->orderBy('created_at', 'desc');
        } elseif ($request->sort == 2) {
            $products = $products->orderBy('created_at', 'asc');
        } elseif ($request->sort == 3) {
            $products = $products->orderBy('new_price', 'desc');
        } elseif ($request->sort == 4) {
            $products = $products->orderBy('new_price', 'asc');
        } elseif ($request->sort == 5) {
            $products = $products->orderBy('name', 'asc');
        } elseif ($request->sort == 6) {
            $products = $products->orderBy('name', 'desc');
        } else {
            $products = $products->latest();
        }

        $min_price = $products->min('new_price');
        $max_price = $products->max('new_price');
        if($request->min_price && $request->max_price){
            $products = $products->where('new_price','>=',$request->min_price);
            $products = $products->where('new_price','<=',$request->max_price);
        }
        $products = $products->paginate(36);
        return view('frontEnd.layouts.pages.shop', compact('products'));
    }




    public function flashsales(Request $request)
    {
        $products = Product::where(['status' => 1, 'approval_status' => 'approved', 'flashsale' => 1])
            ->select('id', 'name', 'slug', 'new_price', 'old_price','stock')
            ->with(['image', 'reviews', 'prosizes', 'procolors', 'variantPrices.color', 'variantPrices.size']);

        if ($request->sort == 1) {
            $products = $products->orderBy('created_at', 'desc');
        } elseif ($request->sort == 2) {
            $products = $products->orderBy('created_at', 'asc');
        } elseif ($request->sort == 3) {
            $products = $products->orderBy('new_price', 'desc');
        } elseif ($request->sort == 4) {
            $products = $products->orderBy('new_price', 'asc');
        } elseif ($request->sort == 5) {
            $products = $products->orderBy('name', 'asc');
        } elseif ($request->sort == 6) {
            $products = $products->orderBy('name', 'desc');
        } else {
            $products = $products->latest();
        }

        $min_price = $products->min('new_price');
        $max_price = $products->max('new_price');
        if($request->min_price && $request->max_price){
            $products = $products->where('new_price','>=',$request->min_price);
            $products = $products->where('new_price','<=',$request->max_price);
        }
        $products = $products->paginate(36);
        return view('frontEnd.layouts.pages.flashsales', compact('products'));
    }

    public function category($slug, Request $request)
    {
        $soldShow = $request->sold=='show'?true:false;
        $category = Category::where(['slug' => $slug, 'status' => 1])->first();

        $products = Product::where(['status' => 1, 'approval_status' => 'approved', 'category_id' => $category->id])
            ->select('id', 'name', 'slug', 'new_price', 'old_price', 'category_id','sold','stock')
            ->with(['image', 'reviews', 'prosizes', 'procolors', 'variantPrices.color', 'variantPrices.size']);
        $subcategories = Subcategory::where('category_id', $category->id)->get();

        if ($request->sort == 1) {
            $products = $products->orderBy('created_at', 'desc');
        } elseif ($request->sort == 2) {
            $products = $products->orderBy('created_at', 'asc');
        } elseif ($request->sort == 3) {
            $products = $products->orderBy('new_price', 'desc');
        } elseif ($request->sort == 4) {
            $products = $products->orderBy('new_price', 'asc');
        } elseif ($request->sort == 5) {
            $products = $products->orderBy('name', 'asc');
        } elseif ($request->sort == 6) {
            $products = $products->orderBy('name', 'desc');
        } else {
            $products = $products->latest();
        }

        $min_price = $products->min('new_price');
        $max_price = $products->max('new_price');
        if($request->min_price && $request->max_price){
            $products = $products->where('new_price','>=',$request->min_price);
            $products = $products->where('new_price','<=',$request->max_price);
        }

        $selectedSubcategories = $request->input('subcategory', []);
        $products = $products->when($selectedSubcategories, function ($query) use ($selectedSubcategories) {
            return $query->whereHas('subcategory', function ($subQuery) use ($selectedSubcategories) {
                $subQuery->whereIn('id', $selectedSubcategories);
            });
        });

        $products = $products->paginate(24);
        return view('frontEnd.layouts.pages.category', compact('category', 'products', 'subcategories', 'min_price', 'max_price','soldShow'));
    }

    public function subcategory($slug, Request $request)
    {
        $soldShow = $request->sold=='show'?true:false;
        $subcategory = Subcategory::where(['slug' => $slug, 'status' => 1])->first();
        $products = Product::where(['status' => 1, 'approval_status' => 'approved', 'subcategory_id' => $subcategory->id])
            ->select('id', 'name', 'slug', 'new_price', 'old_price', 'category_id', 'subcategory_id','sold','stock')
            ->with(['image', 'reviews', 'prosizes', 'procolors', 'variantPrices.color', 'variantPrices.size']);
        $childcategories = Childcategory::where('subcategory_id', $subcategory->id)->get();

        if ($request->sort == 1) {
            $products = $products->orderBy('created_at', 'desc');
        } elseif ($request->sort == 2) {
            $products = $products->orderBy('created_at', 'asc');
        } elseif ($request->sort == 3) {
            $products = $products->orderBy('new_price', 'desc');
        } elseif ($request->sort == 4) {
            $products = $products->orderBy('new_price', 'asc');
        } elseif ($request->sort == 5) {
            $products = $products->orderBy('name', 'asc');
        } elseif ($request->sort == 6) {
            $products = $products->orderBy('name', 'desc');
        } else {
            $products = $products->latest();
        }

        $min_price = $products->min('new_price');
        $max_price = $products->max('new_price');
        if($request->min_price && $request->max_price){
            $products = $products->where('new_price','>=',$request->min_price);
            $products = $products->where('new_price','<=',$request->max_price);
        }

        $selectedChildcategories = $request->input('childcategory', []);
        $products = $products->when($selectedChildcategories, function ($query) use ($selectedChildcategories) {
            return $query->whereHas('childcategory', function ($subQuery) use ($selectedChildcategories) {
                $subQuery->whereIn('id', $selectedChildcategories);
            });
        });

        $products = $products->paginate(24);
        $impproducts = Product::where(['status' => 1, 'topsale' => 1])
            ->with('image')
            ->limit(6)
            ->select('id', 'name', 'slug')
            ->get();

        return view('frontEnd.layouts.pages.subcategory', compact('subcategory', 'products', 'impproducts', 'childcategories', 'max_price', 'min_price','soldShow'));
    }

    public function products($slug, Request $request)
    {
        $soldShow = $request->sold=='show'?true:false;
        $childcategory = Childcategory::where(['slug' => $slug, 'status' => 1])->first();
        $childcategories = Childcategory::where('subcategory_id', $childcategory->subcategory_id)->get();
        $products = Product::where(['status' => 1, 'approval_status' => 'approved', 'childcategory_id' => $childcategory->id])
            ->with(['category', 'image', 'reviews', 'prosizes', 'procolors', 'variantPrices.color', 'variantPrices.size'])
            ->select('id', 'name', 'slug', 'new_price', 'old_price', 'category_id', 'subcategory_id', 'childcategory_id','sold','stock');

        if ($request->sort == 1) {
            $products = $products->orderBy('created_at', 'desc');
        } elseif ($request->sort == 2) {
            $products = $products->orderBy('created_at', 'asc');
        } elseif ($request->sort == 3) {
            $products = $products->orderBy('new_price', 'desc');
        } elseif ($request->sort == 4) {
            $products = $products->orderBy('new_price', 'asc');
        } elseif ($request->sort == 5) {
            $products = $products->orderBy('name', 'asc');
        } elseif ($request->sort == 6) {
            $products = $products->orderBy('name', 'desc');
        } else {
            $products = $products->latest();
        }

        $min_price = $products->min('new_price');
        $max_price = $products->max('new_price');
        if($request->min_price && $request->max_price){
            $products = $products->where('new_price','>=',$request->min_price);
            $products = $products->where('new_price','<=',$request->max_price);
        }

        $products = $products->paginate(24);
        $impproducts = Product::where(['status' => 1, 'approval_status' => 'approved', 'topsale' => 1])
            ->with('image')
            ->limit(6)
            ->select('id', 'name', 'slug','stock')
            ->get();

        return view('frontEnd.layouts.pages.childcategory', compact('childcategory', 'products', 'impproducts', 'min_price', 'max_price', 'childcategories','soldShow'));
    }

    public function details($slug)
    {
        $cacheKey = 'product_details_' . $slug;
        $details = Cache::remember($cacheKey, 600, function () use ($slug) {
            return Product::where(['slug' => $slug, 'status' => 1, 'approval_status' => 'approved'])
                ->with([
                    'image',
                    'images',
                    'category',
                    'subcategory',
                    'childcategory',
                    'brand',
                    'variantPrices.color',
                    'variantPrices.size',
                    'wholesalePrices'
                ])
                ->firstOrFail();
        });

        // Related products: limit 12, exclude current, eager load to avoid N+1
        $products = Product::where('category_id', $details->category_id)
            ->where('id', '!=', $details->id)
            ->where(['status' => 1, 'approval_status' => 'approved'])
            ->with(['image', 'category', 'brand', 'reviews', 'prosizes', 'procolors', 'variantPrices.color', 'variantPrices.size'])
            ->select('id', 'name', 'slug', 'new_price', 'old_price', 'stock', 'category_id', 'brand_id', 'pro_unit')
            ->limit(12)
            ->get();

        $shippingcharge = Cache::remember('shipping_charges_active', 300, fn() => ShippingCharge::where('status', 1)->get());
        $reviews = Review::where('product_id', $details->id)
            ->where('status', 'active')
            ->latest()
            ->limit(50)
            ->get();

        return view('frontEnd.layouts.pages.details', compact(
            'details',
            'products',
            'shippingcharge',
            'reviews'
        ));
    }

    public function quickview(Request $request)
    {
        $data['data'] = Product::where(['id' => $request->id, 'status' => 1, 'approval_status' => 'approved'])
            ->with('images')
            ->withCount('reviews')
            ->first();

        $data = view('frontEnd.layouts.ajax.quickview', $data)->render();
        if ($data != '') {
            echo $data;
        }
    }

    /**
     * 🔥 Quick-Order পপআপের জন্য প্রোডাক্ট ডাটা JSON আকারে return করে।
     * Order Now / Cart বাটনে ক্লিক করলে এখান থেকে সাইজ/কালার/ভ্যারিয়েন্ট দাম আসে
     * (হোমপেজে embedded `CDP` ডাটা থাকলে সে ডাটাই ব্যবহার হয় — দ্রুত হয়)।
     */
    public function quickOrderData($id)
    {
        $p = Product::where(['id' => $id, 'status' => 1, 'approval_status' => 'approved'])
            ->with(['image', 'variantPrices.color', 'variantPrices.size', 'prosizes', 'procolors'])
            ->first();

        if (!$p) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        $img = optional($p->image)->image
            ?? DB::table('productimages')->where('product_id', $p->id)->value('image')
            ?? 'public/uploads/default.webp';

        $url          = route('product', $p->slug);

        // ভ্যারিয়েন্ট স্টক থাকলে সব সাইজ/কালারের মোট স্টকই আসল স্টক
        $variantRows = $p->variantPrices ?? collect();
        $hasVariantStock = $variantRows->contains(fn ($v) => $v->stock !== null);
        $displayStock = !empty($p->is_wholesale)
            ? 99999
            : ($hasVariantStock
                ? (int) $variantRows->sum(fn ($v) => max(0, (int) $v->stock))
                : (int) ($p->stock ?? 0));

        $sizes = []; $colors = []; $variants = [];

        if ($p->variantPrices && $p->variantPrices->count()) {
            foreach ($p->variantPrices as $v) {
                $variants[] = [
                    's'  => $v->size_id  ? (int) $v->size_id  : null,
                    'c'  => $v->color_id ? (int) $v->color_id : null,
                    'p'  => (float) $v->price,
                    'st' => $v->stock === null ? null : (int) $v->stock,
                ];
                if ($v->size_id && $v->size) {
                    if (!isset($sizes[$v->size_id])) {
                        $sizes[$v->size_id] = ['id' => (int) $v->size_id, 'name' => $v->size->sizeName, 'stock' => 0, 'has_stock' => false];
                    }
                    if ($v->stock !== null) {
                        $sizes[$v->size_id]['stock']   += max(0, (int) $v->stock);
                        $sizes[$v->size_id]['has_stock'] = true;
                    }
                }
                if ($v->color_id && $v->color) {
                    $colors[$v->color_id] = ['id' => (int) $v->color_id, 'name' => $v->color->colorName, 'hex' => $v->color->color];
                }
            }
        }

        // ফলব্যাক — পুরনো productsizes / productcolors টেবিল
        if (empty($sizes) && $p->prosizes && $p->prosizes->count()) {
            foreach ($p->prosizes as $ps) {
                $sz = $ps->size ?? null;
                if ($sz) { $sizes[$sz->id] = ['id' => (int) $sz->id, 'name' => $sz->sizeName]; }
            }
        }
        if (empty($colors) && $p->procolors && $p->procolors->count()) {
            foreach ($p->procolors as $pc) {
                $cl = $pc->color ?? null;
                if ($cl) { $colors[$cl->id] = ['id' => (int) $cl->id, 'name' => $cl->colorName, 'hex' => $cl->color]; }
            }
        }

        $sizes  = array_values($sizes);
        $colors = array_values($colors);

        // পপআপের অর্ডার সামারিতে ডেলিভারি চার্জ দেখানোর জন্য ডিফল্ট শিপিং চার্জ
        $shippingFee = 0;
        if (!empty($p->is_digital)) {
            $shippingFee = 0;
        } else {
            $firstCharge = \App\Models\ShippingCharge::where('status', 1)->first();
            $shippingFee = $firstCharge ? (float) $firstCharge->amount : 0;
        }

        return response()->json([
            'id'       => $p->id,
            'name'     => $p->name,
            'img'      => $img,
            'url'      => $url,
            'price'    => (float) $p->new_price,
            'old'      => (float) ($p->old_price ?? 0),
            'stock'    => $displayStock,
            'sizes'    => $sizes,
            'colors'   => $colors,
            'variants' => $variants,
            'shipping' => $shippingFee,
        ]);
    }

    /**
     * 🔥 কুইক-অর্ডার অর্ডার প্লেসমেন্ট (পপআপ থেকেই অর্ডার কমপ্লিট)
     *
     * Product Card → Order Now → সাইজ/পরিমাণ → কাস্টমার তথ্য → Confirm
     * পুরো ফ্লো পপআপেই শেষ হয়; চেকআউট পেজে যেতে হয় না। অর্ডার তৈরি হয়
     * normal checkout-এর মতোই (QuickOrderService দেখুন)।
     */
    public function quickOrderPlace(Request $request)
    {
        $request->validate([
            'id'            => 'required|integer',
            'qty'           => 'required|integer|min:1',
            'product_size'  => 'nullable|integer',
            'product_color' => 'nullable|integer',
            'name'          => 'required|string|max:100',
            'phone'         => 'required|string|max:20',
            'address'       => 'required|string|max:500',
            'note'          => 'nullable|string|max:500',
        ]);

        try {
            $order = app(\App\Services\QuickOrderService::class)->placeOrder([
                'product_id'  => $request->id,
                'qty'         => $request->qty,
                'size_id'     => $request->product_size,
                'color_id'    => $request->product_color,
                'name'        => trim($request->name),
                'phone'       => trim($request->phone),
                'address'     => trim($request->address),
                'note'        => trim($request->note ?? ''),
            ], $request);

            return response()->json([
                'success'    => true,
                'message'    => 'অর্ডারটি সফলভাবে গ্রহণ করা হয়েছে!',
                'invoice_id' => $order->invoice_id,
                'order_id'   => $order->id,
                'amount'     => (float) $order->amount,
                'order_url'  => route('customer.order_success', $order->id),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'সব ঘর সঠিকভাবে পূরণ করুন।'], 422);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            \Log::error('Quick order failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => 'অর্ডারটি করা যায়নি। আবার চেষ্টা করুন।'], 500);
        }
    }

    public function livesearch(Request $request)
    {
        $products = Product::select('id', 'name', 'slug', 'new_price', 'old_price','stock')
            ->where('status', 1)
            ->where('approval_status', 'approved')
            ->with(['image', 'reviews', 'prosizes', 'procolors', 'variantPrices.color', 'variantPrices.size']);
        if ($request->keyword) {
            $products = $products->where('name', 'LIKE', '%' . $request->keyword . "%");
        }
        if ($request->category) {
            $products = $products->where('category_id', $request->category);
        }
        $products = $products->get();

        if (empty($request->category) && empty($request->keyword)) {
            $products = [];
        }
        return view('frontEnd.layouts.ajax.search', compact('products'));
    }

    public function search(Request $request)
    {
        $products = Product::select('id', 'name', 'slug', 'new_price', 'old_price','stock')
            ->where('status', 1)
            ->where('approval_status', 'approved')
            ->with(['image', 'reviews', 'prosizes', 'procolors', 'variantPrices.color', 'variantPrices.size']);
        if ($request->keyword) {
            $products = $products->where('name', 'LIKE', '%' . $request->keyword . "%");
        }
        if ($request->category) {
            $products = $products->where('category_id', $request->category);
        }
        $products = $products->paginate(36);
        $keyword = $request->keyword;
        return view('frontEnd.layouts.pages.search', compact('products', 'keyword'));
    }

    public function shipping_charge(Request $request)
    {
        // ⭐ Free Delivery Check - যদি সব প্রোডাক্ট free delivery eligible হয়, shipping charge 0
        $hasAllFreeDelivery = \App\Http\Controllers\Frontend\ShoppingController::hasAllFreeDeliveryProducts();
        
        if ($hasAllFreeDelivery || $request->id == 'free_delivery') {
            Session::put('shipping', 0);
            Session::put('shipping_id', null);
        } else {
            $shipping = ShippingCharge::where(['id' => $request->id])->first();
            if ($shipping) {
                Session::put('shipping', $shipping->amount);
                Session::put('shipping_id', $shipping->id);
            }
        }
        return view(\App\Http\Controllers\Frontend\ShoppingController::cartPartial());
    }

    public function contact()
    {
        $contact = Contact::where('status', 1)->first();
        $cmnmenu = CreatePage::where('status', 1)->get();

        return view('frontEnd.layouts.pages.contact', compact('contact', 'cmnmenu'));
    }

    public function contactStore(Request $request)
    {
        $request->validate([
            'name'    => 'required|string|max:255',
            'phone'   => 'required|numeric',
            'email'   => 'required|email|max:255',
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        \App\Models\Contact::create([
            'name'    => $request->name,
            'mobile'  => $request->phone,
            'email'   => $request->email,
            'subject' => $request->subject,
            'message' => $request->message,
        ]);

        $adminEmail = 'admin@example.com';
        try {
            \Mail::to($adminEmail)->send(new \App\Mail\ContactMail($request->all()));
        } catch (\Exception $e) {
            \Log::error('Email send failed: ' . $e->getMessage());
        }

        Toastr::success('✅ আপনার বার্তাটি সফলভাবে পাঠানো হয়েছে!', 'Success');
        return back();
    }

    public function page($slug)
    {
        $page = CreatePage::where('slug', $slug)->firstOrFail();
        return view('frontEnd.layouts.pages.page', compact('page'));
    }

    public function districts(Request $request)
    {
        $areas = District::where(['district' => $request->id])->pluck('area_name', 'id');
        return response()->json($areas);
    }

    public function campaign($slug)
    {
        $campaign_data = Campaign::with('images')
            ->where('slug', $slug)
            ->where('status', true)
            ->where('is_published', true)
            ->firstOrFail();

        // Keep the primary product first, then all pivot products in a deterministic order.
        // Applying availability conditions after a grouped ID constraint prevents the old
        // orWhere() branch from accidentally exposing inactive/unapproved primary products.
        $productIds = DB::table('campaign_product')
            ->where('campaign_id', $campaign_data->id)
            ->pluck('product_id')
            ->push($campaign_data->product_id)
            ->filter()
            ->unique()
            ->values();

        $products = Product::query()
            ->whereIn('id', $productIds)
            ->where('status', 1)
            ->where('approval_status', 'approved')
            ->with([
                'image',
                'sizes',
                'colors',
                'variantPrices.size',
                'variantPrices.color',
            ])
            ->when($campaign_data->product_id, function ($query, $primaryProductId) {
                $query->orderByRaw('CASE WHEN products.id = ? THEN 0 ELSE 1 END', [$primaryProductId]);
            })
            ->orderBy('products.id')
            ->get();

        if ($products->isEmpty()) {
            $products = collect();
        }

        // A campaign is a single-product checkout flow. Start it with the deterministic
        // primary/first available product and safely handle products without an image.
        Cart::instance('shopping')->destroy();
        $product = $products->first();
        if ($product) {
            Cart::instance('shopping')->add([
                'id'    => $product->id,
                'name'  => $product->name,
                'qty'   => 1,
                'price' => (float) ($product->new_price ?? $product->old_price ?? 1),
                'options' => [
                    'slug'             => $product->slug,
                    'image'            => optional($product->image)->image ?? 'public/uploads/default.webp',
                    'old_price'        => (float) ($product->old_price ?? 0),
                    'purchase_price'   => (float) ($product->purchase_price ?? 0),
                    'product_size'     => null,
                    'product_color'    => null,
                    'size_id'          => null,
                    'color_id'         => null,
                    'variant_price_id' => null,
                    'pro_unit'         => $product->pro_unit ?? null,
                    'advance_amount'   => (float) ($product->advance_amount ?? 0),
                    'is_digital'       => (int) ($product->is_digital ?? 0),
                    'free_delivery'    => (int) ($product->free_delivery ?? 0),
                ],
            ]);
        }

        $shippingcharge = ShippingCharge::where('status', 1)->orderBy('id')->get();
        $select_charge = $shippingcharge->first();
        if ($select_charge) {
            Session::put('shipping', $select_charge->amount);
        } else {
            Session::put('shipping', 0);
        }

        // The legacy campaign view and standalone builder both need this explicitly.
        $tiktok_pixels = Cache::remember('tiktok_pixels_list', 1800, function () {
            return TiktokPixel::where('status', 1)->get();
        });

        // Facebook CAPI ViewContent — server-side, event_id দিয়ে Pixel-এর সাথে deduplicate হবে
        $fb_view_content_event_id = 'vc_camp' . $campaign_data->id . '_' . time();
        $capiPayload = [
            'content_name' => strip_tags($campaign_data->name),
            'content_ids'  => $products->pluck('id')->map(fn ($id) => (string) $id)->values()->toArray(),
            'content_type' => 'product',
            'value'        => (float) (optional($products->first())->new_price ?? 0),
            'currency'     => 'BDT',
            'num_items'    => $products->count(),
        ];
        $capiUserData = [
            'client_ip_address' => request()->ip(),
            'client_user_agent' => request()->userAgent(),
        ];
        if (isset($_COOKIE['_fbp'])) $capiUserData['fbp'] = $_COOKIE['_fbp'];
        if (isset($_COOKIE['_fbc'])) $capiUserData['fbc'] = $_COOKIE['_fbc'];
        $capiUrl = request()->fullUrl();
        dispatch(function () use ($capiPayload, $capiUserData, $fb_view_content_event_id, $capiUrl) {
            try {
                app(\App\Services\FacebookCapiService::class)->sendViewContent($capiPayload, $capiUserData, [
                    'event_id' => $fb_view_content_event_id,
                    'event_source_url' => $capiUrl,
                ]);
            } catch (\Throwable $e) {
            }
        })->afterResponse();

        // ⭐ ক্যাম্পেইন অ্যানালিটিক্স — এই পেজের ভিজিট গোনা হচ্ছে।
        // পরে অর্ডারের সাথে মিলিয়ে কনভার্শন রেট বের করা হবে।
        app(\App\Services\CampaignAnalyticsService::class)->recordVisit($campaign_data->id);

        // কোন ক্যাম্পেইন থেকে চেকআউটে এলো সেটা সেশনে রেখে দিই — অর্ডার সেভের
        // সময় orders.campaign_id-তে বসবে, তাই ভিজিট → অর্ডার মেলানো যাবে।
        Session::put('active_campaign_id', $campaign_data->id);

        // ⭐ অর্ডার বাম্প — চেকআউটে দেখানোর মতো অ্যাড-অন অফার (AOV বাড়ানোর জন্য)
        $orderBumps = app(\App\Services\OrderBumpService::class)
            ->availableFor($campaign_data->id);

        if ($orderBumps->isNotEmpty()) {
            app(\App\Services\OrderBumpService::class)->recordImpressions($orderBumps);
        }

        $viewData = compact(
            'campaign_data',
            'products',
            'shippingcharge',
            'tiktok_pixels',
            'fb_view_content_event_id',
            'orderBumps'
        );

        // Published custom source has priority, followed by the visual builder. Campaigns
        // without either use the new premium conversion template based on the supplied design.
        if ($campaign_data->isCustomPageLive()) {
            $viewData['renderPageHtml'] = app(\App\Services\CampaignCustomPageService::class)
                ->render($campaign_data->custom_html, $campaign_data, $products);
            $viewData['renderPageCss'] = $campaign_data->custom_css;
            $viewData['renderPageJs'] = $campaign_data->custom_js;
            $viewData['pageType'] = 'custom';

            return view('frontEnd.layouts.pages.campaign.campaign-builder', $viewData);
        }

        if (!empty($campaign_data->page_html)) {
            $viewData['pageType'] = 'visual';
            return view('frontEnd.layouts.pages.campaign.campaign-builder', $viewData);
        }

        $viewData['renderPageHtml'] = view(
            'frontEnd.layouts.pages.campaign.partials.premium-template',
            $viewData
        )->render();
        $viewData['renderPageCss'] = null;
        $viewData['renderPageJs'] = null;
        $viewData['pageType'] = 'premium';

        return view('frontEnd.layouts.pages.campaign.campaign-builder', $viewData);
    }

    public function payment_success(Request $request)
    {
        $order_id = $request->order_id;
        $shurjopay_service = new ShurjopayController();
        $json = $shurjopay_service->verify($order_id);
        $data = json_decode($json);

        if ($data[0]->sp_code != 1000) {
            Toastr::error('Your payment failed, try again', 'Oops!');
            return redirect()->route('home');
        }

        if ($data[0]->value1 == 'customer_payment') {
            $customer = Customer::find(Auth::guard('customer')->user()->id);

            $order = new Order();
            $order->invoice_id   = $data[0]->id;
            $order->amount       = $data[0]->amount;
            $order->customer_id  = Auth::guard('customer')->user()->id;
            $order->order_status = $data[0]->bank_status;
            $order->save();

            $payment = new Payment();
            $payment->order_id       = $order->id;
            $payment->customer_id    = Auth::guard('customer')->user()->id;
            $payment->payment_method = 'shurjopay';
            $payment->amount         = $order->amount;
            $payment->trx_id         = $data[0]->bank_trx_id;
            $payment->sender_number  = $data[0]->phone_no;
            $payment->payment_status = 'paid';
            $payment->save();

            // Order details + stock update helper
            OrderHelper::saveOrderDetails($order);

            Cart::instance('shopping')->destroy();
            Toastr::success('Thanks, Your payment send successfully', 'Success!');
            return redirect()->route('home');
        }

        Toastr::error('Something wrong, please try again', 'Error!');
        return redirect()->route('home');
    }

    public function payment_cancel(Request $request)
    {
        $order_id = $request->order_id;
        $shurjopay_service = new ShurjopayController();
        $json = $shurjopay_service->verify($order_id);
        $data = json_decode($json);

        Toastr::error('Your payment cancelled', 'Cancelled!');
        return redirect()->route('home');
    }

    public function offers()
    {
        return view('frontEnd.layouts.pages.offers');
    }

    /**
     * Helper: প্রোডাক্টের stock কলাম থেকে available স্টক বের করবে
     * products টেবিলে stock / qty / quantity – যেটা আছে সেটাই ব্যবহার করবে
     */
    protected function getAvailableStock(Product $product)
    {
        if (isset($product->stock)) {
            return (int) $product->stock;
        }

        if (isset($product->qty)) {
            return (int) $product->qty;
        }

        if (isset($product->quantity)) {
            return (int) $product->quantity;
        }

        // কোনো stock-সংক্রান্ত কলাম না থাকলে null রিটার্ন করবে
        return null;
    }

    // Wholesale Products Page
    public function wholesaleProducts(Request $request)
    {
        $query = Product::where('status', 1)
            ->where('approval_status', 'approved')
            ->where('is_wholesale', 1)
            ->with(['image', 'category', 'brand', 'reviews']);

        // Search
        if ($request->keyword) {
            $query->where('name', 'like', '%' . $request->keyword . '%');
        }

        // Category filter
        if ($request->category) {
            $query->where('category_id', $request->category);
        }

        // Sort
        switch ($request->sort) {
            case '2':
                $query->orderBy('id', 'ASC');
                break;
            case '3':
                $query->orderBy('wholesale_price', 'DESC');
                break;
            case '4':
                $query->orderBy('wholesale_price', 'ASC');
                break;
            case '5':
                $query->orderBy('name', 'ASC');
                break;
            case '6':
                $query->orderBy('name', 'DESC');
                break;
            default:
                $query->orderBy('id', 'DESC');
        }

        $products = $query->paginate(24);
        $categories = Category::where('status', 1)->where('parent_id', 0)->get();

        return view('frontEnd.layouts.pages.wholesale_products', compact('products', 'categories'));
    }
}
