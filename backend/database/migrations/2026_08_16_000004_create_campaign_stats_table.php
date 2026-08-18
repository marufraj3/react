<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * প্রতি ক্যাম্পেইনের দৈনিক পারফরম্যান্স।
 *
 * প্রতিটি ভিজিটের জন্য আলাদা রো না রেখে দিন-ভিত্তিক কাউন্টার রাখা হচ্ছে —
 * ফেসবুক অ্যাডের ট্রাফিকে রো সংখ্যা বিশাল হয়ে যেত এবং রিপোর্টের কুয়েরি
 * ধীর হয়ে যেত। এক ক্যাম্পেইন × এক দিন = এক রো।
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('campaign_stats')) {
            Schema::create('campaign_stats', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('campaign_id');
                $table->date('stat_date');
                $table->unsignedInteger('visits')->default(0);
                $table->unsignedInteger('unique_visits')->default(0);
                $table->unsignedInteger('add_to_carts')->default(0);
                $table->unsignedInteger('checkouts')->default(0);
                $table->unsignedInteger('orders')->default(0);
                $table->decimal('revenue', 14, 2)->default(0);
                $table->timestamps();

                // এক ক্যাম্পেইনের এক দিনে একটাই রো — increment গুলো এর উপর নির্ভর করে
                $table->unique(['campaign_id', 'stat_date']);
                $table->index('stat_date');
            });
        }

        // অর্ডার কোন ক্যাম্পেইন থেকে এসেছে সেটা না জানলে conversion rate বের করা যায় না
        if (Schema::hasTable('orders') && !Schema::hasColumn('orders', 'campaign_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->unsignedBigInteger('campaign_id')->nullable()->after('customer_id');
                $table->index('campaign_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_stats');

        if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'campaign_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn('campaign_id');
            });
        }
    }
};
