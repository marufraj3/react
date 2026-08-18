<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * অর্ডার বাম্প = চেকআউটে দেখানো এক-ক্লিক অ্যাড-অন অফার।
 *
 * কাস্টমার যখন অর্ডার কনফার্ম করতে যাচ্ছে ঠিক তখনই একটা ছোট, সস্তা
 * প্রোডাক্ট বিশেষ ছাড়ে অফার করা হয়। এটি AOV (গড় অর্ডার ভ্যালু) বাড়ানোর
 * সবচেয়ে সহজ উপায় — নতুন কোনো ট্রাফিক লাগে না।
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_bumps', function (Blueprint $table) {
            $table->id();

            // যে প্রোডাক্টটি অফার হিসেবে যোগ হবে
            $table->unsignedBigInteger('product_id');

            // চেকআউটে দেখানো টেক্সট (বাংলা)
            $table->string('title')->nullable();
            $table->string('subtitle')->nullable();

            // ডিসকাউন্ট: flat = টাকা, percent = শতাংশ
            $table->string('discount_type', 20)->default('flat');
            $table->decimal('discount_value', 10, 2)->default(0);

            // কার্টের সাবটোটাল এর কম হলে বাম্প দেখাবে না
            $table->decimal('min_cart_amount', 10, 2)->nullable();

            // null = সব পেজে; নাহলে নির্দিষ্ট ক্যাম্পেইনে সীমাবদ্ধ
            $table->unsignedBigInteger('campaign_id')->nullable();

            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('status')->default(1);

            // পারফরম্যান্স ট্র্যাকিং — কতবার দেখানো হলো, কতবার নেওয়া হলো
            $table->unsignedBigInteger('impressions')->default(0);
            $table->unsignedBigInteger('conversions')->default(0);

            $table->timestamps();

            $table->index(['status', 'campaign_id']);
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_bumps');
    }
};
