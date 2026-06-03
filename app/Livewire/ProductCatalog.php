<?php

namespace App\Livewire;

use App\Models\Category;
use App\Models\City;
use App\Models\Product;
use App\Services\PriceAggregator;
use Livewire\Component;

class ProductCatalog extends Component
{
    public int $days = 30;

    public ?int $cityId = null;

    public function mount(): void
    {
        $this->cityId = auth()->user()->city_id;
    }

    public function render()
    {
        $user      = auth()->user();
        $city      = $this->cityId ? City::find($this->cityId) : null;
        $currency  = $user->effectiveCurrency();
        $aggregator = app(PriceAggregator::class);

        // Load all products once — filtering is done client-side by Alpine.
        // Sorted by category name → product name (both English) entirely in PHP
        // so the DB order doesn't matter and SQL JSON functions aren't needed.
        $products = Product::with(['category', 'unit'])->get()
            ->sortBy([
                fn ($p) => $p->category->getRawTranslations('name')['en'] ?? '',
                fn ($p) => $p->getRawTranslations('name')['en'] ?? '',
            ])
            ->values();

        $bulkMetrics = ($city && $currency)
            ? $aggregator->bulkCityMetrics($products, $city, $currency, $this->days)
            : [];

        $userStatuses = $currency
            ? $aggregator->bulkUserStatuses($products, $currency, $user->id)
            : [];

        return view('livewire.product-catalog', [
            'products'     => $products,
            'bulkMetrics'  => $bulkMetrics,
            'userStatuses' => $userStatuses,
            'categories'   => Category::orderBy('name')->get(),
            'city'         => $city,
            'currency'     => $currency,
            'days'         => $this->days,
        ])->layout('layouts.app');
    }
}
