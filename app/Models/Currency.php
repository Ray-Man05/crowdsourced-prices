<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class Currency extends Model
{
    protected $fillable = ['name', 'code', 'symbol'];

    /** In-memory rate cache keyed by "fromId:toId". Lives for the duration of one PHP request. */
    private static array $rateCache = [];

    public function countries(): HasMany
    {
        return $this->hasMany(Country::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function priceEstimates(): HasMany
    {
        return $this->hasMany(PriceEstimate::class);
    }

    public function exchangeRatesFrom(): HasMany
    {
        return $this->hasMany(ExchangeRate::class, 'from_currency_id');
    }

    public function exchangeRatesTo(): HasMany
    {
        return $this->hasMany(ExchangeRate::class, 'to_currency_id');
    }

    /**
     * Get the exchange rate to convert this currency to another.
     * Returns null if no direct rate exists.
     */
    public function getRateTo(Currency $target): ?float
    {
        if ($this->id === $target->id) return 1.0;

        $mapKey = "{$this->id}:{$target->id}";

        if (array_key_exists($mapKey, self::$rateCache)) {
            return self::$rateCache[$mapKey];
        }

        $cacheKey = "exchange_rate:{$this->id}:{$target->id}";

        $rate = Cache::remember($cacheKey, 86400, fn() =>
            ExchangeRate::where('from_currency_id', $this->id)
                ->where('to_currency_id', $target->id)
                ->value('rate')
        );

        return self::$rateCache[$mapKey] = $rate;
    }

    /**
     * Convert an amount from this currency to another.
     */
    public function convert(float $amount, Currency $target): ?float
    {
        $rate = $this->getRateTo($target);
        return $rate !== null ? $amount * $rate : null;
    }

    /**
     * Format an amount in this currency according to its symbol.
    */
    public function format(float $amount, int $decimals = 2): string
    {
        return $this->symbol . number_format($amount, $decimals);
    }
}