<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\IncompleteOrder;

// নতুন যেগুলো লাগবে
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\Shipping;
use App\Models\Payment;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Support\Str;

class IncompleteOrderController extends Controller
{
    /**
     * ইনকমপ্লিট অর্ডার লিস্ট
     */
    public function index(Request $request)
    {
        $status = $request->query('status');
        $search = trim((string) $request->query('q'));

        $query = IncompleteOrder::query();

        // recovery_status কলামটি মাইগ্রেশনের আগে না-ও থাকতে পারে —
        // পুরনো ইনস্টলে পেজটা যেন সাদা না হয়ে যায়।
        $hasRecovery = Schema::hasColumn('incomplete_orders', 'recovery_status');

        if ($hasRecovery) {
            if ($status && array_key_exists($status, IncompleteOrder::RECOVERY_STATUSES)) {
                $query->where('recovery_status', $status);
            } elseif ($status !== 'all') {
                // ডিফল্টে শুধু কাজের সারি — যেগুলোর ফলোআপ এখনো বাকি।
                // রিকভার/হারানো রো গুলো "সব" ফিল্টারে দেখা যাবে।
                $query->whereIn('recovery_status', ['pending', 'contacted']);
            }
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('phone', 'like', '%' . $search . '%');
            });
        }

        $orders = $query->latest()->paginate(25)->withQueryString();

        // উপরের সামারি কার্ডগুলো — ফিল্টার নির্বিশেষে সবসময় মোট হিসাব দেখায়
        $stats = [
            'total'      => IncompleteOrder::count(),
            'amount'     => (float) IncompleteOrder::sum('total_amount'),
            'today'      => IncompleteOrder::whereDate('created_at', now()->toDateString())->count(),
            'pending'    => $hasRecovery ? IncompleteOrder::where('recovery_status', 'pending')->count() : 0,
            'contacted'  => $hasRecovery ? IncompleteOrder::where('recovery_status', 'contacted')->count() : 0,
            'recovered'  => $hasRecovery ? IncompleteOrder::where('recovery_status', 'recovered')->count() : 0,
            'lost'       => $hasRecovery ? IncompleteOrder::where('recovery_status', 'lost')->count() : 0,
        ];

        // রিকভারি রেট = রিকভার হওয়া / (মোট নিষ্পত্তি হওয়া), শূন্য দিয়ে ভাগ এড়িয়ে
        $settled = $stats['recovered'] + $stats['lost'];
        $stats['recovery_rate'] = $settled > 0
            ? round(($stats['recovered'] / $settled) * 100, 1)
            : 0.0;

        $hasCaptureMetadata = Schema::hasColumn('incomplete_orders', 'device_type');

        return view('backEnd.incomplete_orders.index', compact(
            'orders', 'stats', 'status', 'search', 'hasRecovery', 'hasCaptureMetadata'
        ));
    }

    /**
     * রিকভারি স্ট্যাটাস / নোট আপডেট।
     *
     * এখানে কোনো SMS বা WhatsApp পাঠানো হয় না — অ্যাডমিন নিজে ফোন করে
     * তারপর এখানে ফলাফল টুকে রাখেন।
     */
    public function updateRecovery(Request $request, $id)
    {
        $request->validate([
            'recovery_status' => 'required|in:' . implode(',', array_keys(IncompleteOrder::RECOVERY_STATUSES)),
            'recovery_note'   => 'nullable|string|max:1000',
        ]);

        $incomplete = IncompleteOrder::findOrFail($id);

        $incomplete->recovery_status = $request->recovery_status;
        $incomplete->recovery_note   = $request->recovery_note;

        // প্রথমবার 'contacted' করার সময়টা ধরে রাখি, পরে বদলাই না —
        // এতে "কতদিন ধরে ফলোআপ চলছে" বোঝা যায়।
        if ($request->recovery_status === 'contacted' && !$incomplete->contacted_at) {
            $incomplete->contacted_at = now();
        }

        $incomplete->save();

        Toastr::success('রিকভারি স্ট্যাটাস আপডেট হয়েছে।', 'Success');

        return redirect()->back();
    }

    /**
     * ইনকমপ্লিট অর্ডার স্টোর (AJAX থেকে)।
     *
     * @deprecated এই স্টোর লজিকটি এখন Frontend\FrontendController@storeIncompleteOrder-এ
     * থাকে, কারণ কলারটি কাস্টমার-ফেসিং চেকআউট পেজ — অ্যাডমিন প্যানেল নয়।
     * পুরনো কোনো কল যেন ভেঙে না যায় সেজন্য এখানে শুধু ফরওয়ার্ড করা হচ্ছে।
     */
    public function store(Request $request)
    {
        return app(\App\Http\Controllers\Frontend\FrontendController::class)
            ->storeIncompleteOrder($request);
    }

    /**
     * ইনকমপ্লিট অর্ডারকে রেগুলার অর্ডারে কনভার্ট করবে
     */
    public function accept($id)
    {
        $incomplete = IncompleteOrder::findOrFail($id);

        DB::beginTransaction();

        try {
            // items অ্যারে / json হ্যান্ডেল
            $items = is_array($incomplete->items)
                ? $incomplete->items
                : (json_decode($incomplete->items, true) ?? []);

            if (empty($items)) {
                Toastr::error('এই ইনকমপ্লিট অর্ডারে কোন প্রোডাক্ট নেই!', 'Error');
                return redirect()->back();
            }

            // subtotal
            $subtotal = 0;
            foreach ($items as $item) {
                $qty   = isset($item['qty']) ? (int) $item['qty'] : 1;
                $price = isset($item['price']) ? (float) $item['price'] : 0;
                $subtotal += $qty * $price;
            }

            $grandTotal    = $incomplete->total_amount ?? $subtotal;
            $shippingAmount = 0;
            $discount       = 0;
            $shippingName   = 'N/A';

            /**
             * CUSTOMER HANDLE
             */
            $baseName  = $incomplete->name ?: 'Customer';
            $slugValue = Str::slug($baseName) . '-' . rand(1000, 9999);

            $customer = null;

            if (!empty($incomplete->phone)) {
                // ফোন থাকলে ফোন দিয়ে কাস্টমার খুঁজে / তৈরি
                $customer = Customer::firstOrCreate(
                    ['phone' => $incomplete->phone],
                    [
                        'name'     => $baseName,
                        'slug'     => $slugValue,
                        'password' => bcrypt(rand(111111, 999999)),
                        'verify'   => 1,
                        'status'   => 'active',
                    ]
                );
            }

            // ফোন না থাকলে নতুন কাস্টমার
            if (!$customer) {
                $customer = Customer::create([
                    'name'     => $baseName,
                    'slug'     => $slugValue,
                    'phone'    => $incomplete->phone ?: '00000000000',
                    'password' => bcrypt(rand(111111, 999999)),
                    'verify'   => 1,
                    'status'   => 'active',
                ]);
            }

            /**
             * ORDER CREATE
             */
            $order                  = new Order();
            $order->invoice_id      = rand(11111, 99999);
            $order->amount          = $grandTotal;
            $order->discount        = $discount;
            $order->shipping_charge = $shippingAmount;
            $order->customer_id     = $customer->id;
            $order->order_status    = 1; // pending
            $order->note            = null;
            $order->save();

            /**
             * SHIPPING CREATE
             */
            $shipping              = new Shipping();
            $shipping->order_id    = $order->id;
            $shipping->customer_id = $customer->id;
            $shipping->name        = $incomplete->name;
            $shipping->phone       = $incomplete->phone;
            $shipping->address     = $incomplete->address;
            $shipping->area        = $shippingName;
            $shipping->save();

            /**
             * PAYMENT CREATE
             */
            $payment                 = new Payment();
            $payment->order_id       = $order->id;
            $payment->customer_id    = $customer->id;
            $payment->payment_method = 'Cash On Delivery';
            $payment->amount         = $order->amount;
            $payment->payment_status = 'pending';
            $payment->save();

            /**
             * ORDER DETAILS + STOCK UPDATE
             */
            foreach ($items as $item) {
                $productId = $item['id'] ?? null;
                $product   = $productId ? Product::find($productId) : null;

                $qty = $item['qty'] ?? 1;

                $detail                   = new OrderDetails();
                $detail->order_id         = $order->id;
                $detail->product_id       = $productId;
                $detail->product_name     = $item['name'] ?? ($product->name ?? 'Product');
                $detail->purchase_price   = $product->purchase_price ?? 0;
                $detail->product_discount = 0;
                $detail->sale_price       = $item['price'] ?? ($product->new_price ?? 0);
                $detail->qty              = $qty;
                $detail->save();

                // 🔻 স্টক কমানো
                if ($product) {

                    if (Schema::hasColumn('products', 'stock')) {
                        $product->stock = max(0, (int)$product->stock - (int)$qty);
                        $product->save();
                    } elseif (Schema::hasColumn('products', 'qty')) {
                        $product->qty = max(0, (int)$product->qty - (int)$qty);
                        $product->save();
                    } elseif (Schema::hasColumn('products', 'quantity')) {
                        $product->quantity = max(0, (int)$product->quantity - (int)$qty);
                        $product->save();
                    }
                }
            }

            // ⭐ ডিলিট না করে 'recovered' চিহ্নিত করি — তাহলে রিকভারি
            // রিপোর্টে হিসাবটা থেকে যায়। কলামটি না থাকলে (পুরনো ইনস্টল)
            // আগের মতোই ডিলিট হবে।
            if (Schema::hasColumn('incomplete_orders', 'recovery_status')) {
                $incomplete->recovery_status    = 'recovered';
                $incomplete->recovered_order_id = $order->id;

                if (!$incomplete->contacted_at) {
                    $incomplete->contacted_at = now();
                }

                $incomplete->save();
            } else {
                $incomplete->delete();
            }

            DB::commit();

            Toastr::success('Incomplete order কে সফলভাবে রেগুলার অর্ডারে কনভার্ট করা হয়েছে।', 'Success');

            return redirect()->route('admin.order.edit', $order->invoice_id);

        } catch (\Exception $e) {
            DB::rollBack();
            Toastr::error('Something went wrong: ' . $e->getMessage(), 'Error');
            return redirect()->back();
        }
    }

    /**
     * ইনকমপ্লিট অর্ডার ডিলিট
     */
    public function destroy($id)
    {
        $order = IncompleteOrder::findOrFail($id);
        $order->delete();

        return redirect()->back()->with('success', 'Incomplete order deleted successfully.');
    }
}
