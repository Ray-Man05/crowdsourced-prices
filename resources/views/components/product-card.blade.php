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
            </div>
            <h3 class="text-base font-bold text-neutral-900 dark:text-neutral-50 leading-snug tracking-tight">
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
                <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-1.5 tabular-nums">
                    {{ $days }}-{{ __('day avg') }} · {{ $city->name }}
                </p>
            @else
                <p class="text-lg font-bold text-neutral-300 dark:text-neutral-600">—</p>
                <p class="text-xs text-neutral-400 dark:text-neutral-500 mt-1">{{ __('No data yet') }}</p>
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
        @endphp

        <h3 class="text-xs font-semibold text-neutral-700 dark:text-neutral-300 tracking-tight">
            {{ $product->name }}
        </h3>

        <div class="space-y-1.5 text-sm">
            @if ($formattedPrice)
                <div class="flex justify-between items-baseline">
                    <span class="text-xs text-neutral-500 dark:text-neutral-400">{{ $days }}-day avg</span>
                    <span class="font-semibold tabular-nums
                        {{ $isHigher ? 'text-error-500' : '' }}
                        {{ $isLower  ? 'text-success-500' : '' }}
                        {{ !$isHigher && !$isLower ? 'text-neutral-800 dark:text-white' : '' }}
                    ">{{ $formattedPrice }}</span>
                </div>
            @endif
            @if ($formatted3xDaysPrice)
                <div class="flex justify-between items-baseline">
                    <span class="text-xs text-neutral-500 dark:text-neutral-400">{{ 3 * $days }}-day avg</span>
                    <span class="font-medium tabular-nums text-neutral-600 dark:text-neutral-300">
                        {{ $formatted3xDaysPrice }}
                    </span>
                </div>
            @endif

            @if ($formatted3xDaysPrice && $formattedPrice)
                <div class="flex justify-between items-center pt-1.5 border-t border-neutral-100 dark:border-white/[0.06]">
                    <span class="text-xs text-neutral-500 dark:text-neutral-400">Trend</span>
                    <span class="text-xs font-bold
                        {{ $isHigher ? 'text-error-500' : '' }}
                        {{ $isLower  ? 'text-success-500' : '' }}
                        {{ !$isHigher && !$isLower ? 'text-neutral-400' : '' }}
                    ">
                        @if ($isHigher) ↑ Rising
                        @elseif ($isLower) ↓ Falling
                        @else → Stable
                        @endif
                    </span>
                </div>
            @endif
        </div>
    </div>
</div>
</a>
