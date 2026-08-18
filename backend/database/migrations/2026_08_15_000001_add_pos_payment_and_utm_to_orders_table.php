<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('paid_amount', 12, 2)->default(0)->after('amount');
            $table->string('payment_method', 55)->nullable()->after('paid_amount');
            $table->string('utm_source', 120)->nullable()->index()->after('payment_method');
            $table->string('utm_medium', 120)->nullable()->after('utm_source');
            $table->string('utm_campaign', 180)->nullable()->after('utm_medium');
            $table->string('utm_term', 180)->nullable()->after('utm_campaign');
            $table->string('utm_content', 180)->nullable()->after('utm_term');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['utm_source']);
            $table->dropColumn([
                'paid_amount', 'payment_method', 'utm_source', 'utm_medium',
                'utm_campaign', 'utm_term', 'utm_content'
            ]);
        });
    }
};
