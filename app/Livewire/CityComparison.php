<?php

namespace App\Livewire;

use App\Models\Category;
use App\Models\City;
use App\Models\Country;
use App\Models\PriceEstimate;
use App\Models\Product;
use App\Models\UserBasket;
use App\Services\PriceAggregator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

class CityComparison extends Component
{
    // City A
    public ?int $cityAId        = null;
    public ?int $cityACountryId = null;

    // City B
    public ?int $cityBId        = null;
    public ?int $cityBCountryId = null;

    public string $days           = '30';
    public bool   $showComparison = false;

    // Basket state
    public ?int   $openBasketId      = null;
    public int    $basketItemFormKey = 0;
    public bool   $showBasketForm    = false;
    public ?int   $editingBasketId   = null;
    public string $basketFormName    = '';

    /** Per-request in-memory cache. Not serialised by Livewire. */
    private array      $metricsCache = [];
    private ?Collection $allProducts = null;

    // ── Internal helpers ──────────────────────────────────────────────────────

    private function products(): Collection
    {
        return $this->allProducts ??= Product::all();
    }

    private function getMetrics(): array
    {
        if (!empty($this->metricsCache)) return $this->metricsCache;
        if (!$this->cityAId || !$this->cityBId) return [];

        $cityA = City::find($this->cityAId);
        $cityB = City::find($this->cityBId);
        if (!$cityA || !$cityB) return [];

        $this->metricsCache = app(PriceAggregator::class)->dualCityMetrics(
            $cityA, $cityB,
            $this->products(),
            auth()->user()->effectiveCurrency(),
            (int) $this->days,
        );

        return $this->metricsCache;
    }

    // ── City selection actions ────────────────────────────────────────────────

    /** Called by Alpine when the user picks a city. Sets both city + country in one round-trip. */
    public function selectCityA(int $cityId, int $countryId): void
    {
        $this->cityAId        = $cityId;
        $this->cityACountryId = $countryId;
        $this->showComparison = false;
    }

    public function selectCityB(int $cityId, int $countryId): void
    {
        $this->cityBId        = $cityId;
        $this->cityBCountryId = $countryId;
        $this->showComparison = false;
    }

    /** Called by Alpine when the user changes country AFTER a city was already selected. */
    public function clearCityA(?int $countryId = null): void
    {
        $this->cityAId        = null;
        $this->cityACountryId = $countryId;
        $this->showComparison = false;
    }

    public function clearCityB(?int $countryId = null): void
    {
        $this->cityBId        = null;
        $this->cityBCountryId = $countryId;
        $this->showComparison = false;
    }

    /**
     * Swap cities. Keeps the current comparison visible — results are
     * re-fetched from cache (normalised key), so this is near-instant.
     */
    public function swapCities(): void
    {
        [$this->cityAId,        $this->cityBId       ] = [$this->cityBId,        $this->cityAId       ];
        [$this->cityACountryId, $this->cityBCountryId] = [$this->cityBCountryId, $this->cityACountryId];
        // intentionally keep $showComparison unchanged
    }

    // ── Period ────────────────────────────────────────────────────────────────

    public function setDays(string $days): void
    {
        if (!in_array($days, ['30', '90', '365', '0'])) return;
        $this->days           = $days;
        $this->showComparison = false;
    }

    // ── Compute trigger ───────────────────────────────────────────────────────

    public function compute(): void
    {
        if ($this->cityAId && $this->cityBId) {
            $this->showComparison = true;
        }
    }

    // ── Computed: product comparison ──────────────────────────────────────────

