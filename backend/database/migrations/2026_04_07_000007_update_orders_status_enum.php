<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Update orders.status to enum with the required values and default
        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('draft','submitted','pending_approval','approved','partially_approved','rejected') NOT NULL DEFAULT 'pending_approval'");
        // Migrate any existing 'submitted' to 'pending_approval'
        DB::statement("UPDATE orders SET status = 'pending_approval' WHERE status = 'submitted'");
    }

    public function down(): void
    {
        // Revert back to simple VARCHAR with default 'submitted' to avoid dependency on doctrine/dbal
        DB::statement("ALTER TABLE orders MODIFY COLUMN status VARCHAR(50) NOT NULL DEFAULT 'submitted'");
    }
};

