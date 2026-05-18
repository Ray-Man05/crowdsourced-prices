<?php

namespace App\Livewire;

use App\Livewire\Concerns\HasBasket;
use App\Models\Category;
use App\Models\City;
use App\Models\Product;
use App\Models\UserBasket;
use Livewire\Component;

class MapPage extends Component
{
    use HasBasket;

    public int    $selectedProductId = 0;
    public float  $selectedQuantity  = 1;
    public int    $days              = 365;
    public string $error             = '';
    public bool   $recomputeOnChange = false;
    public bool   $resultsStale      = false;

    public string $colorScale  = 'green_red';
    public string $colorMin    = '#22c55e';
    public string $colorMax    = '#ef4444';

    public array $colorScales = [
        'green_red'  => ['min' => '#22c55e', 'max' => '#ef4444', 'label' => 'Green → Red'],
        'blue_red'   => ['min' => '#3b82f6', 'max' => '#ef4444', 'label' => 'Blue → Red'],
        'yellow_red' => ['min' => '#eab308', 'max' => '#dc2626', 'label' => 'Yellow → Red'],
        'custom'     => ['min' => '#22c55e', 'max' => '#ef4444', 'label' => 'Custom'],
    ];

    public array $results = [];

    public function mount(): void
    {
        $this->syncBasketFromDb();
    }

    protected function getActiveBasket(): UserBasket
    {
        return UserBasket::firstOrCreate([
            'user_id' => auth()->id(),
            'type'    => 'map',
        ]);
    }

    // ── Extension hooks ────────────────────────────────────────────────────

    protected function afterItemAdded(int $productId): void
    {
        $this->dispatch('product-added-to-basket');

        if ($this->recomputeOnChange) {
            $this->compute();
        } elseif (!empty($this->results)) {
            $this->resultsStale = true;
        }
    }

    protected function afterItemRemoved(int $productId): void
    {
        if ($this->recomputeOnChange) {
            $this->compute();
        } elseif (!empty($this->results)) {
            $this->resultsStale = true;
        }
    }

    protected function afterBasketEmptied(): void
    {
        $this->results      = [];
        $this->resultsStale = false;
        $this->dispatch('markers-cleared');
    }

    // ── Public Livewire actions ────────────────────────────────────────────

    public function addToBasket(): void
    {
        if (!$this->selectedProductId || $this->selectedQuantity <= 0) {
            return;
        }

        $this->addItem($this->selectedProductId, $this->selectedQuantity);

        $this->selectedProductId = 0;
        $this->selectedQuantity  = 1;
    }

    public function removeFromBasket(int $productId): void
    {
        $this->removeItem($productId);
    }

    // ── Livewire lifecycle ─────────────────────────────────────────────────

    public function updatedDays(): void
    {
        if ($this->recomputeOnChange) {
            $this->compute();
        } elseif (!empty($this->results)) {
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
        $this->error   = '';
        $this->results = [];

        if (empty($this->basket)) {
            $this->error = __('Your basket is empty.');
            return;
        }

        $currency   = auth()->user()->effectiveCurrency();
        $products   = Product::findMany(array_column($this->basket, 'product_id'));
        $aggregator = app(\App\Services\PriceAggregator::class);

        // [cityId => [productId => averagePrice]] — one bulk DB query instead of N×M calls
        $metrics    = $aggregator->bulkMultiCityMetrics($products, $currency, $this->days);
        $productIds = $products->pluck('id')->all();

        // Keep only cities where every basket product has a price
        $completeCityIds = [];
        foreach ($metrics as $cityId => $productMap) {
            foreach ($productIds as $pid) {
                if (!isset($productMap[$pid])) {
                    continue 2;
                }
            }
            $completeCityIds[] = $cityId;
        }

        if (empty($completeCityIds)) {
            $this->error = __('No price data found for this basket.');
            return;
        }

        $cities  = City::with('country')->whereIn('id', $completeCityIds)->get()->keyBy('id');
        $results = [];

        foreach ($completeCityIds as $cityId) {
            $city       = $cities[$cityId];
            $productMap = $metrics[$cityId];
            $total      = 0.0;
            $breakdown  = [];

            foreach ($this->basket as $item) {
                $pid      = $item['product_id'];
                $avg      = $productMap[$pid];
                $subtotal = $avg * $item['quantity'];
                $total   += $subtotal;

                $breakdown[] = [
                    'name'     => $item['name'],
                    'unit'     => $item['unit'] ?? '',
                    'avg'      => round($avg, 2),
                    'qty'      => $item['quantity'],
                    'subtotal' => round($subtotal, 2),
                ];
            }

            $results[] = [
                'city_id'   => $city->id,
                'city_name' => $city->name,
                'country'   => $city->country->name,
                'lat'       => $city->lat,
                'lng'       => $city->lng,
                'total'     => round($total, 2),
                'symbol'    => $currency->symbol,
                'breakdown' => $breakdown,
            ];
        }

        $this->results = $results;
        $this->dispatch('markers-updated',
            results:  $results,
            colorMin: $this->colorMin,
            colorMax: $this->colorMax,
        );
    }

    public function updatedColorScale(): void
    {
        if ($this->colorScale !== 'custom') {
            $this->colorMin = $this->colorScales[$this->colorScale]['min'];
            $this->colorMax = $this->colorScales[$this->colorScale]['max'];
        }

        $this->redispatchMarkers();
    }

    public function updatedColorMin(): void { $this->redispatchMarkers(); }
    public function updatedColorMax(): void { $this->redispatchMarkers(); }

    private function redispatchMarkers(): void
    {
        if (!empty($this->results)) {
            $this->dispatch('markers-updated',
                results:  $this->results,
                colorMin: $this->colorMin,
                colorMax: $this->colorMax,
            );
        }
    }

    public function render()
    {
        return view('livewire.map-page', [
            'categories' => Category::withSortedProducts(),
        ])->layout('layouts.app');
    }
}
