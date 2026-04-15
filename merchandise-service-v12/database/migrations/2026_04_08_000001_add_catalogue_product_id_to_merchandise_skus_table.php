<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add a nullable FK reference to the catalogue-service products table.
     *
     * catalogue_product_id stores the pk_product_code from catalogue-service.
     * It is nullable so that non-catalogue merchandise (branded merch, custom
     * items) can still be created without a catalogue entry. No DB-level FK
     * constraint is applied because catalogue-service lives in a separate
     * database — application-level validation is responsible for integrity.
     */
    public function up(): void
    {
        if (Schema::hasColumn('merchandise_skus', 'catalogue_product_id')) {
            return;
        }

        Schema::table('merchandise_skus', function (Blueprint $table) {
            $table->unsignedBigInteger('catalogue_product_id')
                ->nullable()
                ->after('company_id')
                ->comment('pk_product_code from catalogue-service products table; nullable for standalone SKUs');

            $table->index(['company_id', 'catalogue_product_id'], 'idx_merch_skus_catalogue_product');
        });
    }

    public function down(): void
    {
        Schema::table('merchandise_skus', function (Blueprint $table) {
            $table->dropIndex('idx_merch_skus_catalogue_product');
            $table->dropColumn('catalogue_product_id');
        });
    }
};