    public function getComparisonProperty(): ?array
    {
        if (!$this->cityAId || !$this->cityBId || !$this->showComparison) return null;

        $cityA = City::with('country')->find($this->cityAId);
        $cityB = City::with('country')->find($this->cityBId);
        if (!$cityA || !$cityB) return null;

        $currency = auth()->user()->effectiveCurrency();
        $metrics  = $this->getMetrics();

        $categories = Category::with(['products' => fn($q) => $q->with('unit')->orderBy('name')])
            ->orderBy('name')
            ->get();

        $sections    = [];
        $productsInA = 0;
        $productsInB = 0;

        foreach ($categories as $category) {
            $rows = [];
            foreach ($category->products as $product) {
                $priceA = $metrics[$this->cityAId][$product->id] ?? null;
                $priceB = $metrics[$this->cityBId][$product->id] ?? null;

                if ($priceA === null && $priceB === null) continue;

                $delta = ($priceA !== null && $priceB !== null && $priceB > 0)
                    ? round((($priceA - $priceB) / $priceB) * 100, 1)
                    : null;

                if ($priceA !== null) $productsInA++;
                if ($priceB !== null) $productsInB++;

                $rows[] = [
                    'product' => $product,
                    'price_a' => $priceA !== null ? round($priceA, 2) : null,
                    'price_b' => $priceB !== null ? round($priceB, 2) : null,
                    'delta'   => $delta,
                ];
            }

            if (!empty($rows)) {
                $sections[] = ['category' => $category, 'rows' => $rows];
            }
        }

        return [
            'city_a'     => $cityA,
            'city_b'     => $cityB,
            'symbol'     => $currency->symbol,
            'sections'   => $sections,
            'products_a' => $productsInA,
            'products_b' => $productsInB,
        ];
    }

    // ── Computed: baskets ─────────────────────────────────────────────────────

    public function getBasketsProperty(): Collection
    {
        return UserBasket::where('user_id', auth()->id())
            ->where('type', 'saved')
            ->with(['items.product.unit', 'items.product.category'])
            ->latest()
            ->get();
    }

    public function getCityProductIdsProperty(): array
    {
        $user = auth()->user();
        if (!$user->city_id) return [];

        return PriceEstimate::where('city_id', $user->city_id)
            ->distinct()
            ->pluck('product_id')
            ->toArray();
    }

    public function getBasketComparisonProperty(): ?array
    {
        if (!$this->cityAId || !$this->cityBId || !$this->showComparison) return null;

        $cityA = City::find($this->cityAId);
        $cityB = City::find($this->cityBId);
        if (!$cityA || !$cityB) return null;

        $currency = auth()->user()->effectiveCurrency();
        $metrics  = $this->getMetrics();

        $results = [];
        foreach ($this->baskets as $basket) {
            $totalA    = 0.0;
            $totalB    = 0.0;
            $breakdown = [];

            foreach ($basket->items as $item) {
                $priceA = $metrics[$this->cityAId][$item->product_id] ?? null;
                $priceB = $metrics[$this->cityBId][$item->product_id] ?? null;
                $qty    = (float) $item->quantity;

                if ($priceA !== null) $totalA += $priceA * $qty;
                if ($priceB !== null) $totalB += $priceB * $qty;

                $breakdown[] = [
                    'product'        => $item->product,
                    'quantity'       => $qty,
                    'unit'           => $item->product->unit?->symbol ?? '',
                    'category_color' => $item->product->category?->color ?? '#9ca3af',
                    'price_a'        => $priceA !== null ? round($priceA, 2) : null,
                    'price_b'        => $priceB !== null ? round($priceB, 2) : null,
                    'subtotal_a'     => $priceA !== null ? round($priceA * $qty, 2) : null,
                    'subtotal_b'     => $priceB !== null ? round($priceB * $qty, 2) : null,
                ];
            }

            $delta = ($totalA > 0 && $totalB > 0)
                ? round((($totalA - $totalB) / $totalB) * 100, 1)
                : null;

            $results[] = [
                'basket'    => $basket,
                'total_a'   => round($totalA, 2),
                'total_b'   => round($totalB, 2),
                'delta'     => $delta,
                'breakdown' => $breakdown,
            ];
        }

        return [
            'city_a'  => $cityA,
            'city_b'  => $cityB,
            'symbol'  => $currency->symbol,
            'baskets' => $results,
        ];
    }

    // ── Basket CRUD ───────────────────────────────────────────────────────────

