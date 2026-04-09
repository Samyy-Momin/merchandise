<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->string('vendor_id'); // Keycloak sub or internal id
            $table->string('tracking_number')->nullable();
            $table->string('courier_name')->nullable();
            $table->enum('status', ['processing','dispatched','in_transit','delivered'])->default('processing');
            $table->date('estimated_delivery_date')->nullable();
            $table->timestamps();
            $table->index('vendor_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};

