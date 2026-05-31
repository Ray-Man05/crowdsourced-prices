<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class ExchangeRate extends Model
{
    protected $fillable = ['rate', 'from_currency_id', 'to_currency_id', 'fetched_at'];

    protected $casts = [
        'rate' => 'float',
        'fetched_at' => 'datetime',
    ];

    public function fromCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'from_currency_id');
    }

    public function toCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'to_currency_id');
    }

    /**
     * Update or create the rate between two currencies.
     * Also automatically creates the inverse rate.
     */
    public static function setRate(
        Currency $from,
        Currency $to,
        float $rate,
    ): void {
        $now = Carbon::now();

        self::updateOrCreate(
            ['from_currency_id' => $from->id, 'to_currency_id' => $to->id],
            ['rate' => $rate, 'fetched_at' => $now],
        );

        self::updateOrCreate(
            ['from_currency_id' => $to->id, 'to_currency_id' => $from->id],
            ['rate' => 1.0 / $rate, 'fetched_at' => $now],
        );

        Cache::forget("exchange_rate:{$from->id}:{$to->id}");
        Cache::forget("exchange_rate:{$to->id}:{$from->id}");
    }

    /**
     * Build the pair of raw records (forward + inverse) for a given rate,
     * without touching the database or the cache.
     *
     * This mirrors exactly what setRate() writes, but returns plain arrays
     * suitable for a bulk upsert() call. Use this when inserting many rates
     * at once; use setRate() for individual updates where cache invalidation matters.
     *
     * @return array{array, array} Always exactly two records: [forward, inverse]
     */
    public static function buildRecordPair(
        Currency $from,
        Currency $to,
        float $rate,
    ): array {
        $now = Carbon::now();

        return [
            ['from_currency_id' => $from->id, 'to_currency_id' => $to->id, 'rate' => $rate,           'fetched_at' => $now],
            ['from_currency_id' => $to->id,   'to_currency_id' => $from->id, 'rate' => 1.0 / $rate,   'fetched_at' => $now],
        ];
    }
}
