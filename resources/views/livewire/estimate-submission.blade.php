<div class="bg-surface-card rounded-xl border border-neutral-200 dark:border-neutral-700/60 shadow-card p-4">

    @if ($latestEstimate && $daysRemaining)
        {{-- On cooldown: show submission and countdown --}}
        <div class="flex items-center justify-between gap-4">
            <div>
                <p class="text-sm font-medium text-neutral-800 dark:text-neutral-100">
                    {{ __('Your latest estimate') }}:
                    <span class="text-primary-600 dark:text-primary-400 font-semibold">
                        {{ $latestEstimate->currency->symbol }}{{ number_format($latestEstimate->price, 2) }}
                    </span>
                </p>
                <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-0.5">
                    {{ __('Submitted') }} {{ $latestEstimate->recorded_at->format('M j, Y') }}
                    ·
                    {{ $daysRemaining === 1
                        ? __('1 day until next submission')
                        : __(':days days until next submission', ['days' => $daysRemaining]) }}
                </p>
            </div>

            <button
                wire:click="deleteEstimate"
                wire:confirm="{{ __('Modify this estimate?') }}"
                class="text-sm text-error-500 hover:text-error-700 dark:text-error-400
                       dark:hover:text-error-300 focus-visible:outline-none focus-visible:ring-2
                       focus-visible:ring-error-400 rounded transition flex-shrink-0"
            >
                {{ __('Delete') }}
            </button>
        </div>

    @else
        {{-- Submission form --}}
        <div>
            <x-input-label for="price" :value="__('Your estimate') . ' (' . $currency->symbol . ')'" />
            <div class="flex items-center gap-2 mt-1">
                <div class="relative flex-1">
                    <span class="absolute inset-y-0 left-3 flex items-center text-sm
                                 text-neutral-500 dark:text-neutral-400 pointer-events-none">
                        {{ $currency->symbol }}
                    </span>
                    <x-text-input
                        id="price"
                        wire:model="price"
                        wire:keydown.enter="submit"
                        type="number"
                        step="0.01"
                        min="0.01"
                        class="pl-8 w-full"
                        placeholder="0.00"
                    />
                </div>
                <button
                    wire:click="submit"
                    wire:loading.attr="disabled"
                    wire:loading.class="opacity-60 cursor-not-allowed"
                    class="px-5 py-2.5 bg-primary-600 hover:bg-primary-700 active:bg-primary-800 text-white
                           text-sm font-semibold rounded-lg transition flex-shrink-0
                           focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500
                           focus-visible:ring-offset-2 dark:focus-visible:ring-offset-neutral-900
                           disabled:opacity-60 disabled:cursor-not-allowed"
                >
                    {{ __('Submit') }}
                </button>
            </div>
            @if ($product->unit)
                <p class="mt-1 text-xs text-neutral-400 dark:text-neutral-500">
                    {{ __('per :unit', ['unit' => $product->unit->symbol]) }}
                </p>
            @endif
        </div>

        @if ($error)
            <p class="mt-2 text-sm text-error-600 dark:text-error-400">{{ $error }}</p>
        @endif
        @error('price')
            <p class="mt-2 text-sm text-error-600 dark:text-error-400">{{ $message }}</p>
        @enderror
    @endif

</div>
