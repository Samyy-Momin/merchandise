<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->string('approver_id'); // Keycloak sub
            $table->enum('status', ['approved', 'rejected', 'partial']);
            $table->text('comments')->nullable();
            $table->timestamps();
            $table->index('approver_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approvals');
    }
};

