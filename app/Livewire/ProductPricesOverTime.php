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

    /**
     * The current user's own non-deleted estimates for this product+period+scope,
     */
    public function getMySubmissionsProperty(): Collection
    {
        $user     = auth()->user();
        $currency = $user->effectiveCurrency();
        $cityId   = $this->allCities ? null : $user->city_id;

        return PriceEstimate::where('product_id', $this->product->id)
            ->where('user_id', $user->id)
            ->when($this->days > 0, fn($q) => $q->where('recorded_at', '>=', Carbon::now()->subDays($this->days)))
            ->when($cityId, fn($q) => $q->where('city_id', $cityId))
            ->with('currency')
            ->oldest('recorded_at')
            ->get()
            ->map(fn($e) => [
                'date'  => $e->recorded_at->toDateString(),
                'price' => round($e->currency->convert($e->price, $currency) ?? 0, 2),
            ])
            ->keyBy('date');
    }

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
            rows:          $this->rows->values()->toArray(),
            categoryColor: $this->product->category->color,
            mySubmissions: $this->mySubmissions->toArray(),
        );
    }

    public function updatedAllCities(): void
    {
        $this->dispatch('chart-data-updated',
            rows:          $this->rows->values()->toArray(),
            categoryColor: $this->product->category->color,
            mySubmissions: $this->mySubmissions->toArray(),
        );
    }

    public function render()
    {
        return view('livewire.product-prices-over-time', [
            'rows'          => $this->rows,
            'mySubmissions' => $this->mySubmissions,
        ]);
    }

    #[On('estimate-changed')]
    public function refresh(): void
    {
        $this->dispatch('chart-data-updated',
            rows:          $this->rows->values()->toArray(),
            categoryColor: $this->product->category->color,
            mySubmissions: $this->mySubmissions->toArray(),
        );
    }
}