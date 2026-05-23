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
    public ?int   $comparisonProductId = null;
    public string $activeSection       = 'estimates';

    // Basket form state
    public bool   $showBasketForm  = false;
    public ?int   $editingBasketId = null;
    public string $basketFormName  = '';
    public string $basketFormColor = '#10b981';

    // Basket item management
    public ?int   $openBasketId      = null;
    public int    $basketItemFormKey = 0;

    // Basket price view
    public ?int   $selectedBasketId = null;
    public string $pricePeriod      = '30';

    public function mount(): void
    {
        $user = auth()->user();
        if ($user->city_id) {
            $this->comparisonProductId = PriceEstimate::where('city_id', $user->city_id)
                ->whereIn('product_id', Product::pluck('id'))
                ->value('product_id');
        }

        if (!$this->comparisonProductId) {
            $this->comparisonProductId = Product::first()?->id;
        }
    }

    // ── Estimates section ─────────────────────────────────────────────────

    public function getRecentEstimatesProperty(): Collection
    {
        $user       = auth()->user();
        $currency   = $user->effectiveCurrency();
        $aggregator = app(PriceAggregator::class);

        $estimates = PriceEstimate::where('user_id', $user->id)
            ->where('created_at', '>=', Carbon::now()->subDays(PriceEstimate::ESTIMATE_COOLDOWN_DAYS))
            ->with(['product.category', 'product.unit', 'currency', 'city'])
            ->latest('created_at')
            ->get();

        return $estimates->map(function ($estimate) use ($currency, $aggregator) {
            $cityAvg = $estimate->city
                ? $aggregator->cityAverage($estimate->product, $estimate->city, $currency, 30)
                : null;

            $convertedPrice = $estimate->currency->convert($estimate->price, $currency);

            $position = null;
            $deviation = null;
            if ($cityAvg !== null && $convertedPrice !== null && $cityAvg > 0) {
                $deviation = (($convertedPrice - $cityAvg) / $cityAvg) * 100;
                $position  = match (true) {
                    $deviation < -10 => 'low',
                    $deviation > 10  => 'high',
                    default          => 'average',
                };
            }

            $isOutlier = $estimate->city
                ? $aggregator->isEstimateOutlier($estimate, $currency)
                : false;

            $cooldownEndsAt = Carbon::parse($estimate->created_at)
                ->addDays(PriceEstimate::ESTIMATE_COOLDOWN_DAYS);

            return [
                'estimate'        => $estimate,
                'converted_price' => $convertedPrice,
                'city_average'    => $cityAvg,
                'deviation'       => $deviation,
                'position'        => $position,
                'is_outlier'      => $isOutlier,
                'cooldown_ends'   => $cooldownEndsAt,
                'symbol'          => $currency->symbol,
            ];
        });
    }

    public function getComparisonProperty(): ?array
    {
        if (!$this->comparisonProductId) return null;

        $user       = auth()->user();
        $currency   = $user->effectiveCurrency();
        $product    = Product::with('unit')->find($this->comparisonProductId);
        $aggregator = app(PriceAggregator::class);

        if (!$product || !$user->city) return null;

        $cityAvg    = $aggregator->cityAverage($product, $user->city, $currency, 30);
        $countryAvg = $aggregator->countryAverage($product, $user->city->country, $currency, 30);
        $globalAvg  = $aggregator->globalAverage($product, $currency, 30);

        if ($cityAvg === null && $countryAvg === null && $globalAvg === null) return null;

        $vsCountry = ($cityAvg !== null && $countryAvg !== null && $countryAvg > 0)
            ? (($cityAvg - $countryAvg) / $countryAvg) * 100
            : null;

        $vsGlobal = ($cityAvg !== null && $globalAvg !== null && $globalAvg > 0)
            ? (($cityAvg - $globalAvg) / $globalAvg) * 100
            : null;

        return [
            'product'     => $product,
            'city'        => $user->city,
            'country'     => $user->city->country,
            'city_avg'    => $cityAvg,
            'country_avg' => $countryAvg,
            'global_avg'  => $globalAvg,
            'vs_country'  => $vsCountry,
            'vs_global'   => $vsGlobal,
            'symbol'      => $currency->symbol,
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
            ->map(fn($c) => (int) $c)
            ->all();
    }

    public function setSection(string $section): void
    {
        if (!in_array($section, ['estimates', 'baskets'])) return;
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
        if (!$user->city_id) return [];

        return PriceEstimate::where('city_id', $user->city_id)
            ->distinct()
            ->pluck('product_id')
            ->toArray();
    }

    public function openCreateBasket(): void
    {
        $this->editingBasketId = null;
        $this->basketFormName  = '';
        $this->basketFormColor = '#10b981';
        $this->showBasketForm  = true;
    }

    public function cancelBasketForm(): void
    {
        $this->showBasketForm  = false;
        $this->editingBasketId = null;
        $this->basketFormName  = '';
    }

    public function updateBasket(int $id, string $name, string $color): void
    {
        $name = trim($name);
        if (empty($name) || mb_strlen($name) > 80) return;

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
        $this->basketFormName  = $basket->name ?? '';
        $this->basketFormColor = $basket->color ?? '#10b981';
        $this->showBasketForm  = true;
    }

    public function saveBasket(): void
    {
        $this->validate(['basketFormName' => 'required|string|max:80']);

        $data = [
            'name'  => trim($this->basketFormName),
            'color' => $this->basketFormColor,
            'type'  => 'saved',
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

        if ($this->openBasketId === $id)    $this->openBasketId    = null;
        if ($this->selectedBasketId === $id) $this->selectedBasketId = null;
    }

    public function selectBasketForPricing(int $id): void
    {
        $this->selectedBasketId = ($this->selectedBasketId === $id) ? null : $id;
    }

    public function setPricePeriod(string $period): void
    {
        if (!in_array($period, ['30', '90', '365', '0'])) return;
        $this->pricePeriod = $period;
    }

    public function getBasketPriceProperty(): ?array
    {
        if (!$this->selectedBasketId) return null;

        $user = auth()->user();
        if (!$user->city_id) return null;

        $basket = UserBasket::where('id', $this->selectedBasketId)
            ->where('user_id', $user->id)
            ->with(['items.product.unit', 'items.product.category'])
            ->first();

        if (!$basket || $basket->items->isEmpty()) return null;

        $currency   = $user->effectiveCurrency();
        $aggregator = app(PriceAggregator::class);
        $days       = (int) $this->pricePeriod;

        $total     = 0.0;
        $breakdown = [];
        $missing   = [];

        foreach ($basket->items as $item) {
            $avg = $aggregator->cityAverage($item->product, $user->city, $currency, $days);

            if ($avg === null) {
                $missing[] = $item;
            } else {
                $subtotal    = round($avg * (float) $item->quantity, 2);
                $total      += $subtotal;
                $breakdown[] = [
                    'name'           => $item->product->name,
                    'unit'           => $item->product->unit?->symbol ?? '',
                    'category_color' => $item->product->category?->color ?? '#9ca3af',
                    'quantity'       => (float) $item->quantity,
                    'avg'            => round($avg, 2),
                    'subtotal'       => $subtotal,
                ];
            }
        }

        return [
            'basket'    => $basket,
            'city'      => $user->city,
            'symbol'    => $currency->symbol,
            'total'     => round($total, 2),
            'breakdown' => $breakdown,
            'missing'   => $missing,
            'complete'  => empty($missing),
            'days'      => $days,
        ];
    }

    public function toggleBasket(int $id): void
    {
        $this->openBasketId = ($this->openBasketId === $id) ? null : $id;
        $this->basketItemFormKey++;
    }

    public function addItemToBasket(int $basketId, int $productId, float $qty): void
    {
        if (!$productId || $qty <= 0) return;

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
        $user        = auth()->user();
        $isEstimates = $this->activeSection === 'estimates';
        $isBaskets   = $this->activeSection === 'baskets';
        $categories  = Category::withSortedProducts();

        return view('livewire.dashboard', [
            'user'            => $user,
            'categories'      => $categories,
            'totalEstimates'  => $isEstimates ? PriceEstimate::where('user_id', $user->id)->count() : 0,
            'recentEstimates' => $isEstimates ? $this->recentEstimates : collect(),
            'comparison'      => $isEstimates ? $this->comparison : null,
            'activityMap'     => $isEstimates ? $this->activityMap : [],
            'baskets'         => $isBaskets ? $this->baskets : collect(),
            'cityProductIds'  => $isBaskets ? $this->cityProductIds : [],
            'basketPrice'     => ($isBaskets && $this->selectedBasketId && $user->city_id)
                                     ? $this->basketPrice
                                     : null,
        ])->layout('layouts.app');
    }
}
