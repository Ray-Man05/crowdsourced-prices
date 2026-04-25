<?php

namespace App\Livewire;

use App\Models\PriceEstimate;
use App\Models\Product;
use Illuminate\Support\Carbon;
use Livewire\Component;

class EstimateSubmission extends Component
{
    public Product $product;
    public string  $price = '';
    public string  $error = '';

    public function mount(Product $product): void
    {
        $this->product = $product;
    }

    /**
     * The user's most recent estimate for this product, if any.
     */
    public function getLatestEstimateProperty(): ?PriceEstimate
    {
        return PriceEstimate::where('product_id', $this->product->id)
            ->where('user_id', auth()->id())
            ->latest('recorded_at')
            ->first();
    }

    /**
     * How many days remain on the cooldown, or null if not on cooldown.
     */
    public function getDaysRemainingProperty(): ?int
    {
        $endsAt = PriceEstimate::cooldownEndsAt(auth()->user(), $this->product);
        if (!$endsAt) return null;
        return (int) ceil(Carbon::now()->diffInHours($endsAt) / 24);
    }

    public function submit(): void
    {
        $this->error = '';

        if (PriceEstimate::isOnCooldown(auth()->user(), $this->product)) {
            $this->error = __('You are still on cooldown for this product.');
            return;
        }

        $this->validate([
            'price' => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
        ]);

        $user = auth()->user();

        PriceEstimate::create([
            'price'       => (float) $this->price,
            'user_id'     => $user->id,
            'product_id'  => $this->product->id,
            'currency_id' => $user->effectiveCurrency()->id,
            'city_id'     => $user->city_id,
            'recorded_at' => now(),
        ]);

        $this->price = '';
        $this->dispatch('estimate-changed');
    }

    public function deleteEstimate(): void
    {
        $estimate = $this->latestEstimate;

        if (!$estimate || $estimate->user_id !== auth()->id()) {
            return;
        }

        $estimate->delete();
        $this->dispatch('estimate-changed');
    }

    public function render()
    {
        return view('livewire.estimate-submission', [
            'currency'      => auth()->user()->effectiveCurrency(),
            'latestEstimate' => $this->latestEstimate,
            'daysRemaining'  => $this->daysRemaining,
        ]);
    }
}