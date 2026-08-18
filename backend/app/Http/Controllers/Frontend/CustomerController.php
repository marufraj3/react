<?php

namespace App\Http\Controllers\Frontend;

use shurjopayv2\ShurjopayLaravelPackage8\Http\Controllers\ShurjopayController;
use App\Mail\OrderPlace;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Brian2694\Toastr\Facades\Toastr;
use Intervention\Image\Facades\Image;
use App\Models\Customer;
use App\Models\District;
use App\Models\Order;
use App\Models\ShippingCharge;
use App\Models\OrderDetails;
use App\Models\Payment;
use App\Models\Shipping;
use App\Models\Review;
use App\Models\PaymentGateway;
use App\Models\SmsGateway;
use App\Models\Contact;
use App\Models\GeneralSetting;
use App\Models\IncompleteOrder;
use App\Models\Product;          // স্টক কমানোর জন্য
use App\Models\ProductVariantPrice;
use App\Models\DigitalDownload;  // ⭐ ডিজিটাল ডাউনলোড মডেল

use Session;
use Hash;
use Auth;
use Cart;
use Mail;
use Str;
use DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash as HashFacade;
use Illuminate\Support\Facades\File;
use App\Helpers\OrderHelper;
use App\Services\FacebookCapiService;

class CustomerController extends Controller
{
    protected $facebookCapiService;

    function __construct(FacebookCapiService $facebookCapiService)
    {
        $this->facebookCapiService = $facebookCapiService;
        $this->middleware('customer', ['except' => [
            'register','store','verify','resendotp','account_verify',
            'login','signin','logout','checkout','forgot_password',
            'forgot_verify','forgot_reset','forgot_store','forgot_resend',
            'order_save','order_success','order_track','order_track_result'
        ]]);
    }

    public function review(Request $request)
    {
        $this->validate($request,[
            'ratting'=>'required',
            'review'=>'required',
        ]);

        $review = new Review();
        $review->name = Auth::guard('customer')->user()->name ?? 'N / A';
        $review->email = Auth::guard('customer')->user()->email ?? 'N / A';
        $review->product_id = $request->product_id;
        $review->review = $request->review;
        $review->ratting = $request->ratting;
        $review->customer_id = Auth::guard('customer')->user()->id;
        $review->status = 'pending';
        $review->save();

        Toastr::success('Thanks, Your review send successfully', 'Success!');
        return redirect()->back();
    }

    public function login()
    {
        return view('frontEnd.layouts.customer.login');
    }

