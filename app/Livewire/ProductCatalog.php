<?php

namespace App\Livewire;

use App\Models\Category;
use App\Models\City;
use App\Models\Currency;
use App\Models\Product;
use App\Services\PriceAggregator;
use Illuminate\Support\Collection;
use Livewire\Component;

class ProductCatalog extends Component
{
    public string $search           = '';
    public array  $selectedCategories = [];
    public int    $days             = 30;
    public ?int   $cityId           = null;

    public function mount(): void
    {
        $user         = auth()->user();
        $this->cityId = $user->city_id;
        $this->days   = 30;
    }

    public function updatedSearch(): void
    {
        // Intentionally empty — triggers re-render automatically
    }

    public function toggleCategory(int $categoryId): void
    {
        if (in_array($categoryId, $this->selectedCategories)) {
            $this->selectedCategories = array_values(
                array_filter($this->selectedCategories, fn($id) => $id !== $categoryId)
            );
        } else {
            $this->selectedCategories[] = $categoryId;
        }
    }

    public function clearFilters(): void
    {
        $this->search             = '';
        $this->selectedCategories = [];
    }

    /**
     * Products filtered by search and selected categories,
     * with their category eager-loaded.
     */
    public function getFilteredProductsProperty(): Collection
    {
        return Product::with(['category', 'unit'])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$.en'))) LIKE ?", ['%' . strtolower($this->search) . '%'])
                      ->orWhereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$.fr'))) LIKE ?", ['%' . strtolower($this->search) . '%']);
                });
            })
            ->when($this->selectedCategories, fn($query) =>
                $query->whereIn('category_id', $this->selectedCategories)
            )
            ->get();
    }

    public function render()
    {
        $city     = $this->cityId ? City::find($this->cityId) : null;
        $currency = auth()->user()->effectiveCurrency();
        $products = $this->filteredProducts;

        $bulkMetrics = ($city && $currency)
            ? app(PriceAggregator::class)->bulkCityMetrics($products, $city, $currency, $this->days)
            : [];

        return view('livewire.product-catalog', [
            'products'    => $products,
            'bulkMetrics' => $bulkMetrics,
            'categories'  => Category::orderBy('name')->get(),
            'city'        => $city,
            'currency'    => $currency,
            'days'        => $this->days,
        ])->layout('layouts.app');
    }
}
