<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariantPrice;
use App\Models\StockAlert;
use App\Services\StockAlertService;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

/**
 * স্টক অ্যালার্ট ব্যবস্থাপনা — কোন প্রোডাক্ট ফুরিয়ে গেছে বা প্রায় শেষ।
 */
class StockAlertController extends Controller
{
    public function index(Request $request)
    {
        if (!Schema::hasTable('stock_alerts')) {
            Toastr::error('স্টক অ্যালার্ট টেবিলটি এখনো তৈরি হয়নি। মাইগ্রেশন চালান।', 'Error');

            return redirect()->route('admin.dashboard');
        }

        $filter = $request->query('type');

        $query = StockAlert::with(['product:id,name,slug,status', 'variant']);

        // ডিফল্টে শুধু খোলা অ্যালার্ট — সমাধান হওয়াগুলো আলাদা ফিল্টারে
        if ($filter === 'resolved') {
            $query->whereNotNull('resolved_at');
        } else {
            $query->whereNull('resolved_at');

            if (in_array($filter, ['out_of_stock', 'low_stock'], true)) {
                $query->where('type', $filter);
            }
        }

        $alerts = $query->latest()->paginate(30)->withQueryString();

        $stats = [
            'out_of_stock' => StockAlert::open()->where('type', 'out_of_stock')->count(),
            'low_stock'    => StockAlert::open()->where('type', 'low_stock')->count(),
            'unread'       => StockAlert::open()->where('is_read', false)->count(),
            'resolved'     => StockAlert::whereNotNull('resolved_at')->count(),
        ];

        // পেজ খোলা মানেই অ্যাডমিন দেখে ফেলেছে — ব্যাজ পরিষ্কার করি
        StockAlert::open()->where('is_read', false)->update(['is_read' => true]);

        return view('backEnd.stock_alerts.index', compact('alerts', 'stats', 'filter'));
    }

    /**
     * স্টক আপডেট করে অ্যালার্ট সমাধান করে।
     */
    public function restock(Request $request, $id)
    {
        $request->validate([
            'stock' => 'required|integer|min:0',
        ]);

        $alert = StockAlert::findOrFail($id);
        $stock = (int) $request->stock;

        if ($alert->variant_price_id) {
            $variant = ProductVariantPrice::find($alert->variant_price_id);

            if (!$variant) {
                Toastr::error('ভ্যারিয়েন্টটি আর নেই।', 'Error');

                return redirect()->back();
            }

            $variant->stock = $stock;
            $variant->save();
        } else {
            $product = Product::find($alert->product_id);

            if (!$product) {
                Toastr::error('প্রোডাক্টটি আর নেই।', 'Error');

                return redirect()->back();
            }

            if (Schema::hasColumn('products', 'stock')) {
                $product->stock = $stock;
                $product->save();
            }
        }

        // স্টক ফিরে এলে প্রোডাক্টটি আবার চালু করি — অটো আউট-অফ-স্টকের উল্টো কাজ
        if ($stock > 0) {
            $product = Product::find($alert->product_id);

            if ($product && (int) $product->status === 0) {
                $product->status = 1;
                $product->save();
            }
        }

        $alert->resolved_at = now();
        $alert->save();

        Toastr::success('স্টক আপডেট হয়েছে এবং অ্যালার্টটি বন্ধ করা হয়েছে।', 'Success');

        return redirect()->back();
    }

    /** অ্যালার্টটি হাতে বন্ধ করে (স্টক না বদলে) */
    public function dismiss($id)
    {
        $alert = StockAlert::findOrFail($id);
        $alert->resolved_at = now();
        $alert->is_read     = true;
        $alert->save();

        Toastr::success('অ্যালার্টটি বন্ধ করা হয়েছে।', 'Success');

        return redirect()->back();
    }

    /**
     * পুরো ক্যাটালগ স্ক্যান করে বর্তমান স্টক অনুযায়ী অ্যালার্ট তৈরি করে।
     *
     * মাইগ্রেশনের আগে যেসব প্রোডাক্ট ফুরিয়ে গিয়েছিল সেগুলো ধরার জন্য।
     */
    public function scan()
    {
        $service = app(StockAlertService::class);
        $checked = 0;

        // চাঙ্ক করে দেখা হচ্ছে — বড় ক্যাটালগে একসাথে সব লোড করলে মেমরি শেষ হয়ে যাবে
        ProductVariantPrice::with(['product', 'size', 'color'])
            ->whereNotNull('stock')
            ->where('stock', '<=', StockAlertService::DEFAULT_LOW_STOCK_THRESHOLD)
            ->chunkById(200, function ($variants) use ($service, &$checked) {
                foreach ($variants as $variant) {
                    $service->checkVariant($variant);
                    $checked++;
                }
            });

        if (Schema::hasColumn('products', 'stock')) {
            Product::whereDoesntHave('variantPrices')
                ->where('stock', '<=', StockAlertService::DEFAULT_LOW_STOCK_THRESHOLD)
                ->chunkById(200, function ($products) use ($service, &$checked) {
                    foreach ($products as $product) {
                        $service->checkProduct($product);
                        $checked++;
                    }
                });
        }

        Toastr::success($checked . ' টি আইটেম স্ক্যান করা হয়েছে।', 'Success');

        return redirect()->route('admin.stock_alerts.index');
    }
}
