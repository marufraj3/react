<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gemini_settings', function (Blueprint $table) {
            $table->id();
            $table->string('api_key')->nullable();
            $table->string('model')->default('gemini-2.5-flash');
            $table->boolean('status')->default(true)->comment('1=active, 0=inactive');
            $table->text('system_prompt')->nullable();
            $table->decimal('temperature', 3, 2)->default(0.70);
            $table->integer('max_output_tokens')->default(2048);
            $table->string('language')->default('auto')->comment('auto,bn,en');
            $table->boolean('include_store_data')->default(true);
            $table->boolean('log_conversation')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gemini_settings');
    }
};
