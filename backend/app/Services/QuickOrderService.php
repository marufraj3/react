<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariantPrice;
use App\Models\Shipping;
use App\Models\ShippingCharge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * 🔥 কুইক-অর্ডার সার্ভিস
 *
 * Product Card-এর "Order Now" পপআপ থেকে সরাসরি অর্ডার তৈরি করে —
 * চেকআউট পেজে না গিয়েই। ডাটাবেস রাইট (Order / Shipping / Payment /
 * OrderDetails), স্টক কমানো, স্টক অ্যালার্ট, অর্ডার রেস্ট্রিকশন ও SMS
 * সবই normal checkout (`CustomerController::order_save`) এর মতোই হয়,
 * যাতে অ্যাডমিন প্যানেল ও রিপোর্টে কোনো পার্থক্য না থাকে।
 */
class QuickOrderService
{
    /**
     * @throws \RuntimeException কোনো ভ্যালিডেশন বা স্টক সমস্যায় (মেসেজ বাংলা)
     */
    public function placeOrder(array $data, Request $request): Order
    {
        $product = Product::where(['id' => $data['product_id'], 'status' => 1, 'approval_status' => 'approved'])
            ->with(['image', 'variantPrices.size', 'variantPrices.color'])
            ->first();

        if (!$product) {
            throw new \RuntimeException('প্রোডাক্টটি খুঁজে পাওয়া যায়নি।');
        }

        $qty    = max(1, (int) ($data['qty'] ?? 1));
        $sizeId = $data['size_id'] ?? null;
        $colorId = $data['color_id'] ?? null;
        $phone  = trim((string) ($data['phone'] ?? ''));

        // =========================================================
        // ১. অর্ডার রেস্ট্রিকশন (ফোন-ভিত্তিক, অ্যাডমিন কন্ট্রোলড)
        // =========================================================
        $restrictionMessage = app(OrderRestrictionService::class)->violationMessage($phone);
        if ($restrictionMessage) {
            throw new \RuntimeException($restrictionMessage);
        }

        // =========================================================
        // ২. স্টক ভ্যালিডেশন (ভ্যারিয়েন্ট হলে ভ্যারিয়েন্ট স্টক)
        // =========================================================
        $variantMatrix = ProductVariantPrice::where('product_id', $product->id);
        $hasVariants   = (clone $variantMatrix)->exists();

        $variant      = null;
        $finalPrice   = (float) $product->new_price;

        if ($hasVariants) {
            $variant = (clone $variantMatrix)
                ->when($colorId, fn ($q) => $q->where('color_id', $colorId))
                ->when($sizeId, fn ($q) => $q->where('size_id', $sizeId))
                ->first();

            if (!$variant) {
                throw new \RuntimeException('সিলেক্ট করা সাইজ/কালারের জন্য ভ্যারিয়েন্ট পাওয়া যায়নি।');
            }

            // ভ্যারিয়েন্টে স্টক থাকলে সেটাই অথরিটেটিভ
            if ($variant->stock !== null) {
                if ((int) $variant->stock <= 0) {
                    throw new \RuntimeException('এই সাইজ/কালারটি বর্তমানে স্টকে নেই।');
                }
                if ((int) $variant->stock < $qty) {
                    throw new \RuntimeException('এই সাইজ/কালারের মাত্র ' . (int) $variant->stock . ' টি বাকি আছে। পরিমাণ কমিয়ে নিন।');
                }
            }

            if ((float) $variant->price > 0) {
                $finalPrice = (float) $variant->price;
            }
        } else {
            // সাধারণ প্রোডাক্টের স্টক চেক (ডিজিটাল প্রোডাক্ট বাদ)
            $isDigital = (int) ($product->is_digital ?? 0) === 1;
            if (!$isDigital && isset($product->stock) && (int) $product->stock < $qty) {
                $left = max(0, (int) $product->stock);
                $message = $left > 0
                    ? 'প্রোডাক্টটির মাত্র ' . $left . ' টি স্টকে আছে। পরিমাণ কমিয়ে নিন।'
                    : 'প্রোডাক্টটি বর্তমানে স্টকে নেই।';
                throw new \RuntimeException($message);
            }
        }

        // =========================================================
        // ৩. শিপিং চার্জ (সবচেয়ে সাশ্রয়ী সক্রিয় এরিয়া)
        // =========================================================
        $shippingCharge = ShippingCharge::where('status', 1)->first();
        // ডিজিটাল প্রোডাক্টে ডেলিভারি চার্জ নেই
        $shippingFee    = ((int) ($product->is_digital ?? 0) === 1)
            ? 0
            : ($shippingCharge ? (float) $shippingCharge->amount : 0);

        $grandTotal = ($finalPrice * $qty) + $shippingFee;

        // =========================================================
        // ৪. কাস্টমার (ফোন দিয়ে খুঁজে নাহলে বানাই — চেকআউটের মতোই)
        // =========================================================
        $customer = Customer::where('phone', $phone)->select('id')->first();
        if ($customer) {
            $customerId = $customer->id;
        } else {
            $store = new Customer();
            $store->name     = $data['name'];
            $store->slug     = Str::slug($data['name']) . '-' . substr(md5($phone . time()), 0, 6);
            $store->phone    = $phone;
            $store->password = bcrypt(rand(111111, 999999));
            $store->verify   = 1;
            $store->status   = 'active';
            $store->save();
            $customerId = $store->id;
        }

        // =========================================================
        // ৫. অর্ডার তৈরি
        // =========================================================
        $order = new Order();
        $order->invoice_id      = rand(11111, 99999);
        $order->amount          = round($grandTotal, 2);
        $order->shipping_charge = $shippingFee;
        $order->customer_id     = $customerId;
        $order->order_status    = 1;
        $order->note            = $data['note'] ?? null;
        $order->payment_status  = 'pending';
        $order->discount        = 0;
        $order->ip_address      = $request->ip();
        $order->save();

        // Shipping info
        $shipping = new Shipping();
        $shipping->order_id    = $order->id;
        $shipping->customer_id = $customerId;
        $shipping->name        = $data['name'];
        $shipping->phone       = $phone;
        $shipping->address     = $data['address'];
        $shipping->area        = $shippingCharge ? $shippingCharge->name : 'Inside Dhaka';
        $shipping->save();

        // Payment info (কুইক-অর্ডার সবসময় Cash on Delivery)
        $payment = new Payment();
        $payment->order_id       = $order->id;
        $payment->customer_id    = $customerId;
        $payment->payment_method = 'cod';
        $payment->amount         = round($grandTotal, 2);
        $payment->payment_status = 'pending';
        $payment->save();

        // Order details (single product)
        $detail = new OrderDetails();
        $detail->order_id        = $order->id;
        $detail->product_id      = $product->id;
        $detail->vendor_id       = $product->vendor_id ?? null;
        $detail->product_name    = $product->name;
        $detail->purchase_price  = $product->purchase_price ?? null;
        $detail->sale_price      = round($finalPrice, 2);
        $detail->qty             = $qty;
        $detail->product_color   = $colorId;
        $detail->product_size    = $sizeId;
        $detail->variant_price_id = $variant->id ?? null;
        $detail->save();

        // =========================================================
        // ৬. স্টক কমানো (ভ্যারিয়েন্ট না থাকলে প্রোডাক্ট স্টক)
        // =========================================================
        if ($variant && $variant->stock !== null) {
            $variant->stock = max(0, (int) $variant->stock - $qty);
            $variant->save();
        } elseif (!$hasVariants && isset($product->stock)) {
            $product->stock = max(0, (int) $product->stock - $qty);
            $product->save();
        }

        // স্টক অ্যালার্ট (ফুরিয়ে গেলে অ্যাডমিন নোটিফিকেশন)
        try {
            app(StockAlertService::class)->checkOrderItems(collect([$detail]));
        } catch (\Throwable $e) {
            Log::warning('Stock alert check failed for order ' . $order->id . ': ' . $e->getMessage());
        }

        // =========================================================
        // ৭. নোটিফিকেশন ও ফ্রড চেক (চেকআউটের মতোই, non-blocking)
        // =========================================================
        try {
            app(OrderNotificationService::class)->orderSms($order, $phone, $data['name']);
            app(OrderNotificationService::class)->adminOrderSms($order, $data['name'], $phone);
        } catch (\Throwable $e) {
            Log::error('Quick order SMS failed for order ' . $order->id . ': ' . $e->getMessage());
        }

        try {
            app(FraudCheckService::class)->queueAfterResponse($phone, $order->id);
        } catch (\Throwable $e) {
            Log::error('Auto fraud check setup failed for order ' . $order->id . ': ' . $e->getMessage());
        }

        return $order;
    }
}
