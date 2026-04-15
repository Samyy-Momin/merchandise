<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Only run on MySQL; skip on SQLite/Postgres to avoid driver-specific SQL errors
        $driver = DB::getDriverName();
        if ($driver !== 'mysql') {
            return;
        }

        // Read existing indexes to avoid duplicate index errors
        $existing = collect(DB::select('SHOW INDEX FROM products'))
            ->pluck('Key_name')
            ->toArray();

        Schema::table('products', function (Blueprint $table) use ($existing) {
            if (!in_array('products_category_id_index', $existing, true) && Schema::hasColumn('products', 'category_id')) {
                $table->index('category_id', 'products_category_id_index');
            }
            if (!in_array('products_price_index', $existing, true) && Schema::hasColumn('products', 'price')) {
                $table->index('price', 'products_price_index');
            }
            if (!in_array('products_name_index', $existing, true) && Schema::hasColumn('products', 'name')) {
                $table->index('name', 'products_name_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            try { $table->dropIndex('products_category_id_index'); } catch (\Throwable $e) {}
            try { $table->dropIndex('products_price_index'); } catch (\Throwable $e) {}
            try { $table->dropIndex('products_name_index'); } catch (\Throwable $e) {}
        });
    }
};
