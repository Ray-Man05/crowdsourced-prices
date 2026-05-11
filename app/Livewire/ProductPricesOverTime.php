<?php

namespace App\Livewire;

use App\Models\PriceEstimate;
use App\Models\Product;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\Attributes\On;
use App\Services\PriceAggregator;

class ProductPricesOverTime extends Component
{
    public Product $product;
    public int     $days     = 30;
    public bool    $allCities = false;

    public function getRowsProperty(): Collection
    {
        $currency = auth()->user()->effectiveCurrency();
        $cityId   = $this->allCities ? null : auth()->user()->city_id;


        $scope = $cityId
            ? ['type' => PriceAggregator::SCOPE_CITY, 'id' => $cityId]
            : ['type' => PriceAggregator::SCOPE_GLOBAL];

        return app(PriceAggregator::class)->dailyAverages(
            $this->product,
            $currency,
            $this->days,
            $scope,
        );
    }

    public function updatedDays(): void
    {
        $this->dispatch('chart-data-updated', 
        rows: $this->rows->values()->toArray(),
        categoryColor: $this->product->category->color,
        );
    }

    public function updatedAllCities(): void
    {
        $this->dispatch('chart-data-updated', 
        rows: $this->rows->values()->toArray(),
        categoryColor: $this->product->category->color,
        );
    }

    public function render()
    {
        return view('livewire.product-prices-over-time', [
            'rows' => $this->rows,
        ]);
    }

    #[On('estimate-changed')]
    public function refresh(): void
    {
        $this->dispatch('chart-data-updated',
            rows:          $this->rows->values()->toArray(),
            categoryColor: $this->product->category->color,
        );
    }
}