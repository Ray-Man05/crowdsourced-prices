<?php

namespace App\Livewire;

use App\Models\Category;
use App\Models\PriceEstimate;
use App\Models\Product;
use App\Models\UserBasket;
use App\Services\PriceAggregator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Component;

class Dashboard extends Component
{
    public ?int $comparisonProductId = null;

    public string $activeSection = 'estimates';

    // Basket form state
    public bool $showBasketForm = false;

    public ?int $editingBasketId = null;

    public string $basketFormName = '';

    public string $basketFormColor = '#10b981';

    // Basket item management
    public ?int $openBasketId = null;

    public int $basketItemFormKey = 0;

    // Basket price view
    public ?int $selectedBasketId = null;

    public string $pricePeriod = '30';

    public function mount(): void
    {
        $user = auth()->user();
        if ($user->city_id) {
            // All estimates have valid product_id FK values, so the previous whereIn(Product::pluck('id'))
            // added no selectivity — it just ran a full Product::pluck('id') query for nothing.
            $this->comparisonProductId = PriceEstimate::where('city_id', $user->city_id)
                ->value('product_id');
        }

        if (! $this->comparisonProductId) {
            $this->comparisonProductId = Product::first()?->id;
        }
    }

    // ── Estimates section ─────────────────────────────────────────────────

    public function getRecentEstimatesProperty(): Collection
    {
        $user = auth()->user();
        $currency = $user->effectiveCurrency();

        $estimates = PriceEstimate::where('user_id', $user->id)
            ->where('created_at', '>=', Carbon::now()->subDays(PriceEstimate::ESTIMATE_COOLDOWN_DAYS))
            ->with(['product.category', 'product.unit', 'currency', 'city'])
            ->latest('created_at')
            ->get();

        if ($estimates->isEmpty() || ! $currency) {
            return collect();
        }

        $aggregator = app(PriceAggregator::class);

        // Build city averages in bulk: one bulkCityMetrics() call per unique city
        // instead of N individual cityAverage() calls (one per estimate row).
        // Most users have a single city so this typically becomes one bulk query.
        $cityAverages = [];
        foreach ($estimates->filter(fn ($e) => $e->city)->groupBy('city_id') as $cityId => $group) {
            $city     = $group->first()->city;
            $products = $group->map(fn ($e) => $e->product)->unique('id')->values();
            $metrics  = $aggregator->bulkCityMetrics($products, $city, $currency, 30);

            foreach ($metrics as $productId => $data) {
                $cityAverages[$cityId][$productId] = $data['average'] ?? null;
            }
        }

        return $estimates->map(function ($estimate) use ($currency, $cityAverages, $user) {
            $convertedPrice = $estimate->currency->convert($estimate->price, $currency);
            $cityAvg        = $cityAverages[$estimate->city_id][$estimate->product_id] ?? null;

            $isOutlier = $estimate->city
                && $cityAvg !== null
                && $convertedPrice !== null
                && $cityAvg > 0
                && ($convertedPrice < $cityAvg / 5 || $convertedPrice > $cityAvg * 5);

            $position  = null;
            $deviation = null;
            if ($cityAvg !== null && $convertedPrice !== null && $cityAvg > 0) {
                $deviation = (($convertedPrice - $cityAvg) / $cityAvg) * 100;
                $position  = match (true) {
                    $deviation < -10 => 'low',
                    $deviation > 10  => 'high',
                    default          => 'average',
                };
            }

            return [
                'estimate'         => $estimate,
                'converted_price'  => $convertedPrice,
                'city_average'     => $cityAvg,
                'deviation'        => $deviation,
                'position'         => $position,
                'is_outlier'       => $isOutlier,
                'cooldown_ends'    => Carbon::parse($estimate->created_at)->addDays(PriceEstimate::ESTIMATE_COOLDOWN_DAYS),
                'symbol'           => $currency->symbol,
                'city_mismatch'    => $estimate->city_id !== $user->city_id,
                'currency_mismatch'=> $estimate->currency_id !== $currency->id,
            ];
        });
    }

