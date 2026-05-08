<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use App\Traits\HasTranslations;
use App\Services\PriceAggregator;

class Product extends Model
{
    use HasTranslations;

    protected $fillable = ['name', 'description', 'category_id', 'unit_id'];
    protected $casts = [
        'name'        => 'array',
        'description' => 'array',
    ];

    public function translatableAttributes(): array
    {
        return ['name', 'description'];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function priceEstimates(): HasMany
    {
        return $this->hasMany(PriceEstimate::class);
    }

    /**
     * Global average price for this product across all cities and all users.
     *
     * @param  Currency  $targetCurrency
     * @param  int       $days            0 = all time.
     * @return float|null
     */
    public function averagePrice(
        Currency $targetCurrency,
        int $days = 30,
    ): ?float {
        return app(PriceAggregator::class)->globalAverage($this, $targetCurrency, $days);
    }

    /**
     * Average price for this product scoped to one city.
     *
     * @param  City      $city
     * @param  Currency  $targetCurrency
     * @param  int       $days            0 = all time.
     * @return float|null
     */
    public function averagePriceInCity(
        City $city,
        Currency $targetCurrency,
        int $days = 30,
    ): ?float {
        return app(PriceAggregator::class)->cityAverage($this, $city, $targetCurrency, $days);
    }

    /**
     * Return all products keyed by category, for grouped UI listings.
     * Returns a Collection of Categories, each with their products eager-loaded.
     */
    public static function groupedByCategory(): Collection
    {
        return Category::with('products')->get();
    }

    /**
     * Cheapest and most expensive cities for this product.
     * Returns a Collection of ['city' => City, 'average' => float] sorted ascending.
     */
    // public function pricesByCities(
    //     Currency $targetCurrency,
    //     int $days = 30,
    // ): Collection {
    //     return City::all()
    //         ->map(fn(City $city) => [
    //             'city'    => $city,
    //             'average' => $this->averagePriceInCity($city, $targetCurrency, $days),
    //         ])
    //         ->filter(fn($row) => $row['average'] !== null)
    //         ->sortBy('average')
    //         ->values();
    // }

    public function pricesByCities(
        Currency $targetCurrency,
        int $days = 30,
    ): Collection {
        return app(PriceAggregator::class)->cityBreakdown($this, $targetCurrency, $days);
    }
}