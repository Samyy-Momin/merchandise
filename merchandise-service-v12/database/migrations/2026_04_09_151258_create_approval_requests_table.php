<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('entity_type', 100);
            $table->unsignedBigInteger('entity_id');
            $table->string('status', 30)->default('draft');
            $table->unsignedBigInteger('buyer_store_id')->nullable();
            $table->unsignedBigInteger('vendor_store_id')->nullable();
            $table->string('approver_role', 50)->nullable();
            $table->unsignedBigInteger('requested_by')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('rejected_by')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('decision_reason')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status']);
            $table->index(['entity_type', 'entity_id']);
            $table->index(['buyer_store_id']);
            $table->index(['vendor_store_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_requests');
    }
};
