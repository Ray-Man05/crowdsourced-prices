<?php

namespace App\Livewire;

use App\Models\PriceEstimate;
use App\Models\Product;
use App\Services\PriceAggregator;
use Illuminate\Support\Carbon;
use Livewire\Component;

class EstimateSubmission extends Component
{
    public Product $product;

    public string $price = '';

    public string $error = '';

    public bool $showModifyForm = false;

    public string $modifyPrice = '';

    public function mount(Product $product): void
    {
        $this->product = $product;
    }

    /**
     * Most recent estimate (including soft-deleted) — determines cooldown state.
     */
    public function getLatestEstimateProperty(): ?PriceEstimate
    {
        return PriceEstimate::withTrashed()
            ->where('product_id', $this->product->id)
            ->where('user_id', auth()->id())
            ->latest('recorded_at')
            ->first();
    }

    /**
     * Days remaining in the cooldown window, or null when not on cooldown.
     * Always at least 1 when a cooldown is active.
     */
    public function getDaysRemainingProperty(): ?int
    {
        $endsAt = PriceEstimate::cooldownEndsAt(auth()->user(), $this->product);
        if (! $endsAt) {
            return null;
        }

        return max(1, (int) ceil(Carbon::now()->diffInHours($endsAt) / 24));
    }

    /**
     * Whether the active (non-deleted) estimate is an outlier.
     */
    public function getIsOutlierProperty(): bool
    {
        $estimate = $this->latestEstimate;
        if (! $estimate || $estimate->trashed()) {
            return false;
        }

        return app(PriceAggregator::class)
            ->isEstimateOutlier($estimate, auth()->user()->effectiveCurrency());
    }

    public function startModify(): void
    {
        $estimate = $this->latestEstimate;
        if (! $estimate || $estimate->trashed()) {
            return;
        }

        $this->modifyPrice = (string) $estimate->price;
        $this->showModifyForm = true;
    }

    public function cancelModify(): void
    {
        $this->showModifyForm = false;
        $this->modifyPrice = '';
    }

    public function saveModify(): void
    {
        $estimate = $this->latestEstimate;
        if (! $estimate || $estimate->trashed() || $estimate->user_id !== auth()->id()) {
            return;
        }

        $this->validate(['modifyPrice' => ['required', 'numeric', 'min:0.01', 'max:999999.99']]);

        // Update price in place — recorded_at is intentionally preserved
        $estimate->update(['price' => (float) $this->modifyPrice]);

        $this->showModifyForm = false;
        $this->modifyPrice = '';
        $this->dispatch('estimate-changed');
    }

    public function submit(): void
    {
        $this->error = '';

        if (PriceEstimate::isOnCooldown(auth()->user(), $this->product)) {
            $this->error = __('You are still on cooldown for this product.');

            return;
        }

        $this->validate(['price' => ['required', 'numeric', 'min:0.01', 'max:999999.99']]);

        $user = auth()->user();

        PriceEstimate::create([
            'price' => (float) $this->price,
            'user_id' => $user->id,
            'product_id' => $this->product->id,
            'currency_id' => $user->effectiveCurrency()->id,
            'city_id' => $user->city_id,
            'recorded_at' => now(),
        ]);

        $this->price = '';
        $this->dispatch('estimate-changed');
    }

    public function deleteEstimate(): void
    {
        $estimate = $this->latestEstimate;

        if (! $estimate || $estimate->trashed() || $estimate->user_id !== auth()->id()) {
            return;
        }

        $estimate->delete();
        $this->dispatch('estimate-changed');
    }

    public function render()
    {
        $estimate = $this->latestEstimate;
        $user = auth()->user();
        $effectiveCurrency = $user->effectiveCurrency();

        $estimateCurrency = $estimate && ! $estimate->trashed() ? $estimate->currency : null;
        $estimateCity = $estimate && ! $estimate->trashed() ? $estimate->city : null;
        $cityMismatch = $estimateCity && $estimateCity->id !== $user->city_id;
        $currencyMismatch = $estimateCurrency && $effectiveCurrency
            && $estimateCurrency->id !== $effectiveCurrency->id;

        return view('livewire.estimate-submission', [
            'currency' => $effectiveCurrency,
            'latestEstimate' => $estimate,
            'daysRemaining' => $this->daysRemaining,
            'isOutlier' => $this->isOutlier,
            'estimateCurrency' => $estimateCurrency,
            'estimateCity' => $estimateCity,
            'cityMismatch' => $cityMismatch,
            'currencyMismatch' => $currencyMismatch,
        ]);
    }
}
