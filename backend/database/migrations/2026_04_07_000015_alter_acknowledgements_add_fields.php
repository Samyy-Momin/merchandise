<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('acknowledgements', function (Blueprint $table) {
            $table->string('employee_code')->nullable();
            $table->string('receiver_name')->nullable();
            $table->string('branch_manager_name')->nullable();
            $table->text('remarks')->nullable();
            $table->unsignedTinyInteger('rating')->nullable(); // 1-5
        });
    }

    public function down(): void
    {
        Schema::table('acknowledgements', function (Blueprint $table) {
            $table->dropColumn(['employee_code','receiver_name','branch_manager_name','remarks','rating']);
        });
    }
};

