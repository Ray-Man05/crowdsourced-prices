<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('price_estimates', function (Blueprint $table) {
            $table->index(['product_id', 'recorded_at'], 'pe_product_recorded_at');
            $table->index(['product_id', 'city_id'], 'pe_product_city');
        });
    }

    public function down(): void
    {
        Schema::table('price_estimates', function (Blueprint $table) {
            $table->dropIndex('pe_product_recorded_at');
            $table->dropIndex('pe_product_city');
        });
    }
};