    public function getComparisonProperty(): ?array
    {
        if (! $this->comparisonProductId) {
            return null;
        }

        $user = auth()->user();
        $currency = $user->effectiveCurrency();
        $product = Product::with('unit')->find($this->comparisonProductId);
        $aggregator = app(PriceAggregator::class);

        if (! $product || ! $user->city) {
            return null;
        }

        $cityAvg = $aggregator->cityAverage($product, $user->city, $currency, 30);
        $countryAvg = $aggregator->countryAverage($product, $user->city->country, $currency, 30);
        $globalAvg = $aggregator->globalAverage($product, $currency, 30);

        if ($cityAvg === null && $countryAvg === null && $globalAvg === null) {
            return null;
        }

        $vsCountry = ($cityAvg !== null && $countryAvg !== null && $countryAvg > 0)
            ? (($cityAvg - $countryAvg) / $countryAvg) * 100
            : null;

        $vsGlobal = ($cityAvg !== null && $globalAvg !== null && $globalAvg > 0)
            ? (($cityAvg - $globalAvg) / $globalAvg) * 100
            : null;

        return [
            'product' => $product,
            'city' => $user->city,
            'country' => $user->city->country,
            'city_avg' => $cityAvg,
            'country_avg' => $countryAvg,
            'global_avg' => $globalAvg,
            'vs_country' => $vsCountry,
            'vs_global' => $vsGlobal,
            'symbol' => $currency->symbol,
        ];
    }

    public function getActivityMapProperty(): array
    {
        $since = Carbon::now()->subDays(364);

        return PriceEstimate::where('user_id', auth()->id())
            ->where('recorded_at', '>=', $since)
            ->selectRaw('DATE(recorded_at) as day, COUNT(*) as cnt')
            ->groupBy('day')
            ->pluck('cnt', 'day')
            ->map(fn ($c) => (int) $c)
            ->all();
    }

    public function setSection(string $section): void
    {
        if (! in_array($section, ['estimates', 'baskets'])) {
            return;
        }
        $this->activeSection = $section;
    }

    public function selectComparisonProduct(int $productId): void
    {
        $this->comparisonProductId = $productId;
    }

    public function deleteEstimate(int $id): void
    {
        $estimate = PriceEstimate::where('id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $estimate->delete();
    }

    // ── Baskets section ───────────────────────────────────────────────────

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
        if (! $user->city_id) {
            return [];
        }

        return PriceEstimate::where('city_id', $user->city_id)
            ->distinct()
            ->pluck('product_id')
            ->toArray();
    }

    public function openCreateBasket(): void
    {
        $this->editingBasketId = null;
        $this->basketFormName = '';
        $this->basketFormColor = '#10b981';
        $this->showBasketForm = true;
    }

    public function cancelBasketForm(): void
    {
        $this->showBasketForm = false;
        $this->editingBasketId = null;
        $this->basketFormName = '';
    }

    public function updateBasket(int $id, string $name, string $color): void
    {
        $name = trim($name);
        if (empty($name) || mb_strlen($name) > 80) {
            return;
        }

        UserBasket::where('id', $id)
            ->where('user_id', auth()->id())
            ->where('type', 'saved')
            ->update(['name' => $name, 'color' => $color]);
    }

    public function editBasket(int $id): void
    {
        $basket = UserBasket::where('user_id', auth()->id())
            ->where('type', 'saved')
            ->findOrFail($id);

        $this->editingBasketId = $id;
        $this->basketFormName = $basket->name ?? '';
        $this->basketFormColor = $basket->color ?? '#10b981';
        $this->showBasketForm = true;
    }

