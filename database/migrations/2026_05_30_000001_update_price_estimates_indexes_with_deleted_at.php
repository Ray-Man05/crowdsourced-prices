<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Append deleted_at as a trailing column to both composite indexes on price_estimates.
     *
     * Without deleted_at, Eloquent's automatic WHERE deleted_at IS NULL predicate is evaluated
     * as a post-scan filter after following each matching index entry to the table row. With
     * deleted_at in the index MySQL can read the value from the leaf page and skip deleted rows
     * without touching the table.
     *
     * withTrashed() queries are unaffected: they omit the deleted_at predicate entirely, so
     * MySQL scans the full (product_id, recorded_at|city_id) range as before.
     *
     * MySQL path: both drops and adds are issued in a single ALTER TABLE statement so MySQL
     * evaluates FK constraints against the final state rather than each intermediate step.
     * The SHOW INDEX check makes the migration idempotent (handles partial prior runs).
     *
     * SQLite path: Blueprint drop+create used for compatibility with the in-memory test DB.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            $existing = collect(DB::select('SHOW INDEX FROM price_estimates'))
                ->pluck('Key_name')
                ->unique()
                ->flip();

            $parts = [];
            if (isset($existing['pe_product_recorded_at'])) {
                $parts[] = 'DROP INDEX pe_product_recorded_at';
            }
            $parts[] = 'ADD INDEX pe_product_recorded_at (product_id, recorded_at, deleted_at)';

            if (isset($existing['pe_product_city'])) {
                $parts[] = 'DROP INDEX pe_product_city';
            }
            $parts[] = 'ADD INDEX pe_product_city (product_id, city_id, deleted_at)';

            DB::statement('ALTER TABLE price_estimates '.implode(', ', $parts));
        } else {
            Schema::table('price_estimates', function (Blueprint $table) {
                $table->dropIndex('pe_product_recorded_at');
                $table->dropIndex('pe_product_city');
                $table->index(['product_id', 'recorded_at', 'deleted_at'], 'pe_product_recorded_at');
                $table->index(['product_id', 'city_id',     'deleted_at'], 'pe_product_city');
            });
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('
                ALTER TABLE price_estimates
                DROP  INDEX pe_product_recorded_at,
                ADD   INDEX pe_product_recorded_at (product_id, recorded_at),
                DROP  INDEX pe_product_city,
                ADD   INDEX pe_product_city        (product_id, city_id)
            ');
        } else {
            Schema::table('price_estimates', function (Blueprint $table) {
                $table->dropIndex('pe_product_recorded_at');
                $table->dropIndex('pe_product_city');
                $table->index(['product_id', 'recorded_at'], 'pe_product_recorded_at');
                $table->index(['product_id', 'city_id'], 'pe_product_city');
            });
        }
    }
};
