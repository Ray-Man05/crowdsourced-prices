<?php

namespace App\Services;

use App\Models\City;
use App\Models\Country;
use App\Models\Currency;
use App\Models\PriceEstimate;
use App\Models\Product;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

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
    public const MIN_SAMPLE = 4;

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
        $tagged = $this->taggedEstimates($product, $currency, $days, cityId: $city->id);
        return $this->averageOfClean($tagged);
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
        $tagged = $this->taggedEstimates($product, $currency, $days, countryId: $country->id);
        return $this->meanOfCityAverages($tagged);
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
        $tagged = $this->taggedEstimates($product, $currency, $days);
        return $this->meanOfCityAverages($tagged);
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

        $source    ??= $this->fetch($product, 0, $cityId, $countryId);
        $converted   = $source->mapWithKeys(fn($e) => [$e->id => $e->currency->convert($e->price, $currency)]);
        $byCityId    = $source->groupBy('city_id');

        return $this->boundsCache[$key] = [
            'convertedById'    => $converted,
            'cityBoundsMap'    => $this->boundsPerCity($byCityId, $converted),
            'countryBoundsMap' => $this->boundsPerCountry($byCityId, $converted),
            'globalBounds'     => $this->computeBounds($converted->filter()->values()),
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
        return PriceEstimate::where('product_id', $product->id)
            ->when($days > 0,    fn($q) => $q->where('recorded_at', '>=', Carbon::now()->subDays($days)))
            ->when($cityId,      fn($q) => $q->where('city_id', $cityId))
            ->when($countryId,   fn($q) => $q->whereHas('city', fn($q) => $q->where('country_id', $countryId)))
            ->with(['currency', 'city.country'])
            ->get();
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
        $q1  = $sorted[(int) floor($sorted->count() * 0.25)];
        $q3  = $sorted[(int) floor($sorted->count() * 0.75)];
        $iqr = $q3 - $q1;
        $median = $count % 2 === 0
        ? ($sorted[(int) ($count / 2) - 1] + $sorted[(int) ($count / 2)]) / 2
        : $sorted[(int) floor($count / 2)];

        // Minimum half-width ensures the fence never collapses on tight clusters.
        // A price that is within IQR_MIN_WIDTH_FRACTION of the median is always clean.
        $minHalfWidth = $median * self::IQR_MIN_WIDTH_FRACTION;

        

        if ($iqr == 0.0) return ['min' => -INF, 'max' => INF];

        $iqrLower = $q1 - self::IQR_PARAM * $iqr;
        $iqrUpper = $q3 + self::IQR_PARAM * $iqr;

        return [
            'min' => min($iqrLower, $median - $minHalfWidth),
            'max' => max($iqrUpper, $median + $minHalfWidth),
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
