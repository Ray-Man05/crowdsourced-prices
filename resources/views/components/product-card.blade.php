<a href="{{ route('products.show', $product) }}" class="block group">
<div
    class="relative bg-surface-card rounded-2xl shadow-card border border-neutral-200/80
           dark:border-white/[0.06] group-hover:shadow-card-md group-hover:-translate-y-0.5
           group-hover:border-neutral-300 dark:group-hover:border-white/[0.1]
           transition-all duration-200 p-4 h-44 sm:h-40 cursor-pointer overflow-hidden"
    style="border-top: 3px solid {{ $product->category->color }}"
    x-data="{ hover: false }"
    @mouseenter="hover = true"
    @mouseleave="hover = false"
>
    {{-- Default view --}}
    <div
        class="absolute inset-0 flex flex-col justify-between p-4 transition-all duration-200"
        :class="hover ? 'opacity-0 translate-y-2' : 'opacity-100 translate-y-0'"
    >
        <div>
            <div class="flex items-start justify-between gap-2 mb-1">
                <span
                    class="inline-flex items-center text-[10px] font-semibold px-2 py-0.5 rounded-full flex-shrink-0 text-white leading-normal"
                    style="background-color: {{ $product->category->color }}"
                >
                    {{ $product->category->name }}
                </span>

                @if ($userStatus === 'active_outlier')
                    <span title="{{ __('Your estimate was flagged as an outlier') }}"
                          class="inline-flex items-center gap-1 text-[10px] font-semibold px-1.5 py-0.5
                                 rounded-full bg-warning-100 dark:bg-warning-900/40
                                 text-warning-700 dark:text-warning-400 flex-shrink-0 leading-normal">
                        <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
                        </svg>
                        {{ __('Flagged') }}
                    </span>
                @elseif ($userStatus === 'active_cooldown')
                    <span title="{{ __('You submitted a price — cooldown active') }}"
                          class="inline-flex items-center gap-1 text-[10px] font-semibold px-1.5 py-0.5
                                 rounded-full bg-primary-100 dark:bg-primary-900/40
                                 text-primary-700 dark:text-primary-400 flex-shrink-0 leading-normal">
                        <span class="w-1.5 h-1.5 rounded-full bg-primary-500 dark:bg-primary-400"></span>
                        {{ __('Submitted') }}
                    </span>
                @elseif ($userStatus === 'deleted_cooldown')
                    <span title="{{ __('Estimate deleted — cooldown still active') }}"
                          class="inline-flex items-center gap-1 text-[10px] font-semibold px-1.5 py-0.5
                                 rounded-full bg-neutral-100 dark:bg-neutral-700/50
                                 text-neutral-500 dark:text-neutral-400 flex-shrink-0 leading-normal">
                        <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                        </svg>
                        {{ __('Cooldown') }}
                    </span>
                @endif
            </div>
            <h3 class="text-lg font-bold text-neutral-900 dark:text-neutral-50 leading-snug tracking-tight">
                {{ $product->name }}
            </h3>
        </div>

        <div>
            @if ($formattedPrice !== null)
                <p class="text-2xl font-bold text-neutral-900 dark:text-white leading-none tracking-tight">
                    {{ $formattedPrice }}
                    @if ($product->unit)
                        <span class="text-xs font-normal text-neutral-500 dark:text-neutral-400 ml-0.5">
                            / {{ $product->unit->symbol }}
                        </span>
                    @endif
                </p>
                <p class="text-sm text-neutral-500 dark:text-neutral-400 mt-1.5 tabular-nums">
                    {{ $days }}-{{ __('day avg') }} · <span class="font-medium text-neutral-700 dark:text-neutral-300">{{ $city->name }}</span>
                </p>
            @elseif ($userStatus === 'active_outlier')
                <p class="text-lg font-bold text-warning-400 dark:text-warning-500">—</p>
                <p class="text-xs text-neutral-400 dark:text-neutral-500 mt-0.5">
                    {{ __('Your estimate was excluded') }}
                </p>
            @elseif ($hasCityData)
                <p class="text-lg font-bold text-neutral-300 dark:text-neutral-600">—</p>
                <p class="text-xs text-neutral-400 dark:text-neutral-500 mt-0.5">
                    {{ __('No data this period') }}
                </p>
                <p class="text-sm font-semibold text-primary-500 dark:text-primary-400 mt-1">
                    {{ __('Submit a price →') }}
                </p>
            @else
                <p class="text-lg font-bold text-neutral-300 dark:text-neutral-600">—</p>
                <p class="text-sm font-semibold text-primary-500 dark:text-primary-400 mt-1">
                    {{ __('Be the first to submit!') }}
                </p>
            @endif
        </div>
    </div>

    {{-- Hover/detail view --}}
    <div
        class="absolute inset-0 flex flex-col justify-between p-4 transition-all duration-200"
        :class="hover ? 'opacity-100 translate-y-0' : 'opacity-0 -translate-y-2 pointer-events-none'"
    >
        @php
            $threshold_percent = 0.01;
            $isHigher = $average3xDaysPrice !== null && $averagePrice > $average3xDaysPrice * (1 + $threshold_percent);
            $isLower  = $average3xDaysPrice !== null && $averagePrice < $average3xDaysPrice * ((1 - $threshold_percent));
            $hasAnyPriceData = $formattedPrice || $formatted3xDaysPrice;
        @endphp

        <h3 class="text-sm font-semibold text-neutral-700 dark:text-neutral-300 tracking-tight">
            {{ $product->name }}
        </h3>

        <div class="space-y-1.5 text-sm">
            @if ($hasAnyPriceData)
                @if ($formattedPrice)
                    <div class="flex justify-between items-baseline">
                        <span class="text-xs text-neutral-500 dark:text-neutral-400">{{ $days }}-{{ __('day avg') }}</span>
                        <span class="font-semibold tabular-nums
                            {{ $isHigher ? 'text-error-500' : '' }}
                            {{ $isLower  ? 'text-success-500' : '' }}
                            {{ !$isHigher && !$isLower ? 'text-neutral-800 dark:text-white' : '' }}
                        ">{{ $formattedPrice }}</span>
                    </div>
                @endif
                @if ($formatted3xDaysPrice)
                    <div class="flex justify-between items-baseline">
                        <span class="text-xs text-neutral-500 dark:text-neutral-400">{{ 3 * $days }}-{{ __('day avg') }}</span>
                        <span class="font-medium tabular-nums text-neutral-600 dark:text-neutral-300">
                            {{ $formatted3xDaysPrice }}
                        </span>
                    </div>
                @endif

                @if ($formatted3xDaysPrice && $formattedPrice)
                    <div class="flex justify-between items-center pt-1.5 border-t border-neutral-100 dark:border-white/[0.06]">
                        <span class="text-xs text-neutral-500 dark:text-neutral-400">{{ __('Trend') }}</span>
                        <span class="text-xs font-bold
                            {{ $isHigher ? 'text-error-500' : '' }}
                            {{ $isLower  ? 'text-success-500' : '' }}
                            {{ !$isHigher && !$isLower ? 'text-neutral-400' : '' }}
                        ">
                            @if ($isHigher) {{ __('↑ Rising') }}
                            @elseif ($isLower) {{ __('↓ Falling') }}
                            @else {{ __('→ Stable') }}
                            @endif
                        </span>
                    </div>
                @endif

                @if ($userStatus === 'active_outlier' && $userEstimateFormatted)
                    <div class="flex justify-between items-baseline pt-1.5 border-t border-neutral-100 dark:border-white/[0.06]">
                        <span class="text-xs text-warning-600 dark:text-warning-400">{{ __('Your estimate') }}</span>
                        <span class="text-xs font-semibold tabular-nums text-warning-600 dark:text-warning-400 line-through">
                            {{ $userEstimateFormatted }}
                        </span>
                    </div>
                @endif
            @elseif ($userStatus === 'active_outlier' && $userEstimateFormatted)
                <div class="flex justify-between items-baseline">
                    <span class="text-xs text-warning-600 dark:text-warning-400">{{ __('Your estimate') }}</span>
                    <span class="text-xs font-semibold tabular-nums text-warning-600 dark:text-warning-400 line-through">
                        {{ $userEstimateFormatted }}
                    </span>
                </div>
                <p class="text-xs text-neutral-400 dark:text-neutral-500 pt-1">
                    {{ __('Excluded from avg — outside expected range') }}
                </p>
            @else
                <p class="text-xs text-neutral-400 dark:text-neutral-500">
                    {{ $hasCityData ? __('No data for this period') : __('No submissions yet') }}
                </p>
                <p class="text-sm font-semibold text-primary-500 dark:text-primary-400 pt-0.5">
                    {{ $hasCityData ? __('Submit a price →') : __('Be the first to submit!') }}
                </p>
            @endif
        </div>
    </div>
</div>
</a>
