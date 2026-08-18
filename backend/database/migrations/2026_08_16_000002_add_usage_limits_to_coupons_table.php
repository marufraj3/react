<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * কুপনে ব্যবহারের সীমা যোগ করা হচ্ছে।
 *
 * এতদিন coupons টেবিলে কোনো usage limit ছিল না — একটা কোড ফাঁস হলে
 * অসীমবার ব্যবহার করা যেত। এখন মোট সীমা ও প্রতি-ফোন সীমা দুটোই রাখা হলো।
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            if (!Schema::hasColumn('coupons', 'usage_limit')) {
                // null = অসীম (আগের আচরণ অক্ষত থাকে)
                $table->unsignedInteger('usage_limit')->nullable()->after('min_purchase');
            }

            if (!Schema::hasColumn('coupons', 'usage_limit_per_customer')) {
                $table->unsignedInteger('usage_limit_per_customer')->nullable()->after('usage_limit');
            }

            if (!Schema::hasColumn('coupons', 'used_count')) {
                $table->unsignedInteger('used_count')->default(0)->after('usage_limit_per_customer');
            }
        });

        // কে কখন কোন কুপন ব্যবহার করল তার হিসাব — প্রতি-কাস্টমার সীমা এখান থেকেই গোনা হয়।
        if (!Schema::hasTable('coupon_usages')) {
            Schema::create('coupon_usages', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('coupon_id');
                $table->string('code', 191);
                $table->string('phone', 55)->nullable();
                $table->unsignedBigInteger('order_id')->nullable();
                $table->unsignedBigInteger('customer_id')->nullable();
                $table->decimal('discount', 10, 2)->default(0);
                $table->timestamps();

                $table->index(['coupon_id', 'phone']);
                $table->index('order_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('coupon_usages');

        Schema::table('coupons', function (Blueprint $table) {
            foreach (['usage_limit', 'usage_limit_per_customer', 'used_count'] as $column) {
                if (Schema::hasColumn('coupons', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
