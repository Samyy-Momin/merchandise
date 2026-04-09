<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Extend the enum with vendor lifecycle statuses
        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('draft','submitted','pending_approval','approved','partially_approved','rejected','processing','dispatched','in_transit','delivered','acknowledged') NOT NULL DEFAULT 'pending_approval'");
    }

    public function down(): void
    {
        // Revert to the previous list without vendor statuses
        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('draft','submitted','pending_approval','approved','partially_approved','rejected') NOT NULL DEFAULT 'pending_approval'");
    }
};

