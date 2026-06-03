<?php

namespace App\Livewire;

use App\Livewire\Concerns\HasBasket;
use App\Models\Category;
use App\Models\City;
use App\Models\Product;
use App\Models\UserBasket;
use App\Services\PriceAggregator;
use Livewire\Component;

class MapPage extends Component
{
    use HasBasket;

    public string $mapMode = 'price';

    public string $coverageMetric = 'estimates';

    public array $modes = [
        'price' => 'Price',
        'coverage' => 'Coverage',
    ];

    public int $selectedProductId = 0;

    public float $selectedQuantity = 1;

    public int $days = 365;

    public string $error = '';

    public bool $recomputeOnChange = true;

    public bool $resultsStale = false;

    public string $colorScale = 'green_red';

    public string $colorMin = '#22c55e';

    public string $colorMax = '#ef4444';

    public array $colorScales = [
        'green_red' => ['min' => '#22c55e', 'max' => '#ef4444', 'label' => 'Green → Red'],
        'blue_red' => ['min' => '#3b82f6', 'max' => '#ef4444', 'label' => 'Blue → Red'],
        'yellow_red' => ['min' => '#eab308', 'max' => '#dc2626', 'label' => 'Yellow → Red'],
        'custom' => ['min' => '#22c55e', 'max' => '#ef4444', 'label' => 'Custom'],
    ];

    public array $results = [];

    public array $coverageSummary = [];

    public function mount(): void
    {
        $this->syncBasketFromDb();
    }

    protected function getActiveBasket(): UserBasket
    {
        return UserBasket::firstOrCreate([
            'user_id' => auth()->id(),
            'type' => 'map',
        ]);
    }

    // ── Extension hooks ────────────────────────────────────────────────────

    protected function afterItemAdded(int $productId): void
    {
        $this->dispatch('product-added-to-basket');

        if ($this->mapMode !== 'price') {
            return;
        }

        if ($this->recomputeOnChange) {
            $this->compute();
        } elseif (! empty($this->results)) {
            $this->resultsStale = true;
        }
    }

    protected function afterItemRemoved(int $productId): void
    {
        if ($this->mapMode !== 'price') {
            return;
        }

        if ($this->recomputeOnChange) {
            $this->compute();
        } elseif (! empty($this->results)) {
            $this->resultsStale = true;
        }
    }

    protected function afterBasketEmptied(): void
    {
        $this->results = [];
        $this->coverageSummary = [];
        $this->resultsStale = false;
        $this->dispatch('markers-cleared');
    }

    // ── Public Livewire actions ────────────────────────────────────────────

    public function addToBasket(float $quantity = 0): void
    {
        if (! $this->selectedProductId) {
            return;
        }

        $qty = $quantity > 0 ? $quantity : $this->selectedQuantity;
        if ($qty <= 0) {
            return;
        }

        $this->addItem($this->selectedProductId, $qty);

        $this->selectedProductId = 0;
        $this->selectedQuantity = 1;
    }

    public function removeFromBasket(int $productId): void
    {
        $this->removeItem($productId);
    }

    public function importFromSavedBasket(int $basketId): void
    {
        $saved = UserBasket::where('id', $basketId)
            ->where('user_id', auth()->id())
            ->where('type', 'saved')
            ->with(['items.product.unit', 'items.product.category'])
            ->first();

        if (! $saved) {
            return;
        }

        foreach ($saved->items as $item) {
            $this->addItem($item->product_id, (float) $item->quantity);
        }
    }

    // ── Livewire lifecycle ─────────────────────────────────────────────────

    public function updatedMapMode(): void
    {
        $this->results = [];
        $this->coverageSummary = [];
        $this->resultsStale = false;
        $this->error = '';
        $this->dispatch('markers-cleared');
    }

    public function updatedCoverageMetric(): void
    {
        if ($this->recomputeOnChange && ! empty($this->results)) {
            $this->compute();
        } elseif (! empty($this->results)) {
            $this->resultsStale = true;
            $this->coverageSummary = [];
        }
    }

    public function updatedDays(): void
    {
        if ($this->recomputeOnChange) {
            $this->compute();
        } elseif (! empty($this->results)) {
            $this->resultsStale = true;
        }
    }

    public function updatedRecomputeOnChange(): void
    {
        if ($this->recomputeOnChange && $this->resultsStale) {
            $this->compute();
        }
    }

