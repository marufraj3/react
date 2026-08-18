<?php

namespace Database\Seeders;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Contact;
use App\Models\Coupon;
use App\Models\GeneralSetting;
use App\Models\Product;
use App\Models\Productimage;
use App\Models\ShippingCharge;
use Illuminate\Database\Seeder;

/**
 * Minimal demo data so the React storefront can be tested end-to-end on a
 * fresh database (php artisan migrate --seed). Safe to run multiple times —
 * it upserts on known keys/slugs.
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // ---- Store settings -------------------------------------------------
        GeneralSetting::firstOrCreate(
            ['id' => 1],
            [
                'name'                => 'Shop Genie',
                'top_headline'        => '🔥 বিশেষ অফার! সারা দেশে দ্রুত ক্যাশ অন ডেলিভারি — হেল্পলাইন: +8801849832178',
                'news_ticker_enabled' => 1,
                'checkout_note'       => '১০০% নিশ্চিত হয়ে অর্ডার করুন। ডেলিভারির সময় প্রোডাক্ট চেক করে নিন।',
                'order_policy'        => 'ঢাকা সিটিতে ২৪-৪৮ ঘণ্টা, ঢাকার বাইরে ২-৩ কর্মদিবসে ডেলিভারি।',
                'primary_color'       => '#111827',
                'secodery_color'      => '#dc2626',
                'hot_deal_end_date'   => '2027-12-31',
                'flash_sale_end_date' => '2027-12-31',
                'footer_about_text'   => 'Shop Genie — বাংলাদেশের বিশ্বস্ত অনলাইন শপিং প্ল্যাটফর্ম।',
                'facebook_page_username' => 'shopgeniebd',
                'status'              => 1,
            ]
        );

        // ---- Contact info (hotline/email/address/whatsapp) ------------------
        Contact::firstOrCreate(
            ['id' => 1],
            [
                'hotline'  => '+8801849832178',
                'email'    => 'support@shopgenie.com.bd',
                'address'  => 'House #42, Road #11, Sector #04, Uttara, Dhaka - 1230',
                'whatsapp' => '+8801849832178',
                'status'   => 1,
            ]
        );

        // ---- Shipping charges ------------------------------------------------
        $shipping = [
            ['id' => 1, 'name' => 'Inside Dhaka City (ঢাকা সিটির ভিতরে)', 'amount' => 60],
            ['id' => 2, 'name' => 'Dhaka Sub-Area (সাভার, কেরানীগঞ্জ, গাজীপুর)', 'amount' => 100],
            ['id' => 3, 'name' => 'Outside Dhaka (ঢাকার বাইরে সারা বাংলাদেশ)', 'amount' => 120],
        ];
        foreach ($shipping as $row) {
            ShippingCharge::firstOrCreate(['id' => $row['id']], $row + ['status' => 1]);
        }

        // ---- Categories -------------------------------------------------------
        $electronics = Category::firstOrCreate(
            ['slug' => 'electronics'],
            [
                'name' => 'Electronics & Gadgets',
                'image' => 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=500&auto=format&fit=crop&q=80',
                'icon' => '⚡',
                'front_view' => 1,
                'status' => 1,
            ]
        );

        $fashion = Category::firstOrCreate(
            ['slug' => 'fashion'],
            [
                'name' => 'Men & Women Fashion',
                'image' => 'https://images.unsplash.com/photo-1445205170230-053b83016050?w=500&auto=format&fit=crop&q=80',
                'icon' => '👕',
                'front_view' => 1,
                'status' => 1,
            ]
        );

        // ---- Products ----------------------------------------------------------
        $physical = Product::firstOrCreate(
            ['slug' => 'smart-amoled-hd-smartwatch-demo'],
            [
                'product_type'   => 'physical',
                'name'           => 'Smart AMOLED HD Bluetooth Calling Smartwatch',
                'category_id'    => $electronics->id,
                'product_code'   => 'P1001',
                'purchase_price' => 1600,
                'old_price'      => 3800,
                'new_price'      => 2490,
                'stock'          => 100,
                'is_digital'     => 0,
                'free_delivery'  => 1,
                'pro_unit'       => 'PCS',
                'description'    => '<p>১.৭৮ ইঞ্চি AMOLED ডিসপ্লে, হার্ট রেট ও SpO2 ট্র্যাকার, ৫০+ স্পোর্টস মোড।</p>',
                'topsale'        => 1,
                'flashsale'      => 1,
                'feature_product'=> 1,
                'ratting'        => 4.9,
                'status'         => 1,
            ]
        );

        Productimage::firstOrCreate(
            ['product_id' => $physical->id],
            ['image' => 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=600&auto=format&fit=crop&q=80']
        );

        $digital = Product::firstOrCreate(
            ['slug' => 'canva-pro-owner-account-demo'],
            [
                'product_type'   => 'digital',
                'name'           => 'Canva Pro Lifetime Owner Account',
                'category_id'    => $electronics->id,
                'product_code'   => 'P1002',
                'purchase_price' => 300,
                'old_price'      => 1500,
                'new_price'      => 490,
                'stock'          => 50,
                'is_digital'     => 1,
                'free_delivery'  => 1,
                'digital_file'   => 'demo/activation-guide.pdf',
                'download_limit' => 5,
                'download_expire_days' => 365,
                'pro_unit'       => 'Account',
                'description'    => '<p>নিজের জিমেইলে Canva Pro Owner Account — ৫০০ মেম্বার পর্যন্ত।</p>',
                'topsale'        => 1,
                'flashsale'      => 1,
                'feature_product'=> 1,
                'ratting'        => 5.0,
                'status'         => 1,
            ]
        );

        Productimage::firstOrCreate(
            ['product_id' => $digital->id],
            ['image' => 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=600&auto=format&fit=crop&q=80']
        );

        $shirt = Product::firstOrCreate(
            ['slug' => 'premium-cotton-casual-shirt-demo'],
            [
                'product_type'   => 'physical',
                'name'           => 'Premium 100% Cotton Full Sleeve Casual Shirt',
                'category_id'    => $fashion->id,
                'product_code'   => 'P1003',
                'purchase_price' => 700,
                'old_price'      => 1900,
                'new_price'      => 1300,
                'stock'          => 80,
                'is_digital'     => 0,
                'free_delivery'  => 0,
                'pro_unit'       => 'PCS',
                'description'    => '<p>১০০% প্রিমিয়াম কম্বেড কটন, স্লিম ফিট ক্যাজুয়াল শার্ট।</p>',
                'topsale'        => 1,
                'flashsale'      => 0,
                'feature_product'=> 1,
                'ratting'        => 4.8,
                'status'         => 1,
            ]
        );

        Productimage::firstOrCreate(
            ['product_id' => $shirt->id],
            ['image' => 'https://images.unsplash.com/photo-1596755094514-f87e34085b2c?w=600&auto=format&fit=crop&q=80']
        );

        // ---- Banners ------------------------------------------------------------
        Banner::firstOrCreate(
            ['id' => 1],
            [
                'category_id' => 1, // hero slider position
                'image'       => 'https://images.unsplash.com/photo-1526738549149-8e07eca6c147?w=1400&auto=format&fit=crop&q=80',
                'link'        => '/#products',
                'status'      => 1,
            ]
        );

        // ---- Coupons ------------------------------------------------------------
        Coupon::firstOrCreate(
            ['code' => 'SAVE100'],
            [
                'type'         => 'fixed',
                'value'        => 100,
                'min_purchase' => 1200,
                'valid_from'   => '2026-01-01',
                'valid_to'     => '2027-12-31',
                'status'       => 1,
            ]
        );
    }
}
