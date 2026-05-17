<?php

namespace App\Livewire;

use App\Models\Category;
use App\Models\PriceEstimate;
use App\Models\Product;
use App\Services\PriceAggregator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Component;

class Dashboard extends Component
{
    public ?int $comparisonProductId = null;

    public function mount(): void
    {
        // Default to first product with estimates in the user's city
        $user = auth()->user();
        if ($user->city_id) {
            $this->comparisonProductId = PriceEstimate::where('city_id', $user->city_id)
                ->whereIn('product_id', Product::pluck('id'))
                ->value('product_id');
        }

        if (!$this->comparisonProductId) {
            $this->comparisonProductId = Product::first()?->id;
        }
    }

    /**
     * User's estimates submitted in the last 7 days (the cooldown window),
     * tagged with outlier status and position relative to city average.
     */
    public function getRecentEstimatesProperty(): Collection
    {
        $user      = auth()->user();
        $currency  = $user->effectiveCurrency();
        $aggregator = app(PriceAggregator::class);

        $estimates = PriceEstimate::where('user_id', $user->id)
            ->where('created_at', '>=', Carbon::now()->subDays(PriceEstimate::ESTIMATE_COOLDOWN_DAYS))
            ->with(['product.category', 'product.unit', 'currency', 'city'])
            ->latest('created_at')
            ->get();

        return $estimates->map(function ($estimate) use ($currency, $aggregator) {
            $cityAvg = $estimate->city
                ? $aggregator->cityAverage($estimate->product, $estimate->city, $currency, 30)
                : null;

            $convertedPrice = $estimate->currency->convert($estimate->price, $currency);

            // Position relative to city average
            $position = null;
            $deviation = null;
            if ($cityAvg !== null && $convertedPrice !== null && $cityAvg > 0) {
                $deviation = (($convertedPrice - $cityAvg) / $cityAvg) * 100;
                $position  = match (true) {
                    $deviation < -10 => 'low',
                    $deviation > 10  => 'high',
                    default          => 'average',
                };
            }

            // Outlier detection — fetch tagged estimates for this product/city
            $isOutlier = false;
            if ($estimate->city) {
                $tagged = $aggregator->dailySubmissions(
                    $estimate->product,
                    $currency,
                    30,
                    ['type' => PriceAggregator::SCOPE_CITY, 'id' => $estimate->city_id]
                )
                ->flatten(1)
                ->firstWhere('id', $estimate->id);

                $isOutlier = $tagged?->is_outlier ?? false;
            }

            $cooldownEndsAt = Carbon::parse($estimate->created_at)
                ->addDays(PriceEstimate::ESTIMATE_COOLDOWN_DAYS);

            return [
                'estimate'        => $estimate,
                'converted_price' => $convertedPrice,
                'city_average'    => $cityAvg,
                'deviation'       => $deviation,
                'position'        => $position,
                'is_outlier'      => $isOutlier,
                'cooldown_ends'   => $cooldownEndsAt,
                'symbol'          => $currency->symbol,
            ];
        });
    }

    /**
     * City vs country vs global comparison for the selected product.
     */
    public function getComparisonProperty(): ?array
    {
        if (!$this->comparisonProductId) return null;

        $user       = auth()->user();
        $currency   = $user->effectiveCurrency();
        $product    = Product::with('unit')->find($this->comparisonProductId);
        $aggregator = app(PriceAggregator::class);

        if (!$product || !$user->city) return null;

        $cityAvg    = $aggregator->cityAverage($product, $user->city, $currency, 30);
        $countryAvg = $aggregator->countryAverage($product, $user->city->country, $currency, 30);
        $globalAvg  = $aggregator->globalAverage($product, $currency, 30);

        if ($cityAvg === null && $countryAvg === null && $globalAvg === null) return null;

        // Compute how city compares to country and global as percentages
        $vsCountry = ($cityAvg !== null && $countryAvg !== null && $countryAvg > 0)
            ? (($cityAvg - $countryAvg) / $countryAvg) * 100
            : null;

        $vsGlobal = ($cityAvg !== null && $globalAvg !== null && $globalAvg > 0)
            ? (($cityAvg - $globalAvg) / $globalAvg) * 100
            : null;

        return [
            'product'    => $product,
            'city'       => $user->city,
            'country'    => $user->city->country,
            'city_avg'   => $cityAvg,
            'country_avg'=> $countryAvg,
            'global_avg' => $globalAvg,
            'vs_country' => $vsCountry,
            'vs_global'  => $vsGlobal,
            'symbol'     => $currency->symbol,
        ];
    }

    public function deleteEstimate(int $id): void
    {
        $estimate = PriceEstimate::where('id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $estimate->delete();
    }

    public function render()
    {
        $user           = auth()->user();
        $totalEstimates = PriceEstimate::where('user_id', $user->id)->count();
        $categories     = Category::withSortedProducts();

        return view('livewire.dashboard', [
            'user'            => $user,
            'totalEstimates'  => $totalEstimates,
            'categories'      => $categories,
            'recentEstimates' => $this->recentEstimates,
            'comparison'      => $this->comparison,
        ])->layout('layouts.app');
    }
}