<?php

namespace App\Livewire;

use App\Models\Product;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;
use Livewire\Component;
use App\Services\PriceAggregator;

class ProductPricesByCity extends Component
{
    public Product $product;
    public string  $citySearch    = '';
    public string  $countrySearch = '';
    public string  $sortBy        = 'price';
    public string  $sortDir       = 'asc';
    public int     $days          = 365;

    public function toggleSort(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDir = $this->sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy  = $column;
            $this->sortDir = 'asc';
        }
    }

    public function getCityStatsProperty(): Collection
    {
        $currency   = auth()->user()->effectiveCurrency();
        $aggregator = app(PriceAggregator::class);

        $stats = $aggregator->cityBreakdown($this->product, $currency, $this->days)
            ->filter(function ($r) {
                if ($this->citySearch && !str_contains(
                    strtolower($r['city']->name),
                    strtolower($this->citySearch)
                )) {
                    return false;
                }
                if ($this->countrySearch && !str_contains(
                    strtolower($r['city']->country->name ?? ''),
                    strtolower($this->countrySearch)
                )) {
                    return false;
                }
                return true;
            });

        $sorted = match ($this->sortBy) {
            'name'        => $stats->sortBy(fn($r) => $r['city']->name, SORT_STRING, $this->sortDir === 'desc'),
            'submissions' => $stats->sortBy('submissions', SORT_NUMERIC, $this->sortDir === 'desc'),
            default       => $stats->sortBy('average', SORT_NUMERIC, $this->sortDir === 'desc'),
        };

        return $sorted->values();
    }

    public function render()
    {
        return view('livewire.product-prices-by-city', [
            'cityStats' => $this->cityStats,
            'currency'  => auth()->user()->effectiveCurrency(),
            'unit'      => $this->product->unit,
        ]);
    }

    #[On('estimate-changed')]
    public function refresh(): void {}
}
