<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 1. cities (country_id, name)
     *    The FK-implicit single-column country_id index supports equality lookups but
     *    cannot satisfy ORDER BY name within a country without a filesort. The composite
     *    index covers:
     *      - CitySelector / CityComparison: WHERE country_id = X ORDER BY name
     *      - PriceAggregator coverage join: JOIN cities … WHERE cities.country_id = X
     *    Since id (PK) is appended implicitly by InnoDB and name + country_id are the
     *    selected columns, most city-by-country reads become index-only scans.
     *
     * 2. price_estimates pe_city_product: (city_id, product_id) → (city_id, product_id, deleted_at)
     *    Every soft-delete Eloquent query appends WHERE deleted_at IS NULL. Without
     *    deleted_at in the index MySQL must follow each leaf pointer to the table row to
     *    evaluate the predicate. The trailing column makes it an index-only scan for the
     *    soft-delete path — consistent with pe_product_city and pe_product_recorded_at
     *    which already carry deleted_at.
     *
     *    Queries covered:
     *      - Dashboard::getCityProductIdsProperty(): WHERE city_id = X → pluck(product_id)
     *      - PriceAggregator aggregation scoped to a single (city, product) pair
     */
    public function up(): void
    {
        Schema::table('cities', function (Blueprint $table) {
            $table->index(['country_id', 'name'], 'cities_country_name');
        });

        if (DB::getDriverName() === 'mysql') {
            $existing = collect(DB::select('SHOW INDEX FROM price_estimates'))
                ->pluck('Key_name')->unique()->flip();

            $parts = [];
            if (isset($existing['pe_city_product'])) {
                $parts[] = 'DROP INDEX pe_city_product';
            }
            $parts[] = 'ADD INDEX pe_city_product (city_id, product_id, deleted_at)';

            DB::statement('ALTER TABLE price_estimates ' . implode(', ', $parts));
        } else {
            Schema::table('price_estimates', function (Blueprint $table) {
                $table->dropIndex('pe_city_product');
                $table->index(['city_id', 'product_id', 'deleted_at'], 'pe_city_product');
            });
        }
    }

    public function down(): void
    {
        Schema::table('cities', function (Blueprint $table) {
            $table->dropIndex('cities_country_name');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('
                ALTER TABLE price_estimates
                DROP  INDEX pe_city_product,
                ADD   INDEX pe_city_product (city_id, product_id)
            ');
        } else {
            Schema::table('price_estimates', function (Blueprint $table) {
                $table->dropIndex('pe_city_product');
                $table->index(['city_id', 'product_id'], 'pe_city_product');
            });
        }
    }
};
