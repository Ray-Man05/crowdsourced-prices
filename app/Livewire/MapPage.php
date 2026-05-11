<?php

namespace App\Livewire;

use App\Models\City;
use App\Models\Product;
use Livewire\Component;
use App\Models\Category;

class MapPage extends Component
{
    // Basket: array of [product_id => quantity]
    public array  $basket            = [];
    public int    $selectedProductId = 0;
    public float  $selectedQuantity  = 1;
    public int    $days              = 30;
    public string $error             = '';

    public string $colorScale  = 'green_red';
    public string $colorMin    = '#22c55e';
    public string $colorMax    = '#ef4444';

    public array $colorScales = [
        'green_red'  => ['min' => '#22c55e', 'max' => '#ef4444', 'label' => 'Green → Red'],
        'blue_red'   => ['min' => '#3b82f6', 'max' => '#ef4444', 'label' => 'Blue → Red'],
        'yellow_red' => ['min' => '#eab308', 'max' => '#dc2626', 'label' => 'Yellow → Red'],
        'custom'     => ['min' => '#22c55e', 'max' => '#ef4444', 'label' => 'Custom'],
    ];

    // Computed results pushed to the map via dispatch
    public array $results = [];

    public function addToBasket(): void
    {
        if (!$this->selectedProductId || $this->selectedQuantity <= 0) {
            return;
        }

        $id = $this->selectedProductId;

        if (isset($this->basket[$id])) {
            $this->basket[$id]['quantity'] += $this->selectedQuantity;
        } else {
            $product = Product::with('unit')->find($id);
            if (!$product) return;

            $this->basket[$id] = [
                'product_id' => $id,
                'name'       => $product->name,
                'unit'       => $product->unit?->symbol ?? '',
                'quantity'   => $this->selectedQuantity,
                'category_color' => $product->category?->color ?? '#ffffff'
            ];
        }

        $this->selectedProductId = 0;
        $this->selectedQuantity  = 1;
    }

    public function removeFromBasket(int $productId): void
    {
        unset($this->basket[$productId]);

        if (empty($this->basket)) {
            $this->results = [];
            $this->dispatch('markers-cleared');
        }
    }

    public function compute(): void
    {
        $this->error   = '';
        $this->results = [];

        if (empty($this->basket)) {
            $this->error = __('Your basket is empty.');
            return;
        }

        $currency = auth()->user()->effectiveCurrency();
        $cities   = City::with('country')->get();
        $products = Product::findMany(array_column($this->basket, 'product_id'));
        $results  = [];
        $aggregator = app(\App\Services\PriceAggregator::class);

        foreach ($cities as $city) {
            $total    = 0.0;

            foreach ($this->basket as $item) {
                $product = $products->find($item['product_id']);
                $avg = $aggregator->cityAverage($product, $city, $currency, $this->days);

                if ($avg === null) {
                    continue 2;
                }

                $total += $avg * $item['quantity'];
            }

            if ($total <= 0) continue;

            $results[] = [
                'city_id'   => $city->id,
                'city_name' => $city->name,
                'country'   => $city->country->name,
                'lat'       => $city->lat,
                'lng'       => $city->lng,
                'total'     => round($total, 2),
                'symbol'    => $currency->symbol
            ];
        }

        if (empty($results)) {
            $this->error = __('No price data found for this basket.');
            // return;
        }

        $this->results = $results;
        $this->dispatch('markers-updated',
            results:  $results,
            colorMin: $this->colorMin,
            colorMax: $this->colorMax,
        );
    }

    public function render()
    {
        return view('livewire.map-page', [
            'categories' => Category::withSortedProducts(),
        ])->layout('layouts.app');
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

}