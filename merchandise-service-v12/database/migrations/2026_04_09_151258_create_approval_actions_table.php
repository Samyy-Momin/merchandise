<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('approval_request_id')->constrained('approval_requests')->cascadeOnDelete();
            $table->string('action', 30);
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('actor_role', 50)->nullable();
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->index(['approval_request_id', 'action']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_actions');
    }
};
