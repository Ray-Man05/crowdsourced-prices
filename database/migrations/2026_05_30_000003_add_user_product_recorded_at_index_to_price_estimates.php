<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add a (user_id, product_id, recorded_at) composite index to price_estimates.
     *
     * Covered queries:
     *   - PriceEstimate::isOnCooldown()   — WHERE user_id=X AND product_id=Y AND recorded_at>=Z  (withTrashed)
     *   - PriceEstimate::cooldownEndsAt() — WHERE user_id=X AND product_id=Y ORDER BY recorded_at DESC (withTrashed)
     *   - PriceAggregator::bulkUserStatuses() — WHERE user_id=X AND product_id IN [...] AND recorded_at>=Z (withTrashed)
     *
     * All three queries use withTrashed(), so deleted_at is intentionally omitted — including it
     * would add overhead without benefit for queries that never filter on deleted_at.
     *
     * The existing single-column price_estimates_user_id_foreign index becomes redundant for all
     * these access patterns, but it is retained here as the FK support index.
     */
    public function up(): void
    {
        Schema::table('price_estimates', function (Blueprint $table) {
            $table->index(['user_id', 'product_id', 'recorded_at'], 'pe_user_product_recorded_at');
        });
    }

    public function down(): void
    {
        Schema::table('price_estimates', function (Blueprint $table) {
            $table->dropIndex('pe_user_product_recorded_at');
        });
    }
};
