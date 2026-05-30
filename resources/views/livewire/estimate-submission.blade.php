<div class="rounded-xl border shadow-card p-4 transition-colors
            {{ $isOutlier
                ? 'bg-warning-50 dark:bg-warning-900/20 border-warning-200 dark:border-warning-700/50'
                : 'bg-surface-card border-neutral-200 dark:border-neutral-700/60' }}">

    @if ($daysRemaining)

        @if ($latestEstimate && !$latestEstimate->trashed())
            {{-- Active estimate on cooldown --}}

            @if ($isOutlier)
                <div class="flex items-start gap-2 mb-3">
                    <svg class="w-4 h-4 text-warning-500 dark:text-warning-400 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
                    </svg>
                    <p class="text-xs text-warning-700 dark:text-warning-400">
                        {{ __('This estimate falls outside the expected range and will not be used in price computations.') }}
                    </p>
                </div>
            @endif

            @if (!$showModifyForm)
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-sm font-medium text-neutral-800 dark:text-neutral-100">
                            {{ __('Your latest estimate') }}:
                            <span class="font-semibold {{ $isOutlier ? 'text-warning-600 dark:text-warning-400' : 'text-primary-600 dark:text-primary-400' }}">
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
                        @if ($estimateCity || $estimateCurrency)
                            <div class="flex items-center gap-1.5 mt-1.5 flex-wrap">
                                @if ($estimateCity)
                                    <span title="{{ $cityMismatch ? __('Differs from your current city') : '' }}"
                                          class="inline-flex items-center gap-1 text-xs px-1.5 py-0.5 rounded-md font-medium
                                                 {{ $cityMismatch
                                                     ? 'bg-warning-100 dark:bg-warning-900/30 text-warning-700 dark:text-warning-400 ring-1 ring-inset ring-warning-300 dark:ring-warning-700/50'
                                                     : 'bg-neutral-100 dark:bg-neutral-800 text-neutral-500 dark:text-neutral-400' }}">
                                        @if ($cityMismatch)
                                            <svg class="w-3 h-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
                                            </svg>
                                        @endif
                                        {{ $estimateCity->name }}
                                    </span>
                                @endif
                                @if ($estimateCurrency)
                                    <span title="{{ $currencyMismatch ? __('Differs from your current currency') : '' }}"
                                          class="inline-flex items-center gap-1 text-xs px-1.5 py-0.5 rounded-md font-medium
                                                 {{ $currencyMismatch
                                                     ? 'bg-warning-100 dark:bg-warning-900/30 text-warning-700 dark:text-warning-400 ring-1 ring-inset ring-warning-300 dark:ring-warning-700/50'
                                                     : 'bg-neutral-100 dark:bg-neutral-800 text-neutral-500 dark:text-neutral-400' }}">
                                        @if ($currencyMismatch)
                                            <svg class="w-3 h-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
                                            </svg>
                                        @endif
                                        {{ $estimateCurrency->code }}
                                    </span>
                                @endif
                            </div>
                        @endif
                    </div>

                    <div class="flex items-center gap-3 flex-shrink-0">
                        <button
                            wire:click="startModify"
                            class="text-sm text-primary-600 hover:text-primary-800 dark:text-primary-400
                                   dark:hover:text-primary-300 focus-visible:outline-none focus-visible:ring-2
                                   focus-visible:ring-primary-400 rounded transition"
                        >
                            {{ __('Modify') }}
                        </button>
                        <button
                            wire:click="deleteEstimate"
                            wire:confirm="{{ __('Delete this estimate? You will not be able to resubmit until the cooldown expires.') }}"
                            class="text-sm text-error-500 hover:text-error-700 dark:text-error-400
                                   dark:hover:text-error-300 focus-visible:outline-none focus-visible:ring-2
                                   focus-visible:ring-error-400 rounded transition"
                        >
                            {{ __('Delete') }}
                        </button>
                    </div>
                </div>

            @else
                {{-- Inline modify form --}}
                <p class="text-sm font-medium text-neutral-800 dark:text-neutral-100 mb-2">
                    {{ __('Update your estimate') }}
                </p>
                @if ($estimateCity || $estimateCurrency)
                    <div class="flex items-center gap-1.5 mb-2 flex-wrap">
                        @if ($estimateCity)
                            <span title="{{ $cityMismatch ? __('Differs from your current city') : '' }}"
                                  class="inline-flex items-center gap-1 text-xs px-1.5 py-0.5 rounded-md font-medium
                                         {{ $cityMismatch
                                             ? 'bg-warning-100 dark:bg-warning-900/30 text-warning-700 dark:text-warning-400 ring-1 ring-inset ring-warning-300 dark:ring-warning-700/50'
                                             : 'bg-neutral-100 dark:bg-neutral-800 text-neutral-500 dark:text-neutral-400' }}">
                                @if ($cityMismatch)
                                    <svg class="w-3 h-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
                                    </svg>
                                @endif
                                {{ $estimateCity->name }}
                            </span>
                        @endif
                        @if ($estimateCurrency)
                            <span title="{{ $currencyMismatch ? __('Differs from your current currency') : '' }}"
                                  class="inline-flex items-center gap-1 text-xs px-1.5 py-0.5 rounded-md font-medium
                                         {{ $currencyMismatch
                                             ? 'bg-warning-100 dark:bg-warning-900/30 text-warning-700 dark:text-warning-400 ring-1 ring-inset ring-warning-300 dark:ring-warning-700/50'
                                             : 'bg-neutral-100 dark:bg-neutral-800 text-neutral-500 dark:text-neutral-400' }}">
                                @if ($currencyMismatch)
                                    <svg class="w-3 h-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
                                    </svg>
                                @endif
                                {{ $estimateCurrency->code }}
                            </span>
                        @endif
                    </div>
                @endif
                <div class="flex items-center gap-2">
                    <div class="relative flex-1">
                        <span class="absolute inset-y-0 left-3 flex items-center text-sm
                                     text-neutral-500 dark:text-neutral-400 pointer-events-none">
                            {{ $estimateCurrency?->symbol ?? $currency->symbol }}
                        </span>
                        <x-text-input
                            wire:model="modifyPrice"
                            wire:keydown.enter="saveModify"
                            wire:keydown.escape="cancelModify"
                            type="number"
                            step="0.01"
                            min="0.01"
                            class="pl-8 w-full"
                            placeholder="0.00"
                        />
                    </div>
                    <button
                        wire:click="saveModify"
                        wire:loading.attr="disabled"
                        wire:loading.class="opacity-60 cursor-not-allowed"
                        class="px-4 py-2.5 bg-primary-600 hover:bg-primary-700 active:bg-primary-800 text-white
                               text-sm font-semibold rounded-lg transition flex-shrink-0
                               focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500
                               focus-visible:ring-offset-2 dark:focus-visible:ring-offset-neutral-900
                               disabled:opacity-60 disabled:cursor-not-allowed"
                    >
                        {{ __('Save') }}
                    </button>
                    <button
                        wire:click="cancelModify"
                        class="text-sm text-neutral-500 hover:text-neutral-700 dark:text-neutral-400
                               dark:hover:text-neutral-300 focus-visible:outline-none focus-visible:ring-2
                               focus-visible:ring-neutral-400 rounded transition flex-shrink-0"
                    >
                        {{ __('Cancel') }}
                    </button>
                </div>
                @error('modifyPrice')
                    <p class="mt-2 text-sm text-error-600 dark:text-error-400">{{ $message }}</p>
                @enderror
            @endif

        @else
            {{-- Estimate was deleted but cooldown is still active --}}
            <div class="flex items-center gap-3">
                <svg class="w-4 h-4 text-neutral-400 dark:text-neutral-500 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                </svg>
                <div>
                    <p class="text-sm font-medium text-neutral-500 dark:text-neutral-400">
                        {{ __('Estimate deleted') }}
                    </p>
                    <p class="text-xs text-neutral-400 dark:text-neutral-500 mt-0.5">
                        {{ $daysRemaining === 1
                            ? __('1 day until next submission')
                            : __(':days days until next submission', ['days' => $daysRemaining]) }}
                    </p>
                </div>
            </div>
        @endif

    @else
        {{-- No active cooldown — show submission form --}}
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
