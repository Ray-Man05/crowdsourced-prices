<?php

namespace App\Services;

use App\Models\City;
use App\Models\Country;
use App\Models\Currency;
use App\Models\ExchangeRate;
use App\Models\PriceEstimate;
use App\Models\Product;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class PriceAggregator
{
    // -------------------------------------------------------------------------
    // Configuration
    // -------------------------------------------------------------------------

    /** @var string 'iqr' or 'sd' */
    public const STRATEGY   = 'iqr';
    public const IQR_PARAM  = 1.5;
    /**
     * Minimum fence half-width as a fraction of the median.
     * Prevents the IQR fence from collapsing on tight clusters.
     * 0.5 = bounds are always at least ±50% of the median price.
     * Increase this to be more permissive of price changes.
     */
    public const IQR_MIN_WIDTH_FRACTION = 4.0;
    public const SD_PARAM   = 2.0;

    /**
     * Minimum number of values in a city group before outlier bounds
     * can be computed reliably. Groups below this threshold are left unfiltered.
     */
    public const MIN_SAMPLE = 8;

    // -------------------------------------------------------------------------
    // Scope constants — used by dailySeries and submissionsPerDay
    // -------------------------------------------------------------------------

    public const SCOPE_CITY    = 'city';
    public const SCOPE_COUNTRY = 'country';
    public const SCOPE_GLOBAL  = 'global';

    // Keyed by "productId:currencyId:cityId:countryId" (no $days — bounds are always
    // derived from all-time data). Shared across calls with different display windows.
    // Lifetime is the current request — no invalidation needed.
    private array $boundsCache = [];

    // Keyed by "productId:days:cityId:countryId". Prevents redundant DB queries when the
    // same product+scope is requested multiple times in one request (e.g. dashboard page
    // calls cityAverage + isEstimateOutlier for the same product).
    private array $fetchCache = [];

    // -------------------------------------------------------------------------
    // Public API — single number averages
    // -------------------------------------------------------------------------

    /**
     * Average price for one product in one city.
     * Returns null if there are no estimates or none survive outlier removal.
     */
    public function cityAverage(
        Product  $product,
        City     $city,
        Currency $currency,
        int      $days = 30,
    ): ?float {
        $key = $this->cacheKey('cityAverage', $product->id, $currency->id, $days, $city->id);
        return Cache::remember($key, 3600, function () use ($product, $city, $currency, $days) {
            $tagged = $this->taggedEstimates($product, $currency, $days, cityId: $city->id);
            return $this->averageOfClean($tagged);
        });
    }

    /**
     * Average price for one product in one country.
     * Computed as the mean of city averages — prevents high-volume cities
     * from dominating the result.
     */
    public function countryAverage(
        Product  $product,
        Country  $country,
        Currency $currency,
        int      $days = 30,
    ): ?float {
        $key = $this->cacheKey('countryAverage', $product->id, $currency->id, $days, $country->id);
        return Cache::remember($key, 3600, function () use ($product, $country, $currency, $days) {
            $tagged = $this->taggedEstimates($product, $currency, $days, countryId: $country->id);
            return $this->meanOfCityAverages($tagged);
        });
    }

    /**
     * Global average price for one product across all cities.
     * Computed as the mean of city averages.
     */
    public function globalAverage(
        Product  $product,
        Currency $currency,
        int      $days = 30,
    ): ?float {
        $key = $this->cacheKey('globalAverage', $product->id, $currency->id, $days);
        return Cache::remember($key, 3600, function () use ($product, $currency, $days) {
            $tagged = $this->taggedEstimates($product, $currency, $days);
            return $this->meanOfCityAverages($tagged);
        });
    }

    // -------------------------------------------------------------------------
    // Public API — dual-city comparison (fast, focused query)
    // -------------------------------------------------------------------------

    /**
     * Average prices for all products in exactly two cities.
     *
     * Loads estimates ONLY for the two target cities — a small fraction of the
     * full table (e.g., ~500 rows instead of 222K). The cache key is normalised
     * so swapping City A and City B is always a cache HIT.
     *
     * Outlier removal: simple median-ratio fence (prices outside [median/5,
     * median×5] are dropped). Fast and good enough for side-by-side display.
     *
     * @return array<int, array<int, float>>  [cityId => [productId => average]]
     */
    public function dualCityMetrics(
        City       $cityA,
        City       $cityB,
        Collection $products,
        Currency   $currency,
        int        $days = 30,
    ): array {
        if ($products->isEmpty()) return [];

        $productIds = $products->pluck('id')->sort()->values();

        // Normalise order so A↔B swap is a cache hit.
        $normA = min($cityA->id, $cityB->id);
        $normB = max($cityA->id, $cityB->id);

        $versionMap  = Cache::many($productIds->map(fn($id) => "agg_v:{$id}")->all());
        $versionHash = md5(implode(',', array_values($versionMap)));
        $cacheKey    = "agg:dual:{$normA}:{$normB}:{$currency->id}:{$days}:{$versionHash}";

        return Cache::remember($cacheKey, 3600, function () use ($productIds, $cityA, $cityB, $currency, $days) {
            $cutoff = $days > 0 ? Carbon::now()->subDays($days) : null;

            // ONE focused query — only the two cities, not the full 222K table.
            $estimates = PriceEstimate::whereIn('product_id', $productIds->all())
                ->whereIn('city_id', [$cityA->id, $cityB->id])
                ->select('price', 'currency_id', 'city_id', 'product_id', 'recorded_at')
                ->get();

            if ($estimates->isEmpty()) return [];

            $rateMap = $this->buildRateMap($estimates, $currency);

            $estimates->each(function ($e) use ($rateMap) {
                $e->converted = isset($rateMap[$e->currency_id])
                    ? (float) $e->price * $rateMap[$e->currency_id]
                    : null;
            });

            $windowed = $cutoff
                ? $estimates->filter(fn($e) => $e->recorded_at >= $cutoff)
                : $estimates;

            $result = [];
            foreach ($windowed->groupBy('product_id') as $productId => $byProduct) {
                foreach ($byProduct->groupBy('city_id') as $cityId => $group) {
                    $prices = $group->pluck('converted')->filter()->sort()->values();
                    if ($prices->isEmpty()) continue;

                    // Simple median-ratio fence: exclude [price < median/5 or > median*5].
                    $mid    = (int) floor($prices->count() / 2);
                    $median = $prices->count() % 2 === 0
                        ? ($prices[$mid - 1] + $prices[$mid]) / 2.0
                        : (float) $prices[$mid];

                    $clean = $median > 0
                        ? $prices->filter(fn($p) => $p >= $median / 5 && $p <= $median * 5)
                        : $prices;

                    if ($clean->isNotEmpty()) {
                        $result[$cityId][$productId] = round($clean->average(), 2);
                    }
                }
            }

            return $result;
        });
    }

    // -------------------------------------------------------------------------
    // Public API — coverage data (no currency, no outlier detection)
    // -------------------------------------------------------------------------

    /**
     * Fast single-query city coverage: how many estimates exist per city.
     * $days = 0 means all-time. Used by the landing page and MapPage coverage mode.
     *
     * @return Collection<array{city_id: int, city_name: string, country: string, lat: float, lng: float, count: int}>
     */
    public function coverageByCity(int $days = 0): Collection
    {
        return Cache::remember("agg:coverageByCity:{$days}", 3600, function () use ($days) {
            $cutoff = $days > 0 ? Carbon::now()->subDays($days) : null;

            return PriceEstimate::query()
                ->join('cities',    'cities.id',    '=', 'price_estimates.city_id')
                ->join('countries', 'countries.id', '=', 'cities.country_id')
                ->when($cutoff, fn($q) => $q->where('price_estimates.recorded_at', '>=', $cutoff))
                ->groupBy('price_estimates.city_id', 'cities.name', 'cities.lat', 'cities.lng', 'countries.name')
                ->selectRaw('price_estimates.city_id, cities.name AS city_name, cities.lat, cities.lng, countries.name AS country_name, COUNT(*) AS count')
                ->get()
                ->map(fn($row) => [
                    'city_id'   => (int)   $row->city_id,
                    'city_name' => (string) $row->city_name,
                    'country'   => (string) $row->country_name,
                    'lat'       => (float)  $row->lat,
                    'lng'       => (float)  $row->lng,
                    'count'     => (int)    $row->count,
                ]);
        });
    }

    // -------------------------------------------------------------------------
    // Public API — breakdown lists
    // -------------------------------------------------------------------------

    /**
     * Per-city breakdown for one product.
     * Each row contains the city, its clean average, and its clean submission count.
     * Sorted cheapest first.
     *
     * @return Collection<array{
     *     city: City,
     *     average: float,
     *     submissions: int,
     *     symbol: string
     * }>
     */
    public function cityBreakdown(
        Product  $product,
        Currency $currency,
        int      $days = 0,
    ): Collection {
        $key = $this->cacheKey('cityBreakdown', $product->id, $currency->id, $days);
        return Cache::remember($key, 3600, function () use ($product, $currency, $days) {
            return $this->taggedEstimates($product, $currency, $days)
                ->groupBy('city_id')
                ->map(fn(Collection $group) => [
                    'city'        => $group->first()->city,
                    'average'     => $this->averageOfClean($group),
                    'submissions' => $group->where('is_outlier', false)->count(),
                    'symbol'      => $currency->symbol,
                ])
                ->filter(fn($r) => $r['average'] !== null && $r['city'] !== null)
                ->sortBy('average')
                ->values();
        });
    }

    /**
     * Per-country breakdown for one product.
     * Each row contains the country, the mean of its cities' averages,
     * and how many cities contributed data.
     * Sorted cheapest first.
     *
     * @return Collection<array{
     *     country: Country,
     *     average: float,
     *     cities: int,
     *     symbol: string
     * }>
     */
    public function countryBreakdown(
        Product  $product,
        Currency $currency,
        int      $days = 0,
    ): Collection {
        $key = $this->cacheKey('countryBreakdown', $product->id, $currency->id, $days);
        return Cache::remember($key, 3600, function () use ($product, $currency, $days) {
            return $this->cityBreakdown($product, $currency, $days)
                ->groupBy(fn($r) => $r['city']->country_id)
                ->map(function (Collection $group) use ($currency) {
                    $cityAverages = $group->pluck('average')->filter();

                    return [
                        'country' => $group->first()['city']->country,
                        'average' => $cityAverages->isEmpty()
                            ? null
                            : round($cityAverages->average(), 2),
                        'cities'  => $group->count(),
                        'symbol'  => $currency->symbol,
                    ];
                })
                ->filter(fn($r) => $r['average'] !== null && $r['country'] !== null)
                ->sortBy('average')
                ->values();
        });
    }

    /**
     * Daily average prices over a time window, for the chart.
     * Only clean (non-outlier) estimates are included in each day's average.
     * Outlier bounds are computed across the full window before grouping by day,
     * so a lone outlier on a single day is still correctly identified.
     *
     * $scope: ['type' => self::SCOPE_CITY,    'id' => $cityId]
     *         ['type' => self::SCOPE_COUNTRY, 'id' => $countryId]
     *         ['type' => self::SCOPE_GLOBAL]
     *
     * @return Collection<array{
     *     date: string,
     *     average: float,
     *     submissions: int,
     *     symbol: string
     * }>
     */
    public function dailyAverages(
        Product  $product,
        Currency $currency,
        int      $days  = 90,
        array    $scope = ['type' => self::SCOPE_GLOBAL],
    ): Collection {
        $tagged = $this->taggedEstimatesForScope($product, $currency, $days, $scope);

        return $tagged
            ->where('is_outlier', false)
            ->groupBy(fn($e) => $e->recorded_at->toDateString())
            ->map(function (Collection $group) use ($currency) {
                $prices = $group->pluck('converted_price')->filter();

                return [
                    'date'        => $group->first()->recorded_at->toDateString(),
                    'average'     => $prices->isNotEmpty()
                        ? round($prices->average(), 2)
                        : null,
                    'submissions' => $group->count(),
                    'symbol'      => $currency->symbol,
                ];
            })
            ->filter(fn($r) => $r['average'] !== null)
            ->sortKeys()
            ->values();
    }

    /**
     * All individual submissions for a product over a time window,
     * grouped by day, with each estimate flagged as clean or outlier.
     * Used by admin views and any context where raw submissions must be shown.
     *
     * $scope: same structure as dailyAverages.
     *
     * @return Collection<string, Collection<PriceEstimate>>
     *         Keyed by date string 'YYYY-MM-DD'. Each estimate has is_outlier set.
     */
    public function dailySubmissions(
        Product $product,
        Currency $currency,
        int     $days  = 90,
        array   $scope = ['type' => self::SCOPE_GLOBAL],
    ): Collection {
        return $this->taggedEstimatesForScope($product, $currency, $days, $scope)
            ->groupBy(fn($e) => $e->recorded_at->toDateString())
            ->sortKeys();
    }

    // -------------------------------------------------------------------------
    // Public API — bulk catalog metrics
    // -------------------------------------------------------------------------

    /**
     * Compute city-scoped price metrics for multiple products in a single DB query.
     * Returns a plain array keyed by product ID with 'average' and 'average3x' values.
     *
     * This is the catalog-page fast path: one query replaces N×2 individual aggregator
     * calls, one per product × two windows ($days and 3×$days).
     *
     * Outlier detection uses city-level bounds from all-time data per product.
     * The country/global fallback is intentionally omitted here — when the sample
     * is too small for city bounds, all estimates are treated as clean, which is the
     * same semantics as having no fallback available.
     *
     * @param  Collection<Product>  $products
     * @return array<int, array{average: ?float, average3x: ?float}>  Keyed by product_id.
     */
    public function bulkCityMetrics(
        Collection $products,
        City       $city,
        Currency   $currency,
        int        $days = 30,
    ): array {
        if ($products->isEmpty()) return [];

        $sortedIds = $products->pluck('id')->sort()->values();

        // Composite version key: one Cache::many() call for all product versions.
        $versionMap  = Cache::many($sortedIds->map(fn($id) => "agg_v:{$id}")->all());
        $versionHash = md5(implode(',', array_values($versionMap)));
        $cacheKey    = "agg:bulkCity:{$city->id}:{$currency->id}:{$days}:{$versionHash}";

        return Cache::remember($cacheKey, 3600, function () use ($sortedIds, $city, $currency, $days) {
            // Load all estimates globally (minimal columns) for city→country→global bounds fallback.
            $allEstimates = PriceEstimate::whereIn('product_id', $sortedIds->all())
                ->select('id', 'price', 'currency_id', 'city_id', 'product_id', 'recorded_at')
                ->get();

            $rateMap = $this->buildRateMap($allEstimates, $currency);

            $convertedById = $allEstimates->mapWithKeys(fn($e) => [
                $e->id => isset($rateMap[$e->currency_id])
                    ? (float) $e->price * $rateMap[$e->currency_id]
                    : null,
            ]);

            // Preload city→country mapping (one lightweight query).
            $cityCountryMap = City::whereIn('id', $allEstimates->pluck('city_id')->unique()->all())
                ->pluck('country_id', 'id')
                ->all();

            $cutoff1x  = $days > 0 ? Carbon::now()->subDays($days) : null;
            $cutoff3x  = $days > 0 ? Carbon::now()->subDays($days * 3) : null;
            $byProduct = $allEstimates->groupBy('product_id');
            $result    = [];

            foreach ($sortedIds as $productId) {
                $allGroup  = $byProduct->get($productId, collect());
                $cityGroup = $allGroup->where('city_id', $city->id);

                if ($cityGroup->isEmpty()) {
                    $result[$productId] = ['average' => null, 'average3x' => null, 'has_city_data' => false];
                    continue;
                }

                // City bounds
                $cityPrices = $cityGroup->map(fn($e) => $convertedById[$e->id])->filter()->values();
                $cityBounds = $this->computeBounds($cityPrices);

                // Country bounds (fallback) — group all estimates by country via the preloaded map
                $countryId     = $city->country_id;
                $countryBounds = null;
                if ($cityBounds === null) {
                    $countryPrices = $allGroup
                        ->filter(fn($e) => ($cityCountryMap[$e->city_id] ?? null) === $countryId)
                        ->map(fn($e) => $convertedById[$e->id])
                        ->filter()
                        ->values();
                    $countryBounds = $this->computeBounds($countryPrices);
                }

                // Global bounds (last resort)
                $globalBounds = null;
                if ($cityBounds === null && $countryBounds === null) {
                    $globalPrices = $allGroup->map(fn($e) => $convertedById[$e->id])->filter()->values();
                    $globalBounds = $this->computeBounds($globalPrices);
                }

                $b = $cityBounds ?? $countryBounds ?? $globalBounds;

                $tagged = $cityGroup->each(function ($e) use ($convertedById, $b) {
                    $e->converted_price = $convertedById[$e->id] ?? null;
                    $e->is_outlier      = $e->converted_price === null
                        || ($b !== null && !$this->withinBounds($e->converted_price, $b));
                });

                $tagged3x = $cutoff3x
                    ? $tagged->filter(fn($e) => $e->recorded_at >= $cutoff3x)
                    : $tagged;

                $tagged1x = $cutoff1x
                    ? $tagged3x->filter(fn($e) => $e->recorded_at >= $cutoff1x)
                    : $tagged3x;

                $result[$productId] = [
                    'average'       => $this->averageOfClean($tagged1x),
                    'average3x'     => $this->averageOfClean($tagged3x),
                    'has_city_data' => true,
                ];
            }

            return $result;
        });
    }

    /**
     * Status and formatted estimate for each user's most recent in-window estimate per product.
     * Returned only for products where an estimate exists within the cooldown window.
     *
     * Each value is an array:
     *   'status'             => 'active_cooldown' | 'active_outlier' | 'deleted_cooldown'
     *   'formattedEstimate'  => string|null  — the estimate's price in its submission currency
     *
     * @param  Collection<Product>  $products
     * @return array<int, array{status: string, formattedEstimate: ?string}>  Keyed by product_id.
     */
    public function bulkUserStatuses(
        Collection $products,
        Currency   $currency,
        int        $userId,
    ): array {
        if ($products->isEmpty()) return [];

        $cutoff = Carbon::now()->subDays(PriceEstimate::ESTIMATE_COOLDOWN_DAYS);

        $userEstimates = PriceEstimate::withTrashed()
            ->where('user_id', $userId)
            ->whereIn('product_id', $products->pluck('id')->all())
            ->where('recorded_at', '>=', $cutoff)
            ->with('currency')
            ->get()
            ->sortByDesc('recorded_at')
            ->groupBy('product_id')
            ->map(fn($g) => $g->first());

        if ($userEstimates->isEmpty()) return [];

        $result = $userEstimates->mapWithKeys(fn($e, $productId) => [
            $productId => [
                'status'            => $e->deleted_at ? 'deleted_cooldown' : 'active_cooldown',
                'formattedEstimate' => $e->deleted_at
                    ? null
                    : $e->currency->symbol . number_format($e->price, 2),
            ],
        ])->all();

        $active = $userEstimates->filter(fn($e) => !$e->deleted_at);
        if ($active->isEmpty()) return $result;

        // Bulk outlier detection: replaces per-estimate isEstimateOutlier() calls.
        // isEstimateOutlier() triggered a lazy-load of $estimate->product (N+1) and then
        // called taggedEstimates() per estimate (up to 3 DB queries each). bulkEstimateStats()
        // handles the same logic for all active estimates in 3 total DB queries.
        $outlierStats = $this->bulkEstimateStats($active->values(), $currency);

        foreach ($active as $productId => $userEstimate) {
            if ($outlierStats[$userEstimate->id]['is_outlier'] ?? false) {
                $result[$productId]['status'] = 'active_outlier';
            }
        }

        return $result;
    }

    /**
     * Compute per-city averages for multiple products across all cities in two DB queries.
     *
     * Returns a sparse map — only cities with a non-null clean average are included.
     * Used by the map page to replace O(products × cities) individual cityAverage() calls.
     *
     * Outlier detection mirrors taggedEstimates(): city bounds → country bounds → global,
     * all derived from all-time data. The display window is applied after tagging.
     *
     * @param  Collection<Product>  $products
     * @return array<int, array<int, float>>  [cityId => [productId => average]]
     */
    public function bulkMultiCityMetrics(
        Collection $products,
        Currency   $currency,
        int        $days = 30,
    ): array {
        if ($products->isEmpty()) {
            return [];
        }

        $sortedIds = $products->pluck('id')->sort()->values();

        // Versioned cache: invalidated whenever any product's estimates change.
        $versionMap  = Cache::many($sortedIds->map(fn($id) => "agg_v:{$id}")->all());
        $versionHash = md5(implode(',', array_values($versionMap)));
        $cacheKey    = "agg:bulkMultiCity:{$currency->id}:{$days}:{$versionHash}";

        return Cache::remember($cacheKey, 3600, function () use ($sortedIds, $currency, $days) {
            // Minimal select + no with() — rate map and city→country map handle conversion
            // and bounds fallback without hydrating full Currency/City/Country models.
            $allEstimates = PriceEstimate::whereIn('product_id', $sortedIds->all())
                ->select('id', 'price', 'currency_id', 'city_id', 'product_id', 'recorded_at')
                ->get();

            if ($allEstimates->isEmpty()) {
                return [];
            }

            $rateMap = $this->buildRateMap($allEstimates, $currency);

            $convertedById = $allEstimates->mapWithKeys(fn($e) => [
                $e->id => isset($rateMap[$e->currency_id])
                    ? (float) $e->price * $rateMap[$e->currency_id]
                    : null,
            ]);

            // City→country map: avoids city.country eager-load per estimate.
            $cityCountryMap = City::whereIn('id', $allEstimates->pluck('city_id')->unique()->all())
                ->pluck('country_id', 'id')
                ->all();

            $cutoff    = $days > 0 ? Carbon::now()->subDays($days) : null;
            $byProduct = $allEstimates->groupBy('product_id');
            $result    = [];

            foreach ($sortedIds as $productId) {
                $group = $byProduct->get($productId, collect());

                if ($group->isEmpty()) {
                    continue;
                }

                $byCityId      = $group->groupBy('city_id');
                $cityBoundsMap = $this->boundsPerCity($byCityId, $convertedById);

                // Country bounds without relation traversal, using the preloaded city→country map.
                $countryBoundsMap = $byCityId
                    ->groupBy(fn(Collection $g) => $cityCountryMap[$g->first()->city_id] ?? 0)
                    ->map(fn(Collection $cityGroups) => $this->computeBounds(
                        $cityGroups->flatten(1)->map(fn($e) => $convertedById[$e->id])->filter()->values()
                    ))
                    ->filter();

                $globalBounds = $this->computeBounds(
                    $group->map(fn($e) => $convertedById[$e->id])->filter()->values()
                );

                $group->each(function ($e) use ($convertedById, $cityBoundsMap, $countryBoundsMap, $globalBounds, $cityCountryMap) {
                    $e->converted_price = $convertedById[$e->id] ?? null;
                    $countryId          = $cityCountryMap[$e->city_id] ?? 0;
                    $b                  = $cityBoundsMap->get($e->city_id)
                        ?? $countryBoundsMap->get($countryId)
                        ?? $globalBounds;
                    $e->is_outlier      = $e->converted_price === null
                        || ($b !== null && !$this->withinBounds($e->converted_price, $b));
                });

                $windowed = $cutoff ? $group->filter(fn($e) => $e->recorded_at >= $cutoff) : $group;

                foreach ($windowed->groupBy('city_id') as $cityId => $cityGroup) {
                    $avg = $this->averageOfClean($cityGroup);
                    if ($avg !== null) {
                        $result[$cityId][$productId] = $avg;
                    }
                }
            }

            return $result;
        });
    }

    /**
     * Coverage statistics for the "products with data" map mode.
     *
     * Loads all estimates in three flat DB queries (estimates, exchange rates,
     * city→country map), applies the same city→country→global outlier-detection
     * fallback as bulkMultiCityMetrics(), then counts non-outlier products per city
     * and non-outlier cities per product across the given display window.
     *
     * Bounds are always derived from all-time data; only counting uses the window.
     *
     * @return array{
     *     city_product_counts: array<int,int>,  cityId  => # products with clean data
     *     product_city_counts: array<int,int>,  productId => # cities with clean data
     *     total_products:      int,             distinct products with any clean data
     * }
     */
    public function bulkCoverageByProduct(Currency $currency, Collection $products, int $days = 0): array
    {
        $sortedIds = $products->pluck('id')->sort()->values();

        if ($sortedIds->isEmpty()) {
            return ['city_product_counts' => [], 'product_city_counts' => [], 'total_products' => 0];
        }

        // Versioned cache: invalidated whenever any relevant product's estimates change.
        $versionMap  = Cache::many($sortedIds->map(fn($id) => "agg_v:{$id}")->all());
        $versionHash = md5(implode(',', array_values($versionMap)));
        $cacheKey    = "agg:bulkCoverage:{$currency->id}:{$days}:{$versionHash}";

        return Cache::remember($cacheKey, 3600, function () use ($sortedIds, $currency, $days) {
            $allEstimates = PriceEstimate::whereIn('product_id', $sortedIds->all())
                ->select('id', 'price', 'currency_id', 'city_id', 'product_id', 'recorded_at')
                ->get();

            if ($allEstimates->isEmpty()) {
                return ['city_product_counts' => [], 'product_city_counts' => [], 'total_products' => 0];
            }

            $rateMap = $this->buildRateMap($allEstimates, $currency);

            $convertedById = $allEstimates->mapWithKeys(fn($e) => [
                $e->id => isset($rateMap[$e->currency_id])
                    ? (float) $e->price * $rateMap[$e->currency_id]
                    : null,
            ]);

            // Pre-load city→country mapping — avoids city.country eager-load per estimate.
            $cityCountryMap = City::whereIn('id', $allEstimates->pluck('city_id')->unique()->all())
                ->pluck('country_id', 'id')
                ->all();

            $cutoff    = $days > 0 ? Carbon::now()->subDays($days) : null;
            $byProduct = $allEstimates->groupBy('product_id');

            $cityProductCounts = [];
            $productCityCounts = [];

            foreach ($byProduct as $productId => $group) {
                $byCityId = $group->groupBy('city_id');

                $cityBoundsMap = $this->boundsPerCity($byCityId, $convertedById);

                $countryBoundsMap = $byCityId
                    ->groupBy(fn(Collection $g) => $cityCountryMap[$g->first()->city_id] ?? 0)
                    ->map(fn(Collection $cityGroups) =>
                        $this->computeBounds(
                            $cityGroups->flatten(1)
                                ->map(fn($e) => $convertedById[$e->id])
                                ->filter()
                                ->values()
                        )
                    )
                    ->filter();

                $globalBounds = $this->computeBounds(
                    $group->map(fn($e) => $convertedById[$e->id])->filter()->values()
                );

                // Tag every estimate for this product (bounds from all-time data above).
                $group->each(function ($e) use ($convertedById, $cityBoundsMap, $countryBoundsMap, $globalBounds, $cityCountryMap) {
                    $countryId          = $cityCountryMap[$e->city_id] ?? 0;
                    $b                  = $cityBoundsMap->get($e->city_id)
                        ?? $countryBoundsMap->get($countryId)
                        ?? $globalBounds;
                    $e->converted_price = $convertedById[$e->id] ?? null;
                    $e->is_outlier      = $e->converted_price === null
                        || ($b !== null && !$this->withinBounds($e->converted_price, $b));
                });

                // Apply display window, then count cities with at least one clean estimate.
                $windowed = $cutoff ? $group->filter(fn($e) => $e->recorded_at >= $cutoff) : $group;

                $productCityCounts[$productId] = 0;

                foreach ($windowed->groupBy('city_id') as $cityId => $cityGroup) {
                    if ($cityGroup->contains('is_outlier', false)) {
                        $cityProductCounts[$cityId] = ($cityProductCounts[$cityId] ?? 0) + 1;
                        $productCityCounts[$productId]++;
                    }
                }
            }

            return [
                'city_product_counts' => $cityProductCounts,
                'product_city_counts' => $productCityCounts,
                'total_products'      => count(array_filter($productCityCounts)),
            ];
        });
    }

    /**
     * Compute city average and outlier flag for a collection of user estimates in 3 DB queries,
     * regardless of how many estimates are passed in.
     *
     * Replaces per-estimate calls to cityAverage() + isEstimateOutlier() on the dashboard.
     * Uses the same rate-map pattern as bulkCityMetrics() — no Eloquent relation traversal.
     *
     * @param  Collection<PriceEstimate>  $userEstimates  Non-deleted, already loaded.
     * @return array<int, array{city_average: ?float, is_outlier: bool, converted_price: ?float}>
     *         Keyed by estimate ID.
     */
    public function bulkEstimateStats(
        Collection $userEstimates,
        Currency   $currency,
        int        $days = 30,
    ): array {
        if ($userEstimates->isEmpty()) return [];

        $productIds = $userEstimates->pluck('product_id')->unique()->values()->all();

        // Load all estimates for these products — needed to compute outlier bounds.
        $allEstimates = PriceEstimate::whereIn('product_id', $productIds)
            ->select('id', 'price', 'currency_id', 'city_id', 'product_id', 'recorded_at')
            ->get();

        if ($allEstimates->isEmpty()) return [];

        $rateMap = $this->buildRateMap($allEstimates, $currency);

        $convertedById = $allEstimates->mapWithKeys(fn($e) => [
            $e->id => isset($rateMap[$e->currency_id])
                ? (float) $e->price * $rateMap[$e->currency_id]
                : null,
        ]);

        // City→country map for bounds fallback — avoids relation traversal.
        $cityCountryMap = City::whereIn('id', $allEstimates->pluck('city_id')->unique()->all())
            ->pluck('country_id', 'id')
            ->all();

        $cutoff    = $days > 0 ? Carbon::now()->subDays($days) : null;
        $byProduct = $allEstimates->groupBy('product_id');
        $allById   = $allEstimates->keyBy('id');

        // Per-product: tag every estimate with is_outlier and compute per-city averages.
        $productCityAvg = [];

        foreach ($productIds as $productId) {
            $group = $byProduct->get($productId, collect());
            if ($group->isEmpty()) continue;

            $byCityId      = $group->groupBy('city_id');
            $cityBoundsMap = $this->boundsPerCity($byCityId, $convertedById);

            $countryBoundsMap = $byCityId
                ->groupBy(fn(Collection $g) => $cityCountryMap[$g->first()->city_id] ?? 0)
                ->map(fn(Collection $cityGroups) => $this->computeBounds(
                    $cityGroups->flatten(1)->map(fn($e) => $convertedById[$e->id])->filter()->values()
                ))
                ->filter();

            $globalBounds = $this->computeBounds(
                $group->map(fn($e) => $convertedById[$e->id])->filter()->values()
            );

            $group->each(function ($e) use ($convertedById, $cityBoundsMap, $countryBoundsMap, $globalBounds, $cityCountryMap) {
                $e->converted_price = $convertedById[$e->id] ?? null;
                $countryId          = $cityCountryMap[$e->city_id] ?? 0;
                $b                  = $cityBoundsMap->get($e->city_id)
                    ?? $countryBoundsMap->get($countryId)
                    ?? $globalBounds;
                $e->is_outlier      = $e->converted_price === null
                    || ($b !== null && !$this->withinBounds($e->converted_price, $b));
            });

            foreach ($byCityId as $cityId => $cityGroup) {
                $windowed = $cutoff
                    ? $cityGroup->filter(fn($e) => $e->recorded_at >= $cutoff)
                    : $cityGroup;
                $productCityAvg[$productId][$cityId] = $this->averageOfClean($windowed);
            }
        }

        $result = [];
        foreach ($userEstimates as $estimate) {
            $id        = $estimate->id;
            $productId = $estimate->product_id;
            $cityId    = $estimate->city_id;

            $convertedPrice = $convertedById[$id] ?? null;

            if (!$cityId) {
                $result[$id] = ['city_average' => null, 'is_outlier' => false, 'converted_price' => $convertedPrice];
                continue;
            }

            $tagged      = $allById->get($id);
            $isOutlier   = $tagged ? (bool) $tagged->is_outlier : false;
            $cityAverage = $productCityAvg[$productId][$cityId] ?? null;

            $result[$id] = [
                'city_average'    => $cityAverage,
                'is_outlier'      => $isOutlier,
                'converted_price' => $convertedPrice,
            ];
        }

        return $result;
    }

    /**
     * Whether a single active estimate is flagged as an outlier in its city context.
     * Bounds are derived from all-time data for the product+city combination.
     */
    public function isEstimateOutlier(PriceEstimate $estimate, Currency $currency): bool
    {
        $tagged = $this->taggedEstimates($estimate->product, $currency, 0, cityId: $estimate->city_id);
        $found  = $tagged->firstWhere('id', $estimate->id);
        return $found ? (bool) $found->is_outlier : false;
    }

    // -------------------------------------------------------------------------
    // Private — core primitive
    // -------------------------------------------------------------------------

    /**
     * Fetch estimates and tag each one with is_outlier = true|false
     * and converted_price = float|null.
     *
     * Outlier detection runs in converted-currency space per city, with a
     * three-level fallback (city → country → global) for small city groups.
     * Bounds are always computed from all-time data via resolvedBounds(), which
     * caches them for the request — so calls with different $days values but the
     * same product/currency/scope share one bounds computation.
     *
     * This is the only place outlier logic runs. Every public method
     * uses this tagged collection as its starting point.
     *
     * @return Collection<PriceEstimate>  each with dynamic is_outlier and converted_price properties
     */
    private function taggedEstimates(
        Product  $product,
        Currency $currency,
        int      $days      = 0,
        ?int     $cityId    = null,
        ?int     $countryId = null,
    ): Collection {
        $estimates = $this->fetch($product, $days, $cityId, $countryId);

        // When $days=0 the display data is already all-time; pass it directly so
        // resolvedBounds can prime its cache without issuing a second DB query.
        $bounds = $this->resolvedBounds($product, $currency, $cityId, $countryId,
            source: $days === 0 ? $estimates : null,
        );

        return $estimates
            ->groupBy('city_id')
            ->flatMap(function (Collection $cityGroup) use ($bounds) {
                $cityId    = $cityGroup->first()->city_id;
                $countryId = $cityGroup->first()->city->country_id;

                $b = $bounds['cityBoundsMap']->get($cityId)
                    ?? $bounds['countryBoundsMap']->get($countryId)
                    ?? $bounds['globalBounds'];

                return $cityGroup->each(function ($e) use ($bounds, $b) {
                    $e->converted_price = $bounds['convertedById'][$e->id] ?? null;
                    $e->is_outlier      = $e->converted_price === null
                        || ($b !== null && !$this->withinBounds($e->converted_price, $b));
                });
            })
            ->values();
    }

    /**
     * Returns the all-time bounds data for a given product/currency/scope,
     * computing it once and caching for the remainder of the request.
     *
     * The cache key omits $days because bounds are always derived from the full
     * history — a call with $days=30 and one with $days=90 share the same bounds.
     *
     * @param ?Collection $source  Already-fetched all-time collection; pass this
     *                             when $days=0 to avoid a redundant DB query.
     * @return array{convertedById: Collection, cityBoundsMap: Collection, countryBoundsMap: Collection, globalBounds: ?array}
     */
    private function resolvedBounds(
        Product     $product,
        Currency    $currency,
        ?int        $cityId    = null,
        ?int        $countryId = null,
        ?Collection $source    = null,
    ): array {
        $key = "{$product->id}:{$currency->id}:{$cityId}:{$countryId}";

        if (array_key_exists($key, $this->boundsCache)) {
            return $this->boundsCache[$key];
        }

        // The display source — filtered to the requested scope
        $source ??= $this->fetch($product, 0, $cityId, $countryId);

        // The fallback source — always the full product history across all cities
        // so country and global fallbacks are not starved when a city filter is active
        $fallbackSource = ($cityId !== null || $countryId !== null)
            ? $this->fetch($product, 0)
            : $source;

        $converted         = $source->mapWithKeys(fn($e) => [$e->id => $e->currency->convert($e->price, $currency)]);
        $fallbackConverted = ($fallbackSource === $source)
            ? $converted
            : $fallbackSource->mapWithKeys(fn($e) => [$e->id => $e->currency->convert($e->price, $currency)]);

        // City bounds from the city-filtered source only
        $cityByCityId = $source->groupBy('city_id');

        // Country and global bounds from the full history
        $fallbackByCityId = $fallbackSource->groupBy('city_id');

        return $this->boundsCache[$key] = [
            'convertedById'    => $converted,
            'cityBoundsMap'    => $this->boundsPerCity($cityByCityId, $converted),
            'countryBoundsMap' => $this->boundsPerCountry($fallbackByCityId, $fallbackConverted),
            'globalBounds'     => $this->computeBounds($fallbackConverted->filter()->values()),
        ];
    }

    /**
     * Resolve scope array into taggedEstimates parameters and return tagged collection.
     */
    private function taggedEstimatesForScope(
        Product  $product,
        Currency $currency,
        int      $days,
        array    $scope,
    ): Collection {
        return match ($scope['type']) {
            self::SCOPE_CITY    => $this->taggedEstimates($product, $currency, $days, cityId:    $scope['id']),
            self::SCOPE_COUNTRY => $this->taggedEstimates($product, $currency, $days, countryId: $scope['id']),
            default             => $this->taggedEstimates($product, $currency, $days),
        };
    }

    // -------------------------------------------------------------------------
    // Private — querying
    // -------------------------------------------------------------------------

    private function fetch(
        Product $product,
        int     $days      = 0,
        ?int    $cityId    = null,
        ?int    $countryId = null,
    ): Collection {
        $key = "{$product->id}:{$days}:{$cityId}:{$countryId}";

        return $this->fetchCache[$key] ??= PriceEstimate::where('price_estimates.product_id', $product->id)
            ->when($days > 0,    fn($q) => $q->where('price_estimates.recorded_at', '>=', Carbon::now()->subDays($days)))
            ->when($cityId,      fn($q) => $q->where('price_estimates.city_id', $cityId))
            ->when($countryId,   fn($q) => $q
                ->join('cities', 'cities.id', '=', 'price_estimates.city_id')
                ->where('cities.country_id', $countryId)
                ->select('price_estimates.*')
            )
            ->with(['currency', 'city.country'])
            ->get();
    }

    // -------------------------------------------------------------------------
    // Private — cache key helpers
    // -------------------------------------------------------------------------

    /**
     * Build a versioned cache key for a public aggregator method.
     * The version is a counter stored in cache and bumped whenever estimates
     * for this product change (via PriceEstimate model events).
     */
    private function cacheKey(
        string $method,
        int    $productId,
        int    $currencyId,
        int    $days,
        ?int   $extraId = null,
    ): string {
        $v     = (int) Cache::get("agg_v:{$productId}", 0);
        $extra = $extraId !== null ? ":{$extraId}" : '';
        return "agg:{$method}:{$productId}:{$currencyId}:{$days}{$extra}:v{$v}";
    }

    // -------------------------------------------------------------------------
    // Private — aggregation helpers
    // -------------------------------------------------------------------------

    /**
     * Average the clean estimates in a tagged collection.
     * Reads the pre-computed converted_price set by taggedEstimates.
     */
    private function averageOfClean(Collection $tagged): ?float
    {
        $prices = $tagged
            ->where('is_outlier', false)
            ->pluck('converted_price')
            ->filter()
            ->values();

        return $prices->isEmpty() ? null : round($prices->average(), 2);
    }

    /**
     * Compute one average per city from a tagged collection,
     * then return the mean of those city averages.
     * Used by countryAverage and globalAverage.
     */
    private function meanOfCityAverages(Collection $tagged): ?float
    {
        $cityAverages = $tagged
            ->groupBy('city_id')
            ->map(fn($group) => $this->averageOfClean($group))
            ->filter()
            ->values();

        return $cityAverages->isEmpty() ? null : round($cityAverages->average(), 2);
    }

    // -------------------------------------------------------------------------
    // Private — bounds computation
    // -------------------------------------------------------------------------

    /**
     * Pre-fetch exchange rates for all currencies present in $estimates.
     * Returns array keyed by from_currency_id with the scalar rate to $currency.
     * Always includes $currency->id => 1.0 so same-currency estimates need no special case.
     */
    private function buildRateMap(Collection $estimates, Currency $currency): array
    {
        $uniqueCurrencyIds = $estimates->pluck('currency_id')->unique()->all();
        return ExchangeRate::whereIn('from_currency_id', $uniqueCurrencyIds)
            ->where('to_currency_id', $currency->id)
            ->pluck('rate', 'from_currency_id')
            ->put($currency->id, 1.0)
            ->all();
    }

    /**
     * Per-city bounds map. Accepts estimates already grouped by city_id.
     * Only cities that meet MIN_SAMPLE are included; absent entries signal
     * that a wider fallback should be used.
     *
     * @return Collection<int, array{min: float, max: float}>  Keyed by city_id.
     */
    private function boundsPerCity(Collection $byCityId, Collection $convertedById): Collection
    {
        return $byCityId
            ->map(fn(Collection $group) =>
                $this->computeBounds(
                    $group->map(fn($e) => $convertedById[$e->id])->filter()->values()
                )
            )
            ->filter(); // drop cities whose sample was below MIN_SAMPLE
    }

    /**
     * Per-country bounds map, derived from already-grouped city data.
     * Re-groups city groups by country (O(cities)) instead of scanning
     * all estimates again (O(estimates)), then pools each country's estimates.
     * Only countries that meet MIN_SAMPLE in aggregate are included.
     *
     * @return Collection<int, array{min: float, max: float}>  Keyed by country_id.
     */
    private function boundsPerCountry(Collection $byCityId, Collection $convertedById): Collection
    {
        return $byCityId
            ->groupBy(fn(Collection $cityGroup) => $cityGroup->first()->city->country_id)
            ->map(fn(Collection $cityGroups) =>
                $this->computeBounds(
                    $cityGroups->flatten(1)->map(fn($e) => $convertedById[$e->id])->filter()->values()
                )
            )
            ->filter();
    }

    /**
     * Compute [min, max] bounds for a set of already-converted prices.
     * Returns null when the sample is too small — the caller treats null
     * as "no filtering" (all estimates are clean).
     */
    private function computeBounds(Collection $prices): ?array
    {
        if ($prices->count() < self::MIN_SAMPLE) return null;

        $sorted = $prices->sort()->values();

        return match (self::STRATEGY) {
            'iqr'   => $this->boundsIqr($sorted),
            'sd'    => $this->boundsSd($sorted),
            default => null,
        };
    }

private function boundsIqr(Collection $sorted): array
{
    $count  = $sorted->count();
    $q1     = $sorted[(int) floor($count * 0.25)];
    $q3     = $sorted[(int) floor($count * 0.75)];
    $iqr    = $q3 - $q1;
    $mid    = (int) floor($count / 2);
    $median = $count % 2 === 0
        ? ($sorted[$mid - 1] + $sorted[$mid]) / 2.0
        : (float) $sorted[$mid];

    // Minimum half-width prevents the IQR fence from collapsing on tight clusters.
    // A price within IQR_MIN_WIDTH_FRACTION × median of the median is always clean.
    // e.g. with a value of 4.0: any price in [median/5, median×5] is never an outlier.
    $minHalfWidth = $median > 0 ? $median * self::IQR_MIN_WIDTH_FRACTION : 0.0;

    // When all values are identical IQR=0, fall back entirely to the median ratio fence.
    // The IQR fence would collapse to a single point, which is not useful.
    if ($iqr == 0.0) {
        return [
            'min' => max(0.0, $median - $minHalfWidth),
            'max' => $median + $minHalfWidth,
        ];
    }

    // Standard IQR fence
    $iqrLower = $q1 - self::IQR_PARAM * $iqr;
    $iqrUpper = $q3 + self::IQR_PARAM * $iqr;

    return [
        // Take the wider of the two fences on each side,
        // but never allow a negative lower bound for prices.
        'min' => max(0.0, min($iqrLower, $median / (1 + self::IQR_MIN_WIDTH_FRACTION))),
        'max' => max($iqrUpper, $median * (1 + self::IQR_MIN_WIDTH_FRACTION)),
    ];
}

    private function boundsSd(Collection $sorted): array
    {
        $mean     = $sorted->average();
        $variance = $sorted->reduce(
            fn($carry, $v) => $carry + ($v - $mean) ** 2, 0.0
        ) / $sorted->count();
        $sd = sqrt($variance);

        if ($sd == 0.0) return ['min' => -INF, 'max' => INF];

        return [
            'min' => $mean - self::SD_PARAM * $sd,
            'max' => $mean + self::SD_PARAM * $sd,
        ];
    }

    private function withinBounds(float $value, array $bounds): bool
    {
        return $value >= $bounds['min'] && $value <= $bounds['max'];
    }
}
