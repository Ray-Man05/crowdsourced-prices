<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class ExchangeRate extends Model
{
    protected $fillable = ['rate', 'from_currency_id', 'to_currency_id', 'fetched_at'];

    protected $casts = [
        'rate'       => 'float',
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
     * also automatically creates the inverse rate.
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
    }
}