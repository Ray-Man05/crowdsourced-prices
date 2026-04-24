<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Currency extends Model
{
    protected $fillable = ['name', 'code', 'symbol'];

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
        if ($this->id === $target->id) {
            return 1.0;
        }

        $rate = ExchangeRate::where('from_currency_id', $this->id)
            ->where('to_currency_id', $target->id)
            ->first();

        return $rate?->rate;
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