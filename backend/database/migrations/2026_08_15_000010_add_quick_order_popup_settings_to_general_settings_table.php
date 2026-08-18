<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddQuickOrderPopupSettingsToGeneralSettingsTable extends Migration
{
    /**
     * Quick-Order পপআপ (Order Now / Cart বাটনে পপআপ) সেটিংস।
     * Admin Panel → General Settings থেকে সব নিয়ন্ত্রণ করা যাবে।
     *
     * Run: php artisan migrate
     */
    public function up()
    {
        Schema::table('general_settings', function (Blueprint $table) {
            $table->boolean('quick_order_popup_enabled')->default(1)->nullable()->after('news_ticker_enabled');
            $table->string('quick_order_popup_title')->nullable()->default('🛒 দ্রুত অর্ডার করুন')->after('quick_order_popup_enabled');
            $table->string('quick_order_confirm_text')->nullable()->default('অর্ডার কনফার্ম করুন →')->after('quick_order_popup_title');
            $table->string('quick_order_cart_text')->nullable()->default('কার্টে রাখুন')->after('quick_order_confirm_text');
            $table->string('quick_order_cart_toast')->nullable()->default('কার্টে যোগ হয়েছে ✔')->after('quick_order_cart_text');
        });
    }

    public function down()
    {
        Schema::table('general_settings', function (Blueprint $table) {
            $table->dropColumn([
                'quick_order_popup_enabled',
                'quick_order_popup_title',
                'quick_order_confirm_text',
                'quick_order_cart_text',
                'quick_order_cart_toast',
            ]);
        });
    }
}
