<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('digital_downloads', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('order_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedInteger('customer_id');
            $table->string('token', 100);
            $table->string('file_path', 255);
            $table->integer('remaining_downloads')->default(0);
            $table->dateTime('expires_at')->nullable();
            $table->timestamps();

            $table->index(['customer_id']);
            $table->index(['order_id']);
            $table->index(['token']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('digital_downloads');
    }
};