    public function compute(): void
    {
        $this->resultsStale = false;
        $this->error = '';
        $this->results = [];

        if ($this->mapMode === 'coverage') {
            $this->computeCoverage();
        } else {
            $this->computePrice();
        }
    }

    // ── Private compute methods ────────────────────────────────────────────

    private function computePrice(): void
    {
        if (empty($this->basket)) {
            $this->error = __('Your basket is empty.');

            return;
        }

        $currency = auth()->user()->effectiveCurrency();
        $products = Product::findMany(array_column($this->basket, 'product_id'));
        $aggregator = app(PriceAggregator::class);

        $metrics = $aggregator->bulkMultiCityMetrics($products, $currency, $this->days);
        $productIds = $products->pluck('id')->all();

        $completeCityIds = [];
        foreach ($metrics as $cityId => $productMap) {
            foreach ($productIds as $pid) {
                if (! isset($productMap[$pid])) {
                    continue 2;
                }
            }
            $completeCityIds[] = $cityId;
        }

        if (empty($completeCityIds)) {
            $this->error = __('No price data found for this basket.');

            return;
        }

        $cities = City::with('country')->whereIn('id', $completeCityIds)->get()->keyBy('id');
        $results = [];

        foreach ($completeCityIds as $cityId) {
            $city = $cities[$cityId];
            $productMap = $metrics[$cityId];
            $total = 0.0;
            $breakdown = [];

            foreach ($this->basket as $item) {
                $pid = $item['product_id'];
                $avg = $productMap[$pid];
                $subtotal = $avg * $item['quantity'];
                $total += $subtotal;

                $breakdown[] = [
                    'name' => $item['name'],
                    'unit' => $item['unit'] ?? '',
                    'avg' => round($avg, 2),
                    'qty' => $item['quantity'],
                    'subtotal' => round($subtotal, 2),
                ];
            }

            $total = round($total, 2);
            $results[] = [
                'city_id' => $city->id,
                'city_name' => $city->name,
                'country' => $city->country->name,
                'lat' => $city->lat,
                'lng' => $city->lng,
                'value' => $total,
                'popup_html' => $this->buildPricePopup(
                    $city->name, $city->country->name, $breakdown, $total, $currency->symbol
                ),
            ];
        }

        $this->results = $results;
        $this->dispatch('markers-updated',
            results: $results,
            colorMin: $this->colorMin,
            colorMax: $this->colorMax,
        );
    }

    private function computeCoverage(): void
    {
        $this->coverageSummary = [];

        if ($this->coverageMetric === 'products') {
            $this->computeCoverageByProducts();
        } else {
            $this->computeCoverageByEstimates();
        }
    }

    private function computeCoverageByEstimates(): void
    {
        $rows = app(PriceAggregator::class)->coverageByCity($this->days);

        if ($rows->isEmpty()) {
            $this->error = __('No data for this period');

            return;
        }

        $results = $rows->map(fn ($row) => [
            'city_id' => $row['city_id'],
            'city_name' => $row['city_name'],
            'country' => $row['country'],
            'lat' => $row['lat'],
            'lng' => $row['lng'],
            'value' => $row['count'],
            'popup_html' => $this->buildCoveragePopup($row['city_name'], $row['country'], $row['count'], 'estimates'),
        ])->values()->all();

        $this->results = $results;
        $this->dispatch('markers-updated',
            results: $results,
            colorMin: $this->colorMin,
            colorMax: $this->colorMax,
        );
    }

