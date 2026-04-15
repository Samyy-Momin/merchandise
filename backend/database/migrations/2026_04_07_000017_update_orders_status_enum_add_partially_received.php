<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $driver = DB::getDriverName();
        if (in_array($driver, ['mysql', 'mariadb'])) {
            DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('draft','submitted','pending_approval','approved','partially_approved','rejected','processing','dispatched','in_transit','delivered','acknowledged','partially_received','completed','issue_reported') NOT NULL DEFAULT 'pending_approval'");
        } else {
            // No-op on SQLite and others
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();
        if (in_array($driver, ['mysql', 'mariadb'])) {
            DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('draft','submitted','pending_approval','approved','partially_approved','rejected','processing','dispatched','in_transit','delivered','acknowledged','completed','issue_reported') NOT NULL DEFAULT 'pending_approval'");
        } else {
            // No-op on SQLite and others
        }
    }
};
