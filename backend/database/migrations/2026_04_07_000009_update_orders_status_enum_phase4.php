<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $driver = DB::getDriverName();
        if (in_array($driver, ['mysql', 'mariadb'])) {
            // Extend the enum with vendor lifecycle statuses (MySQL/MariaDB only)
            DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('draft','submitted','pending_approval','approved','partially_approved','rejected','processing','dispatched','in_transit','delivered','acknowledged') NOT NULL DEFAULT 'pending_approval'");
        } else {
            // No-op on SQLite and others
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();
        if (in_array($driver, ['mysql', 'mariadb'])) {
            // Revert to the previous list without vendor statuses (MySQL/MariaDB only)
            DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('draft','submitted','pending_approval','approved','partially_approved','rejected') NOT NULL DEFAULT 'pending_approval'");
        } else {
            // No-op on SQLite and others
        }
    }
};
