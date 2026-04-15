<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('merchandise_orders', function (Blueprint $table) {
            $table->string('order_kind', 30)->default('standard')->after('customer_id');
            $table->unsignedBigInteger('buyer_store_id')->nullable()->after('order_kind');
            $table->unsignedBigInteger('vendor_store_id')->nullable()->after('buyer_store_id');
            $table->unsignedBigInteger('fulfillment_store_id')->nullable()->after('vendor_store_id');
            $table->unsignedBigInteger('approval_request_id')->nullable()->after('rejected_reason');

            $table->index(['company_id', 'order_kind']);
            $table->index(['buyer_store_id']);
            $table->index(['vendor_store_id']);
            $table->index(['approval_request_id']);
        });
    }

    public function down(): void
    {
        Schema::table('merchandise_orders', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'order_kind']);
            $table->dropIndex(['buyer_store_id']);
            $table->dropIndex(['vendor_store_id']);
            $table->dropIndex(['approval_request_id']);

            $table->dropColumn([
                'order_kind',
                'buyer_store_id',
                'vendor_store_id',
                'fulfillment_store_id',
                'approval_request_id',
            ]);
        });
    }
};
