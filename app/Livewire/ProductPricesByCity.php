<?php

namespace App\Livewire;

use App\Models\PriceEstimate;
use App\Models\Product;
use Illuminate\Support\Collection;
use Livewire\Component;

    use Livewire\Attributes\On;
class ProductPricesByCity extends Component
{
    public Product $product;
    public string  $citySearch = '';
    public string  $sortBy     = 'price';
    public string  $sortDir    = 'asc';

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
        $currency  = auth()->user()->effectiveCurrency();

        $estimates = PriceEstimate::where('product_id', $this->product->id)
            ->with('currency', 'city.country')
            ->get();

        $stats = $estimates
            ->groupBy('city_id')
            ->map(function (Collection $group) use ($currency) {
                $converted = $group
                    ->map(fn($e) => $e->currency->convert($e->price, $currency))
                    ->filter(fn($v) => $v !== null);

                return [
                    'city'        => $group->first()->city,
                    'average'     => $converted->isNotEmpty() ? round($converted->average(), 2) : null,
                    'submissions' => $group->count(),
                    'symbol'      => $currency->symbol,
                ];
            })
            ->filter(fn($r) => $r['average'] !== null)
            ->filter(fn($r) => !$this->citySearch || str_contains(
                strtolower($r['city']->name),
                strtolower($this->citySearch)
            ));

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
