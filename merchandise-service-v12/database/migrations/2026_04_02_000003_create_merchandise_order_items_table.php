<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('merchandise_order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('sku_id');
            $table->string('sku_code');
            $table->string('sku_name');
            $table->integer('requested_quantity');
            $table->integer('approved_quantity')->nullable();
            $table->integer('unit_price_cents');
            $table->integer('line_total_cents')->default(0);
            $table->timestamp('created_date')->nullable();
            $table->timestamp('updated_date')->nullable();

            $table->foreign('order_id')->references('id')->on('merchandise_orders')->onDelete('cascade');
            $table->foreign('sku_id')->references('id')->on('merchandise_skus')->onDelete('restrict');
            $table->index(['company_id', 'order_id']);
            $table->index(['order_id']);
            $table->index(['sku_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merchandise_order_items');
    }
};
