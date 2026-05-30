<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add two indexes to address moderate-scale query degradation.
     *
     * 1. user_baskets (user_id, type)
     *    Every basket query filters by both columns: WHERE user_id=X AND type='saved'.
     *    The FK-implicit single-column user_id index is sufficient only for user_id filters;
     *    adding type as a second column lets MySQL satisfy the combined predicate in one seek.
     *    Callers: Dashboard::getBasketsProperty(), CityComparison::getBasketsProperty(),
     *             Dashboard::editBasket(), and every other UserBasket query scoped to type.
     *
     * 2. price_estimates (user_id, recorded_at)
     *    Dashboard::getActivityMapProperty() filters by (user_id, recorded_at) over a
     *    364-day window and groups by date. Without this index MySQL scans all estimates for
     *    the user and post-filters on recorded_at.
     */
    public function up(): void
    {
        Schema::table('user_baskets', function (Blueprint $table) {
            $table->index(['user_id', 'type'], 'ub_user_type');
        });

        Schema::table('price_estimates', function (Blueprint $table) {
            $table->index(['user_id', 'recorded_at'], 'pe_user_recorded_at');
        });
    }

    public function down(): void
    {
        Schema::table('user_baskets', function (Blueprint $table) {
            $table->dropIndex('ub_user_type');
        });

        Schema::table('price_estimates', function (Blueprint $table) {
            $table->dropIndex('pe_user_recorded_at');
        });
    }
};