    public function signin(Request $request)
    {
        $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ]);

        $login = trim((string) $request->input('login'));
        $password = (string) $request->input('password');
        $isPhone = preg_match('/^[0-9+]+$/', $login) === 1;

        // Preserve historical reseller rows but explicitly deny runtime authentication.
        $resellerEmail = $isPhone ? Customer::where('phone', $login)->value('email') : $login;
        $retiredReseller = $resellerEmail
            ? \App\Models\User::where('email', $resellerEmail)
                ->where(function ($query) {
                    $query->where('role', 'reseller')
                        ->orWhereHas('roles', fn ($role) => $role->whereRaw('LOWER(name) = ?', ['reseller']));
                })->exists()
            : false;

        if ($retiredReseller) {
            Toastr::error('The reseller portal is no longer available.', 'Access disabled');
            return redirect()->back()->withInput($request->only('login'));
        }

        $customerCredentials = $isPhone
            ? ['phone' => $login, 'password' => $password]
            : ['email' => $login, 'password' => $password];
        $customerExists = Customer::where($isPhone ? 'phone' : 'email', $login)->exists();

        if ($customerExists && Auth::guard('customer')->attempt($customerCredentials)) {
            Toastr::success('You are login successfully', 'Success');
            return Cart::instance('shopping')->count() > 0
                ? redirect()->route('customer.checkout')
                : redirect()->intended('customer/account');
        }

        $vendor = $isPhone
            ? \App\Models\Vendor::where('phone', $login)->first()
            : \App\Models\Vendor::where('email', $login)->first();
        $adminEmail = $vendor?->email ?: (!$isPhone ? $login : null);

        if ($adminEmail && Auth::guard('admin')->attempt(['email' => $adminEmail, 'password' => $password])) {
            $user = Auth::guard('admin')->user();
            if ($user->hasRole('reseller') || strtolower((string) $user->role) === 'reseller') {
                Auth::guard('admin')->logout();
                Toastr::error('The reseller portal is no longer available.', 'Access disabled');
                return redirect()->back()->withInput($request->only('login'));
            }
            if ($user->hasRole('vendor')) {
                Toastr::success('You are login successfully', 'Success');
                return redirect()->route('vendor.dashboard');
            }
            if ($user->hasRole('admin')) {
                return redirect()->route('admin.dashboard');
            }
            Auth::guard('admin')->logout();
        }

        Toastr::error('Opps! your credentials are wrong', 'Error');
        return redirect()->back()->withInput($request->only('login'));
    }

    public function register()
    {
        return view('frontEnd.layouts.customer.register');
    }

    public function store(Request $request)
    {
        $isSeller = $request->boolean('is_seller');

        if ($isSeller) {
            $request->validate([
                'name' => 'required|string|max:255',
                'phone' => 'required|string|max:55|unique:vendors,phone|unique:customers,phone',
                'email' => 'required|email|unique:users,email|unique:vendors,email|unique:customers,email',
                'shop_name' => 'required|string|max:255',
                'slug' => 'required|string|max:255|unique:vendors,slug',
                'password' => 'required|confirmed|min:6',
                'address' => 'nullable|string',
                'logo' => 'nullable|image|max:2048',
                'banner' => 'nullable|image|max:3072',
            ]);

            $logoPath = $request->hasFile('logo')
                ? $request->file('logo')->store('uploads/vendor/logo', 'public')
                : null;
            $bannerPath = $request->hasFile('banner')
                ? $request->file('banner')->store('uploads/vendor/banner', 'public')
                : null;

            $vendor = \App\Models\Vendor::create([
                'shop_name' => $request->shop_name,
                'slug' => $request->slug,
                'owner_name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'address' => $request->address,
                'logo' => $logoPath,
                'banner' => $bannerPath,
                'status' => 1,
            ]);

            $user = \App\Models\User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => HashFacade::make($request->password),
                'status' => 1,
                'vendor_id' => $vendor->id,
            ]);
            $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'vendor', 'guard_name' => 'admin']);
            $user->assignRole($role);
            Auth::guard('admin')->login($user);

            Toastr::success('Vendor account created successfully!', 'Success');
            return redirect()->route('vendor.dashboard');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:55|unique:customers,phone',
            'email' => 'nullable|email|unique:customers,email',
            'password' => 'required|confirmed|min:6',
        ]);

        $nextId = (int) (Customer::max('id') ?? 0) + 1;
        $customer = Customer::create([
            'name' => $request->name,
            'slug' => strtolower(Str::slug($request->name . '-' . $nextId)),
            'phone' => $request->phone,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'verify' => 1,
            'status' => 'active',
        ]);
        $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'customer']);
        $customer->assignRole($role);

        Toastr::success('Account created successfully.', 'Success');
        return redirect()->route('customer.login');
    }

    public function verify()
    {
        return view('frontEnd.layouts.customer.verify');
    }

    public function resendotp(Request $request)
    {
        $customer_info = Customer::where('phone',session::get('verify_phone'))->first();
        $customer_info->verify = rand(1111,9999);
        $customer_info->save();
        $site_setting = GeneralSetting::where('status', 1)->first();
        $sms_gateway = SmsGateway::where('status', 1)->first();

        if($sms_gateway) {
            $url = "$sms_gateway->url";
            $data = [
                "api_key" => "$sms_gateway->api_key",
                "number" => $customer_info->phone,
                "type" => 'text',
                "senderid" => "$sms_gateway->serderid",
                "message" => "Dear $customer_info->name!\r\nYour account verify OTP is $customer_info->verify \r\nThank you for using $site_setting->name"
            ];
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_exec($ch);
            curl_close($ch);
        }

        Toastr::success('Success','Resend code send successfully');
        return redirect()->back();
    }

    public function account_verify(Request $request)
    {
        $this->validate($request,['otp' => 'required']);
        $customer_info = Customer::where('phone',session::get('verify_phone'))->first();

        if($customer_info->verify != $request->otp){
            Toastr::error('Success','Your OTP not match');
            return redirect()->back();
        }

        $customer_info->verify = 1;
        $customer_info->status = 'active';
        $customer_info->save();
        Auth::guard('customer')->loginUsingId($customer_info->id);
        return redirect()->route('customer.account');
    }

    public function forgot_password()
    {
        return view('frontEnd.layouts.customer.forgot_password');
    }

    public function forgot_verify(Request $request)
    {
        $request->validate(['phone' => 'required|string']);
        $phone = $request->phone;
        $customer = Customer::where('phone', $phone)->first();
        $vendor = null;

        if ($customer && $this->isRetiredResellerEmail($customer->email)) {
            Toastr::error('The reseller portal is no longer available.');
            return back();
        }

        if ($customer) {
            $customer->forgot = rand(1111, 9999);
            $customer->save();
            $userType = 'customer';
            $name = $customer->name;
            $otp = $customer->forgot;
        } else {
            $vendor = \App\Models\Vendor::where('phone', $phone)->first();
            if (!$vendor) {
                Toastr::error('Your phone number not found');
                return back();
            }
            $vendor->forgot = rand(1111, 9999);
            $vendor->save();
            $userType = 'vendor';
            $name = $vendor->owner_name;
            $otp = $vendor->forgot;
        }

        $this->sendForgotOtp($phone, (string) $name, (string) $otp, true);
        Session::put('verify_phone', $phone);
        Session::put('user_type', $userType);
        Toastr::success('OTP sent successfully to your phone');
        return redirect()->route('customer.forgot.reset');
    }

    public function forgot_resend(Request $request)
    {
        $phone = Session::get('verify_phone');
        $userType = Session::get('user_type');

        if ($userType === 'customer') {
            $account = Customer::where('phone', $phone)->first();
            if ($account && $this->isRetiredResellerEmail($account->email)) $account = null;
            $name = $account?->name;
        } elseif ($userType === 'vendor') {
            $account = \App\Models\Vendor::where('phone', $phone)->first();
            $name = $account?->owner_name;
        } else {
            $account = null;
            $name = null;
        }

        if (!$account) {
            Toastr::error('Something went wrong');
            return redirect()->route('customer.forgot.password');
        }

        $account->forgot = rand(1111, 9999);
        $account->save();
        $this->sendForgotOtp((string) $phone, (string) $name, (string) $account->forgot);
        Toastr::success('Success', 'Resend code send successfully');
        return redirect()->back();
    }

    public function forgot_reset()
    {
        if(!Session::get('verify_phone')){
          Toastr::error('Something wrong please try again');
          return redirect()->route('customer.forgot.password'); 
        }
        return view('frontEnd.layouts.customer.forgot_reset');
    }

    public function forgot_store(Request $request)
    {
        $request->validate(['otp' => 'required', 'password' => 'required|confirmed|min:6']);
        $phone = Session::get('verify_phone');
        $userType = Session::get('user_type');

        if ($userType === 'customer') {
            $customer = Customer::where('phone', $phone)->first();
            if (!$customer || $this->isRetiredResellerEmail($customer->email) || (string) $customer->forgot !== (string) $request->otp) {
                Toastr::error('Your OTP not match');
                return redirect()->back();
            }
            $customer->forgot = 1;
            $customer->password = bcrypt($request->password);
            $customer->save();
            Auth::guard('customer')->attempt(['phone' => $customer->phone, 'password' => $request->password]);
            $redirect = redirect()->intended('customer/account');
        } elseif ($userType === 'vendor') {
            $vendor = \App\Models\Vendor::where('phone', $phone)->first();
            if (!$vendor || (string) $vendor->forgot !== (string) $request->otp) {
                Toastr::error('Your OTP not match');
                return redirect()->back();
            }
            $user = \App\Models\User::where('email', $vendor->email)->first();
            if (!$user) {
                Toastr::error('User account not found');
                return redirect()->route('customer.forgot.password');
            }
            $user->password = bcrypt($request->password);
            $user->save();
            $vendor->forgot = 1;
            $vendor->save();
            $redirect = redirect()->route('customer.login');
        } else {
            Toastr::error('Something went wrong');
            return redirect()->route('customer.forgot.password');
        }

        Session::forget(['verify_phone', 'user_type']);
        Toastr::success('Password reset successfully.', 'Success');
        return $redirect;
    }

    private function isRetiredResellerEmail(?string $email): bool
    {
        if (!$email) return false;
        return \App\Models\User::where('email', $email)
            ->where(function ($query) {
                $query->where('role', 'reseller')
                    ->orWhereHas('roles', fn ($role) => $role->whereRaw('LOWER(name) = ?', ['reseller']));
            })->exists();
    }

    private function sendForgotOtp(string $phone, string $name, string $otp, bool $forgotOnly = false): void
    {
        $site = GeneralSetting::where('status', 1)->first();
        $gateway = SmsGateway::where('status', 1)
            ->when($forgotOnly, fn ($query) => $query->where('forget_pass', 1))
            ->first();
        if (!$gateway) return;

        $ch = curl_init((string) $gateway->url);
        curl_setopt_array($ch, [
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => [
                'api_key' => $gateway->api_key,
                'number' => $phone,
                'type' => 'text',
                'senderid' => $gateway->serderid,
                'message' => "Dear {$name}!\r\nYour forgot password verify OTP is {$otp}\r\nThank you for using " . ($site->name ?? 'our shop'),
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        curl_exec($ch);
        curl_close($ch);
    }

    public function account()
    {
        return view('frontEnd.layouts.customer.account');
    }

    public function logout(Request $request)
    {
        Auth::guard('customer')->logout();
        Toastr::success('You are logout successfully', 'success!');
        return redirect()->route('customer.login');
    }

    public function checkout()
    {
        $shippingcharge = ShippingCharge::where('status',1)->get();
        $select_charge = ShippingCharge::where('status',1)->first();
        $bkash_gateway = PaymentGateway::where(['status'=> 1, 'type'=>'bkash'])->first();
        $shurjopay_gateway = PaymentGateway::where(['status'=> 1, 'type'=>'shurjopay'])->first();
        $uddoktapay_gateway = PaymentGateway::where(['status'=> 1, 'type'=>'uddoktapay'])->first();
        $aamarpay_gateway = PaymentGateway::where(['status'=> 1, 'type'=>'aamarpay'])->first();

        // ⭐ Free Delivery Check - যদি সব প্রোডাক্ট free delivery eligible হয়, shipping charge 0
        $hasAllFreeDelivery = \App\Http\Controllers\Frontend\ShoppingController::hasAllFreeDeliveryProducts();
        $shippingAmount = $hasAllFreeDelivery ? 0 : ($select_charge->amount ?? 0);
        Session::put('shipping', $shippingAmount);

        $advanceTotal = \App\Http\Controllers\Frontend\ShoppingController::getCartAdvanceAmount();
        $hasAdvance   = $advanceTotal > 0;

        // ⭐ কার্টে ডিজিটাল প্রোডাক্ট আছে কি না
        $hasDigital = \App\Http\Controllers\Frontend\ShoppingController::hasDigitalProductInCart();

        return view('frontEnd.layouts.customer.checkout',compact(
            'shippingcharge',
            'bkash_gateway',
            'shurjopay_gateway',
            'uddoktapay_gateway',
            'aamarpay_gateway',
            'advanceTotal',
            'hasAdvance',
            'hasDigital',
            'hasAllFreeDelivery'
        ));
    }

public function order_save(Request $request)
    {
        $this->validate($request,[
            'name'=>'required',
            'phone'=>'required',
            'address'=>'required',
            'area'=>'required',
        ]);

        if(Cart::instance('shopping')->count() <= 0) {
            Toastr::error('Your shopping empty', 'Failed!');
            return redirect()->back();
        }

        /* ─────────────────────────────────────────────────────────────────
         * অর্ডার রেস্ট্রিকশন v2 — শুধু ফোন নম্বর দিয়ে গণনা।
         *
         * v1-এ IP ঠিকানা দিয়েও ম্যাচ করা হতো, যা CGNAT/শেয়ার্ড ওয়াইফাইয়ে
         * নির্দোষ কাস্টমারকে আটকে দিত — সেই অংশটি বাদ দেওয়া হয়েছে।
         *
         * এখন এটি চালু হয় শুধু তখনই যখন অ্যাডমিন সেটিংস পেজ থেকে স্পষ্টভাবে
         * সুইচটি চালু করে (general_settings.order_limit_enabled)। হোয়াইটলিস্ট
         * করা নম্বর (পাইকারি ক্রেতা) কখনোই আটকাবে না।
         * ───────────────────────────────────────────────────────────────── */
        $restrictionMessage = app(\App\Services\OrderRestrictionService::class)
            ->violationMessage($request->phone);

        if ($restrictionMessage) {
            Toastr::error($restrictionMessage, 'অর্ডার সীমা অতিক্রান্ত!');
            return redirect()->back()
                ->withErrors(['order_limit' => $restrictionMessage])
                ->withInput();
        }

        // ⭐ ভ্যারিয়েন্ট (সাইজ/কালার) ভ্যালিডেশন — অর্ডারের আগে স্টক নিশ্চিত করি
        foreach (Cart::instance('shopping')->content() as $cartItem) {
            $variantMatrix = ProductVariantPrice::where('product_id', $cartItem->id);
            if (!(clone $variantMatrix)->exists()) {
                continue;
            }

            $requiresSize  = (clone $variantMatrix)->whereNotNull('size_id')->exists();
            $requiresColor = (clone $variantMatrix)->whereNotNull('color_id')->exists();
            $sizeId  = $cartItem->options->size_id ?? null;
            $colorId = $cartItem->options->color_id ?? null;

            if (($requiresSize && !$sizeId) || ($requiresColor && !$colorId)) {
                Toastr::error('অর্ডারের আগে "' . $cartItem->name . '" এর সাইজ/কালার সিলেক্ট করুন।', 'Failed!');
                return redirect()->back()
                    ->withErrors(['variant' => 'অর্ডারের আগে "' . $cartItem->name . '" এর সাইজ/কালার সিলেক্ট করুন।'])
                    ->withInput();
            }

            $variant = ($cartItem->options->variant_price_id ?? null)
                ? ProductVariantPrice::find($cartItem->options->variant_price_id)
                : (clone $variantMatrix)
                    ->when($sizeId, fn ($q) => $q->where('size_id', $sizeId))
                    ->when($colorId, fn ($q) => $q->where('color_id', $colorId))
                    ->first();

            if ($variant && $variant->stock !== null && (int) $variant->stock < (int) $cartItem->qty) {
                $left = max(0, (int) $variant->stock);

                $message = $left > 0
                    ? '"' . $cartItem->name . '" এর নির্বাচিত সাইজ/কালারের মাত্র ' . $left . ' টি বাকি আছে। পরিমাণ কমিয়ে নিন।'
                    : '"' . $cartItem->name . '" এর নির্বাচিত সাইজ/কালারটি স্টকে নেই। অন্য অপশন বেছে নিন।';

                Toastr::error($message, 'স্টক আউট!');
                return redirect()->back()
                    ->withErrors(['variant' => $message])
                    ->withInput();
            }
        }

        // ⭐ ভ্যারিয়েন্টবিহীন সাধারণ প্রোডাক্টের স্টকও যাচাই করি — আগে শুধু
        // ভ্যারিয়েন্ট দেখা হতো, ফলে স্টক শূন্য থাকা সাধারণ প্রোডাক্টও অর্ডার হয়ে যেত।
        foreach (Cart::instance('shopping')->content() as $cartItem) {
            if (ProductVariantPrice::where('product_id', $cartItem->id)->exists()) {
                continue; // উপরে যাচাই হয়ে গেছে
            }

            $product = Product::find($cartItem->id);

            if (!$product || !\Illuminate\Support\Facades\Schema::hasColumn('products', 'stock')) {
                continue;
            }

            // ডিজিটাল প্রোডাক্টের স্টক গোনার দরকার নেই
            if ((int) ($product->is_digital ?? 0) === 1) {
                continue;
            }

            if ((int) $product->stock < (int) $cartItem->qty) {
                $left = max(0, (int) $product->stock);

                $message = $left > 0
                    ? '"' . $cartItem->name . '" এর মাত্র ' . $left . ' টি স্টকে আছে। পরিমাণ কমিয়ে নিন।'
                    : '"' . $cartItem->name . '" এখন স্টকে নেই।';

                Toastr::error($message, 'স্টক আউট!');
                return redirect()->back()
                    ->withErrors(['stock' => $message])
                    ->withInput();
            }
        }

        // ⭐ কার্টে ডিজিটাল প্রোডাক্ট আছে কি না চেক
        $hasDigital = \App\Http\Controllers\Frontend\ShoppingController::hasDigitalProductInCart();

        if ($hasDigital && $request->payment_method === 'cod') {
            Toastr::error('ডিজিটাল প্রোডাক্টের জন্য Cash On Delivery পাওয়া যায় না, অনুগ্রহ করে অনলাইন পেমেন্ট সিলেক্ট করুন।', 'Failed!');
            return redirect()->back();
        }

        // Amount ক্যালকুলেশন
        $subtotal = (float) str_replace([',','.00'],'',Cart::instance('shopping')->subtotal());

        // ⭐ কুপনের ব্যবহারের সীমা শেষ মুহূর্তে আরেকবার যাচাই।
        // কার্টে কুপন বসানোর পর অন্য কেউ শেষ ব্যবহারটি নিয়ে নিতে পারে,
        // তাই অর্ডার সেভ করার ঠিক আগে ফোন নম্বর সহ আবার চেক করি।
        $couponCode = Session::get('coupon_code');

        if ($couponCode) {
            $recheck = app(\App\Services\CouponService::class)->apply($couponCode, $request->phone);

            if (!$recheck['ok']) {
                Toastr::error($recheck['message'], 'কুপন বাতিল');
                return redirect()->back()->withInput();
            }
        }

        $discount = Session::get('discount', 0);
        
        // ⭐ Free Delivery Check - যদি সব প্রোডাক্ট free delivery eligible হয়, shipping charge 0
        $hasAllFreeDelivery = \App\Http\Controllers\Frontend\ShoppingController::hasAllFreeDeliveryProducts();
        $shipping_area = null;
        
        if ($hasAllFreeDelivery) {
            $shippingfee = 0;
            Session::put('shipping', 0);
        } else {
            $shipping_area = ShippingCharge::where('id', $request->area)->first();
            $shippingfee = $shipping_area ? $shipping_area->amount : Session::get('shipping', 0);
            Session::put('shipping', $shippingfee);
        }

        // কার্টের advance item গুলোর মোট
        $advanceTotal = \App\Http\Controllers\Frontend\ShoppingController::getCartAdvanceAmount();

        // ইনভয়েসে দেখানোর মোট (Grand Total)
        $grandTotal = ($subtotal + $shippingfee) - $discount;

        // =========================================================
        // ⭐ ফিক্সড লজিক: গেটওয়েতে কত টাকা পাঠাবো?
        // =========================================================
        // যদি এডভান্স থাকে, তাহলে শুধু এডভান্স এমাউন্ট পে করতে হবে।
        // যদি না থাকে, তাহলে পুরো গ্র্যান্ড টোটাল পে করতে হবে।
        $payable_amount = ($advanceTotal > 0) ? $advanceTotal : $grandTotal;

        // Customer ঠিক করা
        if(Auth::guard('customer')->user()){
            $customer_id = Auth::guard('customer')->user()->id;
        }else{
            $exist = Customer::where('phone',$request->phone)->select('id')->first();
            if($exist){
                $customer_id = $exist->id;
            }else{
                $password = rand(111111,999999);
                $store = new Customer();
                $store->name = $request->name;
                $store->slug = Str::slug($request->name);
                $store->phone = $request->phone;
                $store->password = bcrypt($password);
                $store->verify = 1;
                $store->status = 'active';
                $store->save();
                $customer_id = $store->id;
            }
        }

        // Main Order save
        $order = new Order();
        $order->invoice_id      = rand(11111,99999);
        $order->amount          = $grandTotal; // অর্ডারে সবসময় টোটাল এমাউন্ট থাকবে
        $order->shipping_charge = $shippingfee;
        $order->customer_id     = $customer_id;
        $order->order_status    = 1;
        $order->note            = $request->note;
        $order->order_note      = $request->order_note;
        $order->payment_status  = 'pending';
        $order->coupon_code     = Session::get('coupon_code') ?? null;
        $order->discount        = $discount ?? 0;
        $order->ip_address      = $request->ip();

        // ⭐ কোন ক্যাম্পেইন থেকে অর্ডারটি এলো — কনভার্শন রেট হিসাব করতে দরকার।
        // কলামটি না থাকলে (মাইগ্রেশন চালানোর আগে) চুপচাপ এড়িয়ে যাই।
        $campaignId = Session::get('active_campaign_id');

        if ($campaignId && \Illuminate\Support\Facades\Schema::hasColumn('orders', 'campaign_id')) {
            $order->campaign_id = $campaignId;
        }

        $order->save();

        // Shipping info
        $shipping = new Shipping();
        $shipping->order_id    = $order->id;
        $shipping->customer_id = $customer_id;
        $shipping->name        = $request->name;
        $shipping->phone       = $request->phone;
        $shipping->address     = $request->address;
        
        if ($shipping_area) {
            $shipping->area = $shipping_area->name;
        } else {
            $shipping->area = 'Digital / Free Shipping';
        }
        $shipping->save();

        // Payment info
        $payment = new Payment();
        $payment->order_id       = $order->id;
        $payment->customer_id    = $customer_id;
        $payment->payment_method = $request->payment_method;

        // =========================================================
        // ⭐ ফিক্সড লজিক: ডাটাবেসে কত টাকা সেভ করব?
        // =========================================================
        if (in_array($request->payment_method, ['bkash', 'shurjopay', 'uddoktapay', 'aamarpay'])) {
            // অনলাইন পেমেন্ট: শুরুতে ০ রাখব। পেমেন্ট ক্যান্সেল হলে ০ থাকবে (Unpaid দেখাবে)।
            // পেমেন্ট সাকসেস হলে IPN/Callback এসে এই ০ কে আপডেট করে $payable_amount বসিয়ে দিবে।
            $payment->amount = 0; 
        } else {
            // COD: এখানে সরাসরি এমাউন্ট বসিয়ে দিব
            $payment->amount = $payable_amount;
        }

        $payment->payment_status = 'pending';
        $payment->save();

        // Order details save
        OrderHelper::saveOrderDetails($order);

        // Stock reduce
        // name/status-ও লোড করি — স্টক অ্যালার্ট ও অটো আউট-অফ-স্টকের জন্য দরকার
        $details = OrderDetails::where('order_id', $order->id)
            ->with('product:id,name,stock,status')
            ->get();

        foreach ($details as $row) {
            $variant = $row->variant_price_id
                ? ProductVariantPrice::find($row->variant_price_id)
                : ProductVariantPrice::where('product_id', $row->product_id)
                    ->when($row->product_size, fn($q) => $q->where('size_id', $row->product_size))
                    ->when($row->product_color, fn($q) => $q->where('color_id', $row->product_color))
                    ->first();
            if ($variant && $variant->stock !== null) {
                $variant->stock = max(0, (int) $variant->stock - (int) $row->qty);
                $variant->save();
            } elseif ($row->product) {
                $row->product->stock = max(0, $row->product->stock - $row->qty);
                $row->product->save();
            }
        }

        // ⭐ স্টক অ্যালার্ট — স্টক কমানোর পরেই দেখি কোনটা ফুরিয়ে গেল কিনা।
        // ফুরিয়ে গেলে অ্যাডমিনের জন্য অ্যালার্ট তৈরি হয় এবং সব ভ্যারিয়েন্ট
        // শেষ হলে প্রোডাক্টটি স্বয়ংক্রিয়ভাবে নিষ্ক্রিয় হয়ে যায়।
        try {
            app(\App\Services\StockAlertService::class)->checkOrderItems($details);
        } catch (\Throwable $e) {
            Log::warning('Stock alert check failed for order ' . $order->id . ': ' . $e->getMessage());
        }

 // === Customer SMS ===
        try {
            $customerPhone = isset($shipping) && $shipping->phone ? $shipping->phone : ($request->phone ?? ($order->customer->phone ?? null));
            $customerName  = isset($shipping) && $shipping->name ? $shipping->name : ($request->name ?? ($order->customer->name ?? 'Customer'));

            app(\App\Services\OrderNotificationService::class)
                ->orderSms($order, $customerPhone, $customerName);
        } catch (\Exception $e) {
            \Log::error("Customer SMS error for order {$order->id}: " . $e->getMessage());
        }

        // === Admin SMS ===
        try {
            $customerName  = isset($request->name) ? $request->name : ($order->customer->name ?? 'Customer');
            $customerPhone = isset($request->phone) ? $request->phone : ($order->customer->phone ?? '');

            app(\App\Services\OrderNotificationService::class)
                ->adminOrderSms($order, $customerName, $customerPhone);
        } catch (\Exception $e) {
            \Log::error('Admin SMS send failed: ' . $e->getMessage());
        }

        // Incomplete order delete
        // (সেভ করার সময় নম্বর normalize হয়, তাই দুই ফরম্যাটেই ডিলিট করা হচ্ছে)
        $normalizedPhone = preg_replace('/[^0-9+]/', '', (string) $request->phone);
        IncompleteOrder::whereIn('phone', array_unique(array_filter([$request->phone, $normalizedPhone])))->delete();

        // ⭐ ক্যাম্পেইন অ্যানালিটিক্স — অর্ডার ও বিক্রির টাকা গোনা হয়।
        if ($campaignId) {
            app(\App\Services\CampaignAnalyticsService::class)
                ->recordOrder($campaignId, (float) $grandTotal);

            // অর্ডার হয়ে গেছে, তাই ফানেলের এই ধাপ শেষ — সেশন পরিষ্কার করি
            // যাতে পরের সাধারণ অর্ডার ভুল করে এই ক্যাম্পেইনে গোনা না হয়।
            Session::forget('active_campaign_id');
        }

        // ⭐ কুপন ব্যবহারের হিসাব — অর্ডার তৈরি হওয়ার পরেই গোনা হয়,
        // কার্টে কুপন বসানোর সময় নয়। নাহলে কেউ কুপন বসিয়ে অর্ডার না করলেও
        // সীমা কমে যেত।
        if ($order->coupon_code) {
            app(\App\Services\CouponService::class)->recordUsage(
                $order->coupon_code,
                $request->phone,
                $order->id,
                $customer_id,
                (float) ($order->discount ?? 0)
            );
        }

        // ⭐ অটো ফ্রড চেক — আগে শুধু অ্যাডমিন ম্যানুয়ালি বাটনে ক্লিক করলে চলত।
        // response পাঠানোর পরে (non-blocking) চলবে, তাই কাস্টমারকে অপেক্ষা করতে হয় না।
        try {
            app(\App\Services\FraudCheckService::class)
                ->queueAfterResponse($request->phone, $order->id);
        } catch (\Exception $e) {
            Log::error('Auto fraud check setup failed for order ' . $order->id . ': ' . $e->getMessage());
        }

        // =========================================================
        // ⭐ পেমেন্ট গেটওয়ে রিডাইরেক্ট (FIXED)
        // =========================================================
        
        // Bkash এবং UddoktaPay এর জন্য সেশনে এমাউন্ট সেট করে দিচ্ছি 
        // যাতে ওই কন্ট্রোলারগুলো সঠিক এমাউন্ট পায়
        Session::put('payable_amount', $payable_amount);

        if($request->payment_method == 'bkash'){
            Session::forget('coupon_code');
            Session::forget('discount');
            return redirect('/bkash/checkout-url/create?order_id='.$order->id);

        } elseif($request->payment_method == 'shurjopay'){

            $info = [
                'currency'        => "BDT",
                'amount'          => $payable_amount, // ✅ এখানে ফিক্স করা হলো: এডভান্স থাকলে এডভান্স, না হলে ফুল
                'order_id'        => uniqid(),
                'client_ip'       => $request->ip(),
                'customer_name'   => $request->name,
                'customer_phone'  => $request->phone,
                'email'           => "customer@gmail.com",
                'customer_address'=> $request->address,
                'customer_city'   => $request->area,
                'customer_country'=> "BD",
                'value1'          => $order->id
            ];

            Session::forget('coupon_code');
            Session::forget('discount');

            $sp = new ShurjopayController();
            return $sp->checkout($info);

        } elseif($request->payment_method == 'uddoktapay'){
            Session::forget('coupon_code');
            Session::forget('discount');
            return redirect()->route('uddoktapay.checkout',['order_id'=>$order->id]);

        } elseif($request->payment_method == 'aamarpay'){
            Session::forget('coupon_code');
            Session::forget('discount');
            return redirect()->route('aamarpay.checkout',['order_id'=>$order->id]);

        } else {
            // Cash On Delivery
            $this->createDigitalDownloads($order);
            
            // Send Facebook Purchase event for COD orders (async - don't block order submission)
            try {
                $customer = Customer::find($customer_id);
                $userData = [];
                
                // Get customer email or phone
                if ($customer && $customer->email) {
                    $userData['email'] = $customer->email;
                } elseif ($request->phone) {
                    $userData['phone'] = $request->phone;
                } elseif ($customer && $customer->phone) {
                    $userData['phone'] = $customer->phone;
                }
                
                // Get Facebook Pixel cookies if available
                if (isset($_COOKIE['_fbp'])) {
                    $userData['fbp'] = $_COOKIE['_fbp'];
                }
                if (isset($_COOKIE['_fbc'])) {
                    $userData['fbc'] = $_COOKIE['_fbc'];
                }
                
                // Send Purchase event after response is sent (non-blocking)
                // Use register_shutdown_function to send after response is sent to user
                register_shutdown_function(function () use ($order, $userData, $request) {
                    try {
                        $orderDetails = $order->orderdetails ?? \App\Models\Order::with('orderdetails')->find($order->id)?->orderdetails ?? collect();
                        $contentIds  = $orderDetails->pluck('product_id')->map(fn($id) => (string)$id)->values()->toArray();
                        $contents    = $orderDetails->map(fn($i) => ['id' => (string)$i->product_id, 'quantity' => (int)$i->qty, 'item_price' => (float)$i->sale_price])->values()->toArray();
                        app(\App\Services\FacebookCapiService::class)->sendEvent('Purchase', [
                            'currency'     => 'BDT',
                            'value'        => $order->amount,
                            'order_id'     => $order->invoice_id ?? $order->id,
                            'content_ids'  => $contentIds,
                            'contents'     => $contents,
                            'num_items'    => count($contents),
                            'content_type' => 'product',
                        ], $userData, [
                            'event_id'          => 'purchase_' . ($order->invoice_id ?? $order->id),
                            'event_source_url'  => $request->fullUrl(),
                        ]);
                    } catch (\Exception $e) {
                        \Log::error('Facebook CAPI Purchase event failed for order ' . $order->id . ': ' . $e->getMessage());
                    }
                });
            } catch (\Exception $e) {
                \Log::error('Facebook CAPI setup failed for order ' . $order->id . ': ' . $e->getMessage());
            }
            
            Session::forget('coupon_code');
            Session::forget('discount');
            return redirect('customer/order-success/'.$order->id);
        }
    }


    public function orders()
    {
        $orders = Order::where('customer_id',Auth::guard('customer')->user()->id)
            ->with(['status', 'orderdetails.product.image', 'orderdetails.image'])
            ->latest()
            ->paginate(10);

        return view('frontEnd.layouts.customer.orders',compact('orders'));
    }

    public function order_success($id)
    {
        $order = Order::with(['orderdetails.size', 'orderdetails.color', 'shipping'])
            ->where('id', $id)
            ->firstOrFail();
        return view('frontEnd.layouts.customer.order_success', compact('order'));
    }

    public function invoice(Request $request)
    {
        $order = Order::where([
                'id'=>$request->id,
                'customer_id'=>Auth::guard('customer')->user()->id
            ])
            ->with(['orderdetails.size', 'orderdetails.color', 'payment', 'shipping', 'customer'])
            ->firstOrFail();

        return view('frontEnd.layouts.customer.invoice',compact('order'));
    }

    public function order_note(Request $request)
    {
        $order = Order::where([
                'id'=>$request->id,
                'customer_id'=>Auth::guard('customer')->user()->id
            ])->firstOrFail();

        return view('frontEnd.layouts.customer.order_note',compact('order'));
    }

    public function profile_edit(Request $request)
    {
        $profile_edit = Customer::where(['id'=>Auth::guard('customer')->user()->id])->firstOrFail();
        $districts = District::distinct()->select('district')->get();
        $areas = District::where(['district'=>$profile_edit->district])->select('area_name','id')->get();
        
        // Refresh the model to get latest data
        $profile_edit->refresh();
        
        return view('frontEnd.layouts.customer.profile_edit',compact('profile_edit','districts','areas'));
    }

    public function profile_update(Request $request)
    {
        $update_data = Customer::where(['id'=>Auth::guard('customer')->user()->id])->firstOrFail();

        // Validation
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'required|email|max:255|unique:customers,email,'.$update_data->id,
            'address' => 'required|string|max:500',
            'district' => 'required|string|max:100',
            'area' => 'required|integer',
            'image' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
        ]);

        $image = $request->file('image');
        if($image){
            try {
                // Delete old image if exists
                if ($update_data->image) {
                    $oldImagePath = public_path($update_data->image);
                    if (file_exists($oldImagePath)) {
                        @unlink($oldImagePath);
                    }
                }

                $name =  time().'-'.$image->getClientOriginalName();
                $name = preg_replace('"\.(jpg|jpeg|png|webp)$"', '.webp',$name);
                $name = strtolower(Str::slug($name));
                
                // Directory path with public/ prefix
                $uploadpath = 'public/uploads/customer/';
                $uploadFullPath = public_path($uploadpath);
                
                // Create directory if not exists
                if (!file_exists($uploadFullPath)) {
                    \Illuminate\Support\Facades\File::makeDirectory($uploadFullPath, 0755, true);
                }
                
                // Full path for saving
                $imageUrl = $uploadFullPath . $name;
                
                // Process and save image
                $img = Image::make($image->getRealPath());
                $img->encode('webp', 90);
                $img->resize(300, 300, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
                $img->save($imageUrl);
                
                // Verify image was saved
                if (!file_exists($imageUrl)) {
                    throw new \Exception('Image file was not saved successfully');
                }
                
                // Save path in database (with public/ prefix for asset() helper)
                $imageUrl = $uploadpath . $name;
            } catch (\Exception $e) {
                Toastr::error('Image upload failed: ' . $e->getMessage(), 'Error!');
                return redirect()->back()->withInput();
            }
        }else{
            $imageUrl = $update_data->image;
        }

        $update_data->name = $request->name;
        $update_data->phone = $request->phone;
        $update_data->email = $request->email;
        $update_data->address = $request->address;
        $update_data->district = $request->district;
        $update_data->area = $request->area;
        $update_data->image = $imageUrl;
        $update_data->save();

        // Refresh the model to get updated attributes
        $update_data->refresh();

        Toastr::success('আপনার প্রোফাইল সফলভাবে আপডেট হয়েছে', 'সফল!');
        return redirect()->route('customer.profile_edit');
    }

   public function order_track_result(Request $request)
    {
        $phone = $request->phone;
        $invoice_id = $request->invoice_id;

        // ১. ভ্যালিডেশন: অন্তত একটি ইনপুট থাকতে হবে
        if (!$phone && !$invoice_id) {
            Toastr::error('অনুগ্রহ করে মোবাইল নাম্বার অথবা ইনভয়েস আইডি দিন', 'Error');
            return redirect()->back();
        }

        // ২. কুয়েরি শুরু (Order মডেল ব্যবহার করে)
        $query = Order::query();

        // যদি ইনভয়েস আইডি দেওয়া থাকে
        if ($invoice_id) {
            $query->where('invoice_id', $invoice_id);
        }

        // যদি ফোন নম্বর দেওয়া থাকে
        if ($phone) {
            // আমরা Shipping টেবিল চেক করব কারণ অর্ডারের ফোন নম্বর সেখানেই থাকে
            $query->whereHas('shipping', function($q) use ($phone){
                $q->where('phone', $phone);
            });
        }

        // ৩. ডাটা নিয়ে আসা (Eager Loading সহ)
        // latest() দিলে নতুন অর্ডার আগে দেখাবে
        $order = $query->with(['shipping', 'status', 'orderdetails'])->latest()->get();

        // ৪. যদি কোনো অর্ডার না পাওয়া যায়
        if ($order->count() == 0) {
            Toastr::error('দুঃখিত! কোনো অর্ডার পাওয়া যায়নি।', 'Failed');
            return redirect()->back();
        }

        // ৫. ভিউতে ডাটা পাঠানো
        // আপনার কন্ট্রোলারে ভিউয়ের নাম 'tracking_result' দেওয়া আছে, তাই সেটিই রাখলাম।
        // কিন্তু নিশ্চিত হোন আপনার ব্লেড ফাইলের নাম tracking_result.blade.php নাকি track_order.blade.php
        return view('frontEnd.layouts.customer.tracking_result', compact('order'));
    }
// এই ফাংশনটি মিসিং থাকার কারণেই এরর আসছিল
    public function order_track()
    {
        return view('frontEnd.layouts.customer.order_track');
    }
    public function change_pass()
    {
        return view('frontEnd.layouts.customer.change_password');
    }

    public function password_update(Request $request)
    {
        $this->validate($request, [
            'old_password'=>'required',
            'new_password'=>'required',
            'confirm_password' => 'required_with:new_password|same:new_password|'
        ]);

        $customer = Customer::find(Auth::guard('customer')->user()->id);
        $hashPass = $customer->password;

        if (Hash::check($request->old_password, $hashPass)) {
            $customer->fill([
                'password' => Hash::make($request->new_password)
            ])->save();

            Toastr::success('Success', 'Password changed successfully!');
            return redirect()->route('customer.account');
        }else{
            Toastr::error('Failed', 'Old password not match!');
            return redirect()->back();
        }
    }

    // =====================================
    // ⭐ DIGITAL DOWNLOAD CREATOR (HELPER)
    // =====================================
    private function createDigitalDownloads(Order $order)
    {
        // orderdetails থেকে product_id নিয়ে Product লোড করব
        $items = OrderDetails::where('order_id', $order->id)->get();

        foreach ($items as $item) {
            $product = Product::find($item->product_id);

            if ($product && $product->is_digital == 1 && $product->digital_file) {

                // একই order+product+customer এর জন্য ডুপ্লিকেট না হয়
                DigitalDownload::firstOrCreate(
                    [
                        'order_id'    => $order->id,
                        'product_id'  => $product->id,
                        'customer_id' => $order->customer_id,
                    ],
                    [
                        'token'               => Str::uuid(),
                        'file_path'           => $product->digital_file,
                        'remaining_downloads' => $product->download_limit ?? 5,
                        'expires_at'          => $product->download_expire_days
                                                    ? now()->addDays($product->download_expire_days)
                                                    : null,
                    ]
                );
            }
        }
    }
}
