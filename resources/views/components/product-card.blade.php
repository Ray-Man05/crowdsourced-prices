<div
    class="relative bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100
           dark:border-gray-700 hover:shadow-md transition-all duration-200 p-4
           flex flex-col justify-between h-36 cursor-pointer"
    style="--category-color: {{ $product->category->color }}"
    x-data
    @mouseenter="$el.style.outline = '2px solid var(--category-color)'; $el.style.outlineOffset = '2px'"
    @mouseleave="$el.style.outline = 'none'"
>
    {{-- Top row: category badge + product name --}}
    <div class="flex items-start justify-between gap-2">
        <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100 leading-snug flex-1">
            {{ $product->name }}
        </h3>
        <span
            class="inline-flex items-center gap-1 text-xs font-medium px-2 py-0.5 rounded-full flex-shrink-0 text-white"
            style="background-color: {{ $product->category->color }}"
        >
            {{ $product->category->name }}
        </span>
    </div>

    {{-- Bottom row: price + unit --}}
    <div>
        @if ($formattedPrice !== null)
            <p class="text-xl font-bold text-gray-800 dark:text-gray-100 leading-none">
                {{ $formattedPrice }}
                @if ($product->unit)
                    <span class="text-sm font-normal text-gray-800 dark:text-gray-100">/ {{ $product->unit->symbol }}</span>
                @endif
            </p>
            <p class="text-xs text-gray-600 dark:text-gray-300 mt-1">
                {{ $days }}-{{ __('day avg') }} · {{ $city->name }}
            </p>
        @else
            <p class="text-sm font-semibold text-gray-600 dark:text-gray-300">—</p>
            <p class="text-xs text-gray-600 dark:text-gray-300 mt-1">{{ __('No data yet') }}</p>
        @endif
    </div>

</div>