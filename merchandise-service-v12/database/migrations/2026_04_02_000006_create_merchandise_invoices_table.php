<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('merchandise_invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('invoice_number', 40)->unique();
            $table->unsignedBigInteger('order_id')->unique();
            $table->unsignedBigInteger('customer_id');
            $table->string('status', 20)->default('draft');
            $table->integer('subtotal_cents');
            $table->integer('tax_cents')->default(0);
            $table->integer('discount_cents')->default(0);
            $table->integer('total_cents');
            $table->integer('amount_paid_cents')->default(0);
            $table->date('due_date');
            $table->timestamp('created_date')->nullable();
            $table->timestamp('updated_date')->nullable();

            $table->foreign('order_id')->references('id')->on('merchandise_orders')->onDelete('cascade');
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'customer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merchandise_invoices');
    }
};
