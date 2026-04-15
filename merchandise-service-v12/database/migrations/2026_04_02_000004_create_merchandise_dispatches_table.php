<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('merchandise_dispatches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('order_id')->unique();
            $table->unsignedBigInteger('dispatched_by');
            $table->string('courier')->nullable();
            $table->string('tracking_number')->nullable();
            $table->timestamp('dispatched_at');
            $table->timestamp('estimated_delivery_at')->nullable();
            $table->timestamp('created_date')->nullable();
            $table->timestamp('updated_date')->nullable();

            $table->foreign('order_id')->references('id')->on('merchandise_orders')->onDelete('cascade');
            $table->index(['company_id']);
            $table->index(['company_id', 'dispatched_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merchandise_dispatches');
    }
};
