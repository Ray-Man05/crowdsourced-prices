<div>

    {{-- Sticky filter bar --}}
    <div class="sticky top-14 z-30 bg-neutral-50/95 dark:bg-[#0a0c12]/95 backdrop-blur-md
                border-b border-neutral-200 dark:border-white/[0.06]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3 flex flex-col sm:flex-row gap-2">

            {{-- Search --}}
            <div class="flex-1 relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-neutral-400 pointer-events-none"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input
                    type="text"
                    wire:model.live.debounce.200ms="search"
                    placeholder="{{ __('Search products…') }}"
                    class="w-full pl-9 text-sm rounded-xl border-neutral-300 dark:border-white/[0.1]
                           bg-white dark:bg-white/[0.04] text-neutral-800 dark:text-neutral-100
                           placeholder-neutral-400 dark:placeholder-neutral-500
                           shadow-card focus:ring-2 focus:ring-primary-500/30 focus:border-primary-500
                           focus:bg-white dark:focus:bg-white/[0.07] transition"
                />
            </div>

            {{-- Category filter --}}
            <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                <button
                    @click="open = !open"
                    class="flex items-center gap-2 px-4 py-2 rounded-xl border border-neutral-300
                           dark:border-white/[0.1] bg-white dark:bg-white/[0.04] text-sm font-medium
                           text-neutral-700 dark:text-neutral-300 shadow-card
                           hover:bg-neutral-50 dark:hover:bg-white/[0.07]
                           focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500
                           transition whitespace-nowrap"
                >
                    <svg class="h-4 w-4 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                    </svg>
                    {{ __('Categories') }}
                    @if (count($selectedCategories))
                        <span class="inline-flex items-center justify-center w-5 h-5 rounded-full
                                     bg-primary-600 text-white text-[10px] font-bold">
                            {{ count($selectedCategories) }}
                        </span>
                    @endif
                </button>

                <div
                    x-show="open"
                    x-cloak
                    x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="opacity-0 scale-95 translate-y-1"
                    x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-75"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    class="absolute right-0 z-50 mt-1.5 w-52 bg-surface-raised border border-neutral-200
                           dark:border-white/[0.08] rounded-xl shadow-card-md py-1"
                >
                    @foreach ($categories as $category)
                        <button
                            wire:click="toggleCategory({{ $category->id }})"
                            class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-left
                                   hover:bg-neutral-50 dark:hover:bg-white/[0.05] transition
                                   {{ in_array($category->id, $selectedCategories)
                                       ? 'font-semibold text-neutral-900 dark:text-white'
                                       : 'text-neutral-700 dark:text-neutral-300' }}"
                        >
                            <span class="w-2.5 h-2.5 rounded-full flex-shrink-0"
                                  style="background-color: {{ $category->color }}"></span>
                            {{ $category->name }}
                            @if (in_array($category->id, $selectedCategories))
                                <svg class="ml-auto h-4 w-4 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                </svg>
                            @endif
                        </button>
                    @endforeach

                    @if (count($selectedCategories))
                        <div class="border-t border-neutral-100 dark:border-white/[0.05] mt-1 pt-1">
                            <button
                                wire:click="clearFilters"
                                class="w-full px-4 py-2 text-sm text-left text-error-600 dark:text-error-400
                                       hover:bg-neutral-50 dark:hover:bg-white/[0.05] transition"
                            >
                                {{ __('Clear filters') }}
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Content --}}
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

        {{-- Results summary --}}
        <p class="text-xs text-neutral-500 dark:text-neutral-400 mb-5 tabular-nums">
            <span wire:loading.remove class="font-medium text-neutral-700 dark:text-neutral-300">
                {{ $products->count() }}
            </span>
            <span wire:loading class="opacity-50">·</span>
            {{ __('products') }}
            @if ($city)
                · <span class="font-medium text-neutral-700 dark:text-neutral-300">{{ $city->name }}</span>
            @endif
            · {{ $days }}-{{ __('day average') }}
        </p>

        {{-- Grid --}}
        <div wire:loading.class="opacity-40" class="transition-opacity duration-200">
            @if ($products->isEmpty())
                <div class="text-center py-20 flex flex-col items-center">
                    <div class="w-14 h-14 rounded-2xl bg-neutral-100 dark:bg-white/[0.04] flex items-center justify-center mb-4">
                        <svg class="h-7 w-7 text-neutral-400 dark:text-neutral-500"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <p class="text-base font-semibold text-neutral-700 dark:text-neutral-300">
                        {{ __('No products found') }}
                    </p>
                    <p class="text-sm text-neutral-500 dark:text-neutral-400 mt-1">
                        {{ __('Try adjusting your search or filters') }}
                    </p>
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-3">
                    @foreach ($products as $product)
                        @if ($city && $currency)
                            <x-product-card
                                :product="$product"
                                :city="$city"
                                :currency="$currency"
                                :days="$days"
                                :average-price="$bulkMetrics[$product->id]['average'] ?? null"
                                :average3x-days-price="$bulkMetrics[$product->id]['average3x'] ?? null"
                            />
                        @endif
                    @endforeach
                </div>
            @endif
        </div>
    </div>

</div>
