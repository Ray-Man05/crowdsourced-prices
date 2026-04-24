<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    {{-- Filters --}}
    <div class="flex flex-col sm:flex-row gap-3 mb-6">

        {{-- Search --}}
        <div class="flex-1">
            <input
                type="text"
                wire:model.live.debounce.200ms="search"
                placeholder="{{ __('Search products...') }}"
                class="w-full rounded-lg border-gray-300 dark:border-gray-600
                bg-white dark:bg-gray-800
                text-gray-800 dark:text-gray-100
                placeholder-gray-400 dark:placeholder-gray-500
                shadow-sm focus:ring focus:ring-primary/30 focus:border-primary text-sm"
            />
        </div>

        {{-- Category filter --}}
        <div class="relative" x-data="{ open: false }" @click.outside="open = false">
            <button
                @click="open = !open"
                class="flex items-center gap-2 px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600
                       bg-white dark:bg-gray-800 text-sm text-gray-700 dark:text-gray-300 shadow-sm
                       hover:bg-gray-50 dark:hover:bg-gray-700 transition"
            >
                {{ __('Categories') }}
                @if (count($selectedCategories))
                    <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-primary text-white text-xs font-bold">
                        {{ count($selectedCategories) }}
                    </span>
                @endif
                <svg class="h-4 w-4 text-neutral" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>

            <div
                x-show="open"
                x-cloak
                class="absolute right-0 z-20 mt-1 w-52 bg-white dark:bg-gray-800 border border-gray-200
                       dark:border-gray-700 rounded-lg shadow-lg py-1"
            >
                @foreach ($categories as $category)
                    <button
                        wire:click="toggleCategory({{ $category->id }})"
                        class="w-full flex items-center gap-3 px-4 py-2 text-sm text-left
                               hover:bg-gray-50 dark:hover:bg-gray-700 transition
                               {{ in_array($category->id, $selectedCategories) ? 'font-semibold text-gray-900 dark:text-white' : 'text-gray-700 dark:text-gray-300' }}"
                    >
                        <span class="w-2.5 h-2.5 rounded-full flex-shrink-0"
                              style="background-color: {{ $category->color }}"></span>
                        {{ $category->name }}
                        @if (in_array($category->id, $selectedCategories))
                            <svg class="ml-auto h-4 w-4 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        @endif
                    </button>
                @endforeach

                @if (count($selectedCategories))
                    <div class="border-t border-gray-100 dark:border-gray-700 mt-1 pt-1">
                        <button
                            wire:click="clearFilters"
                            class="w-full px-4 py-2 text-sm text-left text-error hover:bg-gray-50 dark:hover:bg-gray-700 transition"
                        >
                            {{ __('Clear filters') }}
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Results summary --}}
    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
        {{ $products->count() }} {{ __('products') }}
        @if ($city) · {{ $city->name }} @endif
        · {{ $days }}-{{ __('day average') }}
    </p>

    {{-- Product grid --}}
    @if ($products->isEmpty())
        <div class="text-center py-16 text-neutral">
            <p class="text-lg font-medium">{{ __('No products found') }}</p>
            <p class="text-sm mt-1">{{ __('Try adjusting your search or filters') }}</p>
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
            @foreach ($products as $product)
                @if ($city && $currency)
                    <x-product-card
                        :product="$product"
                        :city="$city"
                        :currency="$currency"
                        :days="$days"
                    />
                @endif
            @endforeach
        </div>
    @endif
    

</div>