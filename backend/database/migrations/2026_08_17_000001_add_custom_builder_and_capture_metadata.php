<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('campaigns')) {
            Schema::table('campaigns', function (Blueprint $table) {
                if (!Schema::hasColumn('campaigns', 'custom_html_draft')) {
                    $table->longText('custom_html_draft')->nullable();
                }
                if (!Schema::hasColumn('campaigns', 'custom_css_draft')) {
                    $table->longText('custom_css_draft')->nullable();
                }
                if (!Schema::hasColumn('campaigns', 'custom_js_draft')) {
                    $table->longText('custom_js_draft')->nullable();
                }
                if (!Schema::hasColumn('campaigns', 'custom_html')) {
                    $table->longText('custom_html')->nullable();
                }
                if (!Schema::hasColumn('campaigns', 'custom_css')) {
                    $table->longText('custom_css')->nullable();
                }
                if (!Schema::hasColumn('campaigns', 'custom_js')) {
                    $table->longText('custom_js')->nullable();
                }
                if (!Schema::hasColumn('campaigns', 'custom_page_published_at')) {
                    $table->timestamp('custom_page_published_at')->nullable();
                }
                if (!Schema::hasColumn('campaigns', 'is_published')) {
                    // Existing live campaigns remain live after this non-destructive upgrade.
                    $table->boolean('is_published')->default(true)->index();
                }
                if (!Schema::hasColumn('campaigns', 'published_at')) {
                    $table->timestamp('published_at')->nullable();
                }
            });

            if (Schema::hasColumn('campaigns', 'published_at') && Schema::hasColumn('campaigns', 'is_published')) {
                DB::table('campaigns')
                    ->where('is_published', true)
                    ->whereNull('published_at')
                    ->update(['published_at' => now()]);
            }
        }

        if (!Schema::hasTable('incomplete_orders')) {
            // Some legacy installations created this table outside the migrations bundled
            // with the repository. Create a complete compatible table on fresh installs.
            Schema::create('incomplete_orders', function (Blueprint $table) {
                $table->id();
                $table->string('name')->nullable();
                $table->string('phone', 55)->nullable()->index();
                $table->text('address')->nullable();
                $table->json('items')->nullable();
                $table->text('product_image')->nullable();
                $table->text('product_link')->nullable();
                $table->decimal('total_amount', 12, 2)->default(0);
                $table->string('recovery_status', 20)->default('pending')->index();
                $table->text('recovery_note')->nullable();
                $table->timestamp('contacted_at')->nullable();
                $table->unsignedBigInteger('recovered_order_id')->nullable();
                $table->unsignedBigInteger('campaign_id')->nullable()->index();
                $table->string('source', 100)->nullable();
                $table->string('device_type', 30)->nullable();
                $table->string('device_name', 120)->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->timestamp('checkout_started_at')->nullable();
                $table->timestamp('last_activity_at')->nullable();
                $table->timestamps();
            });

            return;
        }

        Schema::table('incomplete_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('incomplete_orders', 'campaign_id')) {
                $table->unsignedBigInteger('campaign_id')->nullable()->index();
            }
            if (!Schema::hasColumn('incomplete_orders', 'source')) {
                $table->string('source', 100)->nullable();
            }
            if (!Schema::hasColumn('incomplete_orders', 'device_type')) {
                $table->string('device_type', 30)->nullable();
            }
            if (!Schema::hasColumn('incomplete_orders', 'device_name')) {
                $table->string('device_name', 120)->nullable();
            }
            if (!Schema::hasColumn('incomplete_orders', 'ip_address')) {
                $table->string('ip_address', 45)->nullable();
            }
            if (!Schema::hasColumn('incomplete_orders', 'user_agent')) {
                $table->text('user_agent')->nullable();
            }
            if (!Schema::hasColumn('incomplete_orders', 'checkout_started_at')) {
                $table->timestamp('checkout_started_at')->nullable();
            }
            if (!Schema::hasColumn('incomplete_orders', 'last_activity_at')) {
                $table->timestamp('last_activity_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        // Intentionally preserve landing-page drafts, publication history, and captured leads.
        // This migration supports pre-existing legacy columns/tables, so dropping them here
        // could destroy data that was not created by this migration.
    }
};
