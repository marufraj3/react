<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * অর্ডার রেস্ট্রিকশন v2 — হোয়াইটলিস্ট + অন/অফ সুইচ।
 *
 * পুরনো সংস্করণে IP দিয়েও গণনা হতো, যা CGNAT/শেয়ার্ড ওয়াইফাইয়ে নির্দোষ
 * কাস্টমারকে আটকে দিত। v2 শুধু ফোন নম্বর দেখে, এবং রিসেলার/পাইকারি
 * ক্রেতাদের নম্বর হোয়াইটলিস্টে রাখলে তাদের ওপর কোনো সীমা প্রযোজ্য হয় না।
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('order_restriction_whitelists')) {
            Schema::create('order_restriction_whitelists', function (Blueprint $table) {
                $table->id();
                $table->string('phone', 30)->unique();
                $table->string('name', 120)->nullable();
                $table->string('note', 255)->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('general_settings') && !Schema::hasColumn('general_settings', 'order_limit_enabled')) {
            Schema::table('general_settings', function (Blueprint $table) {
                // ডিফল্ট বন্ধ — অ্যাডমিন নিজে চালু না করা পর্যন্ত কোনো অর্ডার আটকাবে না
                $table->boolean('order_limit_enabled')->default(false)->after('order_limit_qty');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('order_restriction_whitelists');

        if (Schema::hasTable('general_settings') && Schema::hasColumn('general_settings', 'order_limit_enabled')) {
            Schema::table('general_settings', function (Blueprint $table) {
                $table->dropColumn('order_limit_enabled');
            });
        }
    }
};