    public function toggleBasket(int $id): void
    {
        $this->openBasketId = ($this->openBasketId === $id) ? null : $id;
        $this->basketItemFormKey++;
    }

    public function addItemToBasket(int $basketId, int $productId, float $qty): void
    {
        if (!$productId || $qty <= 0) return;
        UserBasket::where('id', $basketId)->where('user_id', auth()->id())->firstOrFail()->addItem($productId, round($qty, 2));
        $this->basketItemFormKey++;
    }

    public function removeItemFromBasket(int $basketId, int $productId): void
    {
        UserBasket::where('id', $basketId)->where('user_id', auth()->id())->firstOrFail()->removeItem($productId);
        $this->basketItemFormKey++; // re-initialise the add-item form so removed product reappears
    }

    public function updateBasket(int $id, string $name, string $color): void
    {
        $name = trim($name);
        if (empty($name) || mb_strlen($name) > 80) return;
        UserBasket::where('id', $id)->where('user_id', auth()->id())->where('type', 'saved')
            ->update(['name' => $name, 'color' => $color]);
    }

    public function openCreateBasket(): void
    {
        $this->editingBasketId = null;
        $this->basketFormName  = '';
        $this->showBasketForm  = true;
    }

    public function cancelBasketForm(): void
    {
        $this->showBasketForm  = false;
        $this->editingBasketId = null;
        $this->basketFormName  = '';
    }

    /**
     * Color is managed by Alpine and passed as a parameter, avoiding a
     * separate Livewire round-trip for every color swatch click.
     */
    public function saveBasket(string $color = '#10b981'): void
    {
        $this->validate(['basketFormName' => 'required|string|max:80']);

        $data = ['name' => trim($this->basketFormName), 'color' => $color, 'type' => 'saved'];

        if ($this->editingBasketId) {
            UserBasket::where('id', $this->editingBasketId)->where('user_id', auth()->id())->update($data);
            $this->editingBasketId = null;
        } else {
            $basket = UserBasket::create([...$data, 'user_id' => auth()->id()]);
            $this->openBasketId = $basket->id;
        }

        $this->showBasketForm = false;
        $this->basketFormName = '';
    }

    public function deleteBasket(int $id): void
    {
        UserBasket::where('id', $id)->where('user_id', auth()->id())->delete();
        if ($this->openBasketId === $id) $this->openBasketId = null;
    }

    // ── Render ────────────────────────────────────────────────────────────────

    public function render()
    {
        $bothSelected = $this->cityAId && $this->cityBId;

        // On initial full-page renders, push the static city/category data as
        // window globals. Livewire AJAX updates do NOT include @push content
        // in their responses, so the large JSON is transmitted only once.
        $isAjax  = request()->header('X-Livewire') !== null;
        $cities          = $isAjax ? collect() : City::orderBy('name')->get(['id', 'name', 'country_id']);
        $citiesWithData  = $isAjax ? [] : Cache::remember('cities_with_any_data', 3600, function () {
            return PriceEstimate::distinct()->pluck('city_id')
                ->mapWithKeys(fn($id) => [$id => true])
                ->toArray();
        });
        $categories = $isAjax ? collect() : Category::withSortedProducts();

        return view('livewire.city-comparison', [
            'cities'           => $cities,
            'citiesWithData'   => $citiesWithData,
            'categories'       => $categories,
            'countries'        => Country::orderBy('name')->get(),
            'cityAName'        => $this->cityAId ? (City::find($this->cityAId)?->name ?? '') : '',
            'cityBName'        => $this->cityBId ? (City::find($this->cityBId)?->name ?? '') : '',
            'canCompute'       => $bothSelected && !$this->showComparison,
            'showComparison'   => $this->showComparison,
            'comparison'       => ($bothSelected && $this->showComparison) ? $this->comparison       : null,
            'baskets'          => $this->baskets,
            'basketComparison' => ($bothSelected && $this->showComparison) ? $this->basketComparison : null,
            'cityProductIds'   => $this->cityProductIds,
        ])->layout('layouts.app');
    }
}