    public function saveBasket(): void
    {
        $this->validate(['basketFormName' => 'required|string|max:80']);

        $data = [
            'name' => trim($this->basketFormName),
            'color' => $this->basketFormColor,
            'type' => 'saved',
        ];

        if ($this->editingBasketId) {
            UserBasket::where('id', $this->editingBasketId)
                ->where('user_id', auth()->id())
                ->update($data);
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

        if ($this->openBasketId === $id) {
            $this->openBasketId = null;
        }
        if ($this->selectedBasketId === $id) {
            $this->selectedBasketId = null;
        }
    }

    public function selectBasketForPricing(int $id): void
    {
        $this->selectedBasketId = ($this->selectedBasketId === $id) ? null : $id;
    }

    public function setPricePeriod(string $period): void
    {
        if (! in_array($period, ['30', '90', '365', '0'])) {
            return;
        }
        $this->pricePeriod = $period;
    }

    public function getBasketPriceProperty(): ?array
    {
        if (! $this->selectedBasketId) {
            return null;
        }

        $user = auth()->user();
        if (! $user->city_id) {
            return null;
        }

        // getBasketsProperty() already loaded all baskets with the same eager-load chain.
        // Re-using that result avoids a redundant DB query + full relation hydration.
        $basket = $this->baskets->firstWhere('id', $this->selectedBasketId);

        if (! $basket || $basket->items->isEmpty()) {
            return null;
        }

        $currency = $user->effectiveCurrency();
        $aggregator = app(PriceAggregator::class);
        $days = (int) $this->pricePeriod;

        // Single bulk call (version-cached) instead of N individual cityAverage() calls.
        $products = $basket->items->map(fn ($i) => $i->product)->filter();
        $metrics = $aggregator->bulkCityMetrics($products, $user->city, $currency, $days);

        $total = 0.0;
        $breakdown = [];
        $missing = [];

        foreach ($basket->items as $item) {
            $avg = $metrics[$item->product_id]['average'] ?? null;

            if ($avg === null) {
                $missing[] = $item;
            } else {
                $subtotal = round($avg * (float) $item->quantity, 2);
                $total += $subtotal;
                $breakdown[] = [
                    'name' => $item->product->name,
                    'unit' => $item->product->unit?->symbol ?? '',
                    'category_color' => $item->product->category?->color ?? '#9ca3af',
                    'quantity' => (float) $item->quantity,
                    'avg' => round($avg, 2),
                    'subtotal' => $subtotal,
                ];
            }
        }

        return [
            'basket' => $basket,
            'city' => $user->city,
            'symbol' => $currency->symbol,
            'total' => round($total, 2),
            'breakdown' => $breakdown,
            'missing' => $missing,
            'complete' => empty($missing),
            'days' => $days,
        ];
    }

    public function toggleBasket(int $id): void
    {
        $this->openBasketId = ($this->openBasketId === $id) ? null : $id;
        $this->basketItemFormKey++;
    }

    public function addItemToBasket(int $basketId, int $productId, float $qty): void
    {
        if (! $productId || $qty <= 0) {
            return;
        }

        UserBasket::where('id', $basketId)
            ->where('user_id', auth()->id())
            ->firstOrFail()
            ->addItem($productId, round($qty, 2));

        $this->basketItemFormKey++;
    }

    public function removeItemFromBasket(int $basketId, int $productId): void
    {
        UserBasket::where('id', $basketId)
            ->where('user_id', auth()->id())
            ->firstOrFail()
            ->removeItem($productId);
    }

    // ── Render ────────────────────────────────────────────────────────────

    public function render()
    {
        $user = auth()->user();
        $isEstimates = $this->activeSection === 'estimates';
        $isBaskets = $this->activeSection === 'baskets';

        // Categories are pushed to window.__dashCategories via @push('scripts') on the
        // initial full-page load. Livewire AJAX responses do NOT include @push content,
        // so Alpine on the client reads from the already-set window global on re-renders.
        // Sending an empty collection on AJAX skips the query + large JSON serialization
        // on every tab switch — the same pattern used by CityComparison.
        $isAjax = request()->header('X-Livewire') !== null;
        $categories = $isAjax ? collect() : Category::withSortedProducts();

        return view('livewire.dashboard', [
            'user' => $user,
            'categories' => $categories,
            'totalEstimates' => $isEstimates ? PriceEstimate::where('user_id', $user->id)->count() : 0,
            'recentEstimates' => $isEstimates ? $this->recentEstimates : collect(),
            'comparison' => $isEstimates ? $this->comparison : null,
            'activityMap' => $isEstimates ? $this->activityMap : [],
            'baskets' => $isBaskets ? $this->baskets : collect(),
            'cityProductIds' => $isBaskets ? $this->cityProductIds : [],
            'basketPrice' => ($isBaskets && $this->selectedBasketId && $user->city_id)
                                     ? $this->basketPrice
                                     : null,
        ])->layout('layouts.app');
    }
}
