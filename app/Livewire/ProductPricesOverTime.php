<?php

namespace App\Livewire;

use App\Models\PriceEstimate;
use App\Models\Product;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\Attributes\On;

class ProductPricesOverTime extends Component
{
    public Product $product;
    public int     $days     = 90;
    public bool    $allCities = false;

    public function getRowsProperty(): Collection
    {
        $currency = auth()->user()->effectiveCurrency();
        $userCity = auth()->user()->city_id;

        $query = PriceEstimate::where('product_id', $this->product->id)
            ->with('currency', 'city');

        if ($this->days > 0) {
            $query->where('recorded_at', '>=', Carbon::now()->subDays($this->days));
        }

        if (!$this->allCities && $userCity) {
            $query->where('city_id', $userCity);
        }

        return $query->get()
            ->groupBy(fn($e) => Carbon::parse($e->recorded_at)->toDateString())
            ->map(function (Collection $group) use ($currency) {
                $converted = $group
                    ->map(fn($e) => $e->currency->convert($e->price, $currency))
                    ->filter(fn($v) => $v !== null);

                return [
                    'date'        => $group->first()->recorded_at->toDateString(),
                    'average'     => $converted->isNotEmpty() ? round($converted->average(), 2) : null,
                    'submissions' => $group->count(),
                    'symbol'      => $currency->symbol,
                ];
            })
            ->filter(fn($r) => $r['average'] !== null)
            ->sortKeys()
            ->values();
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