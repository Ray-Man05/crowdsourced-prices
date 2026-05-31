<?php

namespace App\Models;

use App\Services\PriceAggregator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class City extends Model
{
    protected $fillable = ['name', 'country_id', 'lat', 'lng'];

    protected $casts = [
        'lat' => 'float',
        'lng' => 'float',
    ];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get all price estimates submitted in this city.
     */
    public function priceEstimates(): HasMany
    {
        return $this->hasMany(PriceEstimate::class);
    }

    /**
     * Get the average price for a product in this city.
     *
     * @param  Currency  $targetCurrency  Currency to express the result in.
     * @param  int  $days  Only consider estimates from the past N days. 0 = all time.
     * @return float|null Null if no convertible estimates exist.
     */
    public function averagePrice(
        Product $product,
        Currency $targetCurrency,
        int $days = 30,
    ): ?float {
        return app(PriceAggregator::class)->cityAverage($product, $this, $targetCurrency, $days);
    }

    /**
     * Return all cities keyed by country, for use in grouped UI selects and listings.
     * Returns a Collection of Countries, each with their cities eager-loaded.
     */
    public static function groupedByCountry(): Collection
    {
        return Country::with('cities')->get();
    }
}
