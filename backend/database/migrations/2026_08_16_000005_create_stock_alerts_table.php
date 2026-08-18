<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * স্টক অ্যালার্ট — কোন প্রোডাক্ট/ভ্যারিয়েন্ট ফুরিয়ে গেছে বা প্রায় শেষ।
 *
 * এতদিন স্টক শূন্য হলে অ্যাডমিন কিছুই জানত না; কাস্টমার অর্ডার করার পরে
 * ফোন করে বলতে হতো "স্টক নেই"। এখন অর্ডার হওয়ার সাথে সাথেই অ্যালার্ট
 * তৈরি হয় এবং শূন্য স্টকের ভ্যারিয়েন্ট চেকআউটে আটকে যায়।
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('stock_alerts')) {
            Schema::create('stock_alerts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('product_id');
                $table->unsignedBigInteger('variant_price_id')->nullable();
                $table->string('product_name', 255)->nullable();
                $table->string('variant_label', 255)->nullable();
                $table->string('type', 20)->default('out_of_stock'); // out_of_stock | low_stock
                $table->integer('stock_left')->default(0);
                $table->boolean('is_read')->default(false);
                $table->timestamp('resolved_at')->nullable();
                $table->timestamps();

                // একই ভ্যারিয়েন্টের জন্য বারবার অ্যালার্ট তৈরি না করতে
                // (খোলা অ্যালার্ট আছে কিনা এই ইনডেক্স দিয়ে দেখা হয়)
                $table->index(['product_id', 'variant_price_id', 'resolved_at'], 'stock_alerts_lookup_index');
                $table->index('is_read');
            });
        }

        // কত স্টক বাকি থাকলে "প্রায় শেষ" ধরা হবে — প্রোডাক্ট ভেদে আলাদা হতে পারে
        if (Schema::hasTable('products') && !Schema::hasColumn('products', 'low_stock_threshold')) {
            Schema::table('products', function (Blueprint $table) {
                $table->unsignedInteger('low_stock_threshold')->nullable()->after('stock');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_alerts');

        if (Schema::hasTable('products') && Schema::hasColumn('products', 'low_stock_threshold')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('low_stock_threshold');
            });
        }
    }
};
