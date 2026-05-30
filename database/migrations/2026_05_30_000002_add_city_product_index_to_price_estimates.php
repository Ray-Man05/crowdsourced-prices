<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add a (city_id, product_id) composite index to price_estimates.
     *
     * The FK-implicit single-column index on city_id is insufficient for queries
     * that filter or group on (city_id, product_id). Affected queries:
     *   - Dashboard::getCityProductIdsProperty(): WHERE city_id = X  → pluck(product_id)
     *   - Dashboard::mount() after the Product::pluck('id') removal
     *
     * The composite index lets MySQL satisfy city-only filters via the leading column,
     * and city+product filters via both columns — covering getCityProductIdsProperty()
     * as an index-only scan without touching the table.
     */
    public function up(): void
    {
        Schema::table('price_estimates', function (Blueprint $table) {
            $table->index(['city_id', 'product_id'], 'pe_city_product');
        });
    }

    public function down(): void
    {
        Schema::table('price_estimates', function (Blueprint $table) {
            $table->dropIndex('pe_city_product');
        });
    }
};
