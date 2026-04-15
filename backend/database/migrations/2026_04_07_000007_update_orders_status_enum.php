<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $driver = DB::getDriverName();
        if (in_array($driver, ['mysql', 'mariadb'])) {
            // Update orders.status to enum with the required values and default (MySQL/MariaDB only)
            DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('draft','submitted','pending_approval','approved','partially_approved','rejected') NOT NULL DEFAULT 'pending_approval'");
            // Migrate any existing 'submitted' to 'pending_approval'
            DB::statement("UPDATE orders SET status = 'pending_approval' WHERE status = 'submitted'");
        } else {
            // On SQLite and other drivers, skip the MySQL-specific ALTER.
            // Apply only the data update which is portable.
            DB::statement("UPDATE orders SET status = 'pending_approval' WHERE status = 'submitted'");
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();
        if (in_array($driver, ['mysql', 'mariadb'])) {
            // Revert back to simple VARCHAR with default 'submitted' (MySQL/MariaDB only)
            DB::statement("ALTER TABLE orders MODIFY COLUMN status VARCHAR(50) NOT NULL DEFAULT 'submitted'");
        } else {
            // No-op on SQLite and others
        }
    }
};
