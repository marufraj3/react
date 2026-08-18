<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * অসম্পূর্ণ অর্ডার (abandoned cart) রিকভারি ট্র্যাকিং।
 *
 * আগে লিস্টে শুধু accept/delete ছিল — কাকে ফলোআপ করা হয়েছে, কে কিনেছে,
 * কে না করে দিয়েছে সেটার কোনো হিসাব থাকত না। ফলে একই কাস্টমারকে বারবার
 * কল করা হতো অথবা কেউ বাদ পড়ে যেত।
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('incomplete_orders')) {
            return;
        }

        Schema::table('incomplete_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('incomplete_orders', 'recovery_status')) {
                // pending = নতুন, contacted = যোগাযোগ করা হয়েছে,
                // recovered = অর্ডারে রূপান্তর হয়েছে, lost = কাস্টমার রাজি হয়নি
                $table->string('recovery_status', 20)->default('pending')->after('total_amount');
            }

            if (!Schema::hasColumn('incomplete_orders', 'recovery_note')) {
                $table->text('recovery_note')->nullable()->after('recovery_status');
            }

            if (!Schema::hasColumn('incomplete_orders', 'contacted_at')) {
                $table->timestamp('contacted_at')->nullable()->after('recovery_note');
            }

            if (!Schema::hasColumn('incomplete_orders', 'recovered_order_id')) {
                $table->unsignedBigInteger('recovered_order_id')->nullable()->after('contacted_at');
            }
        });

        Schema::table('incomplete_orders', function (Blueprint $table) {
            $table->index('recovery_status');
            $table->index('phone');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('incomplete_orders')) {
            return;
        }

        Schema::table('incomplete_orders', function (Blueprint $table) {
            foreach (['recovery_status', 'recovery_note', 'contacted_at', 'recovered_order_id'] as $column) {
                if (Schema::hasColumn('incomplete_orders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