    private function computeCoverageByProducts(): void
    {
        $currency = auth()->user()->effectiveCurrency();
        $products = Product::all();
        $stats = app(PriceAggregator::class)->bulkCoverageByProduct($currency, $products, $this->days);

        if (empty($stats['city_product_counts'])) {
            $this->error = __('No data for this period');

            return;
        }

        $cities = City::with('country')
            ->whereIn('id', array_keys($stats['city_product_counts']))
            ->get()
            ->keyBy('id');

        $results = [];
        foreach ($stats['city_product_counts'] as $cityId => $productCount) {
            $city = $cities[$cityId] ?? null;
            if (! $city) {
                continue;
            }
            $results[] = [
                'city_id' => $city->id,
                'city_name' => $city->name,
                'country' => $city->country->name,
                'lat' => (float) $city->lat,
                'lng' => (float) $city->lng,
                'value' => $productCount,
                'popup_html' => $this->buildCoveragePopup($city->name, $city->country->name, $productCount, 'products'),
            ];
        }

        if (empty($results)) {
            $this->error = __('No data for this period');

            return;
        }

        // Cities considered "fully covered" = have clean data for every tracked product.
        $totalProducts = $stats['total_products'];
        $fullCoverage = count(array_filter(
            $stats['city_product_counts'],
            fn ($count) => $count === $totalProducts
        ));

        // Top 5 products by number of cities with clean data.
        $productCounts = $stats['product_city_counts'];
        arsort($productCounts);
        $top5Ids = array_slice(array_keys($productCounts), 0, 5);
        $top5Products = Product::findMany($top5Ids)
            ->sortBy(fn ($p) => array_search($p->id, $top5Ids))
            ->map(fn ($p) => ['name' => $p->name, 'city_count' => $productCounts[$p->id]])
            ->values()
            ->all();

        $this->results = $results;
        $this->coverageSummary = [
            'full_coverage_cities' => $fullCoverage,
            'total_products' => $totalProducts,
            'top_products' => $top5Products,
        ];

        $this->dispatch('markers-updated',
            results: $results,
            colorMin: $this->colorMin,
            colorMax: $this->colorMax,
        );
    }

    // ── Popup builders ─────────────────────────────────────────────────────

    private function buildPricePopup(string $cityName, string $country, array $breakdown, float $total, string $symbol): string
    {
        $city = htmlspecialchars($cityName);
        $ctry = htmlspecialchars($country);
        $rows = '';

        foreach ($breakdown as $b) {
            $qty = $b['unit'] ? "{$b['qty']} {$b['unit']}" : (string) $b['qty'];
            $name = htmlspecialchars($b['name']);
            $sub = number_format($b['subtotal'], 2);
            $rows .= "<tr><td style=\"padding:2px 10px 2px 0;white-space:nowrap\">{$name} ×{$qty}</td>"
                   ."<td style=\"padding:2px 0;text-align:right;white-space:nowrap\">{$symbol}{$sub}</td></tr>";
        }

        $totalFmt = $symbol.number_format($total, 2);

        if ($rows) {
            return "<strong>{$city}</strong>, {$ctry}"
                 ."<table style=\"border-collapse:collapse;margin-top:6px;font-size:12px\">{$rows}"
                 .'<tr><td colspan="2" style="border-top:1px solid #ddd;padding-top:3px"></td></tr>'
                 .'<tr style="font-weight:600"><td style="padding:2px 10px 2px 0">Total</td>'
                 ."<td style=\"padding:2px 0;text-align:right\">{$totalFmt}</td></tr></table>";
        }

        return "<strong>{$city}</strong>, {$ctry}<br><strong>{$totalFmt}</strong>";
    }

    private function buildCoveragePopup(string $cityName, string $country, int $value, string $metric): string
    {
        $city = htmlspecialchars($cityName);
        $ctry = htmlspecialchars($country);
        $label = $metric === 'products' ? __('Products') : __('Submissions');

        return "<strong>{$city}</strong>, {$ctry}<br><span style=\"font-size:13px\">{$value} {$label}</span>";
    }

    // ── Color scale ────────────────────────────────────────────────────────

    public function updatedColorScale(): void
    {
        if ($this->colorScale !== 'custom') {
            $this->colorMin = $this->colorScales[$this->colorScale]['min'];
            $this->colorMax = $this->colorScales[$this->colorScale]['max'];
        }

        $this->redispatchMarkers();
    }

    public function updatedColorMin(): void
    {
        $this->redispatchMarkers();
    }

    public function updatedColorMax(): void
    {
        $this->redispatchMarkers();
    }

    private function redispatchMarkers(): void
    {
        if (! empty($this->results)) {
            $this->dispatch('markers-updated',
                results: $this->results,
                colorMin: $this->colorMin,
                colorMax: $this->colorMax,
            );
        }
    }

    public function render()
    {
        return view('livewire.map-page', [
            'categories' => Category::withSortedProducts(),
            'savedBaskets' => UserBasket::where('user_id', auth()->id())
                ->where('type', 'saved')
                ->withCount('items')
                ->orderBy('name')
                ->get(),
        ])->layout('layouts.app');
    }
}
