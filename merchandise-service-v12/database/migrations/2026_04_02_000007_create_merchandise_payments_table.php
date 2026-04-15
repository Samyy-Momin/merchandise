<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('merchandise_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('invoice_id');
            $table->integer('amount_cents');
            $table->string('payment_method', 50);
            $table->string('reference', 100)->nullable();
            $table->timestamp('paid_at');
            $table->timestamp('created_date')->nullable();
            $table->timestamp('updated_date')->nullable();

            $table->foreign('invoice_id')->references('id')->on('merchandise_invoices')->onDelete('cascade');
            $table->index(['company_id', 'invoice_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merchandise_payments');
    }
};
