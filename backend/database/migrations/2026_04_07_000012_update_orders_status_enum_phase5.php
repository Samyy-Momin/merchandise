<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Add completed and issue_reported states
        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('draft','submitted','pending_approval','approved','partially_approved','rejected','processing','dispatched','in_transit','delivered','acknowledged','completed','issue_reported') NOT NULL DEFAULT 'pending_approval'");
    }

    public function down(): void
    {
        // Remove the added states
        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('draft','submitted','pending_approval','approved','partially_approved','rejected','processing','dispatched','in_transit','delivered','acknowledged') NOT NULL DEFAULT 'pending_approval'");
    }
};

