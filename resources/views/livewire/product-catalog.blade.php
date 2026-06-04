{{--
    Product names + category IDs are pushed to window.__catalogProducts ONCE on the
    initial full-page load so Alpine can compute the visible count reactively.
    Livewire AJAX responses do NOT include @push content, and there are no Livewire
    interactions on this page after mount — all filtering is handled client-side.
--}}
@push('scripts')
@php
    $catalogData = $products->map(fn ($p) => [
        'id' => $p->id,
        'n'  => strtolower(implode(' ', array_filter(array_values($p->getRawTranslations('name'))))),
        'c'  => $p->category_id,
    ]);
@endphp
<script>
    window.__catalogProducts = @json($catalogData);
</script>
@endpush

<div
    x-data="{
        search: '',
        selectedCategories: [],
        allProducts: window.__catalogProducts ?? [],
        sortBy: 'category',

        get visibleCount() {
            const q = this.search.toLowerCase().trim();
            if (!q && !this.selectedCategories.length) return this.allProducts.length;
            return this.allProducts.filter(p =>
                (!q || p.n.includes(q)) &&
                (!this.selectedCategories.length || this.selectedCategories.includes(p.c))
            ).length;
        },

        showProduct(el) {
            const q = this.search.toLowerCase().trim();
            const nameOk = !q || (el.dataset.name || '').includes(q);
            const catOk  = !this.selectedCategories.length ||
                           this.selectedCategories.includes(parseInt(el.dataset.category));
            return nameOk && catOk;
        },

        showCategory(categoryId) {
            const q    = this.search.toLowerCase().trim();
            const cats = this.selectedCategories;
            return this.allProducts.some(p =>
                p.c === categoryId &&
                (!q    || p.n.includes(q)) &&
                (!cats.length || cats.includes(p.c))
            );
        },

        toggleCategory(id) {
            const idx = this.selectedCategories.indexOf(id);
            if (idx === -1) this.selectedCategories.push(id);
            else this.selectedCategories.splice(idx, 1);
        },

        clearFilters() {
            this.search = '';
            this.selectedCategories = [];
        },
    }"
>

    {{-- Sticky filter bar --}}
    <div class="sticky top-14 z-30 bg-neutral-50/95 dark:bg-[#0a0c12]/95 backdrop-blur-md
                border-b border-neutral-200 dark:border-white/[0.06]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3 flex flex-col sm:flex-row gap-2">

            {{-- Search — purely Alpine, no Livewire binding --}}
            <div class="flex-1 relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-neutral-400 pointer-events-none"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input
                    type="text"
                    x-model="search"
                    placeholder="{{ __('Search products…') }}"
                    class="w-full pl-9 text-sm rounded-xl border-neutral-300 dark:border-white/[0.1]
                           bg-white dark:bg-white/[0.04] text-neutral-800 dark:text-neutral-100
                           placeholder-neutral-400 dark:placeholder-neutral-500
                           shadow-card focus:ring-2 focus:ring-primary-500/30 focus:border-primary-500
                           focus:bg-white dark:focus:bg-white/[0.07] transition"
                />
            </div>

            {{-- Category filter — Alpine-driven --}}
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
                    <span x-show="selectedCategories.length > 0"
                          x-text="selectedCategories.length"
                          class="inline-flex items-center justify-center w-5 h-5 rounded-full
                                 bg-primary-600 text-white text-[10px] font-bold">
                    </span>
                </button>

                <div
                    x-show="open"
                    x-cloak
                    x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                    x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-100"
                    x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                    x-transition:leave-end="opacity-0 scale-95 -translate-y-1"
                    class="absolute right-0 z-50 mt-2 w-56
                           rounded-xl overflow-hidden
                           border border-neutral-200/70 dark:border-white/[0.08]
                           bg-white/[0.97] dark:bg-[#0a0c12]/95 backdrop-blur-xl
                           shadow-lg shadow-black/[0.08] dark:shadow-black/40"
                >
                    <div class="px-4 py-2.5 border-b border-neutral-100 dark:border-white/[0.06]">
                        <p class="text-[11px] font-semibold uppercase tracking-wider text-neutral-400 dark:text-neutral-500">
                            {{ __('Categories') }}
                        </p>
                    </div>

                    <div class="py-1">
                        @foreach ($categories as $category)
                            <button
                                @click="toggleCategory({{ $category->id }})"
                                :style="selectedCategories.includes({{ $category->id }})
                                    ? 'color: {{ $category->color }}; background-color: {{ $category->color }}18;'
                                    : ''"
                                :class="selectedCategories.includes({{ $category->id }})
                                    ? 'font-semibold'
                                    : 'text-neutral-700 dark:text-neutral-300 hover:bg-neutral-100 dark:hover:bg-white/[0.07]'"
                                class="w-full flex items-center gap-3 px-4 py-2.5 text-[15px] text-left transition-colors"
                            >
                                <span class="w-2.5 h-2.5 rounded-full flex-shrink-0 shadow-sm"
                                      style="background-color: {{ $category->color }}"></span>
                                {{ $category->name }}
                                <svg x-show="selectedCategories.includes({{ $category->id }})"
                                     class="ml-auto h-3.5 w-3.5 flex-shrink-0"
                                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                </svg>
                            </button>
                        @endforeach
                    </div>

                    <div x-show="selectedCategories.length > 0"
                         class="border-t border-neutral-100 dark:border-white/[0.06] py-1">
                        <button
                            @click="clearFilters(); open = false"
                            class="w-full flex items-center gap-2.5 px-4 py-2 text-[15px] text-left
                                   text-error-600 dark:text-error-400
                                   hover:bg-neutral-100 dark:hover:bg-white/[0.07] transition-colors"
                        >
                            <svg class="h-3.5 w-3.5 opacity-70 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                            {{ __('Clear filters') }}
                        </button>
                    </div>
                </div>
            </div>

            {{-- Sort toggle --}}
            <div class="flex items-center rounded-xl border border-neutral-300 dark:border-white/[0.1]
                        bg-white dark:bg-white/[0.04] shadow-card p-1 gap-0.5 shrink-0">
                <button
                    @click="sortBy = 'category'"
                    :class="sortBy === 'category'
                        ? 'bg-primary-100 dark:bg-primary-900/40 text-primary-700 dark:text-primary-300 font-semibold'
                        : 'text-neutral-600 dark:text-neutral-400 hover:text-neutral-800 dark:hover:text-neutral-200'"
                    class="px-3 py-1.5 text-sm rounded-lg transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500"
                >{{ __('By category') }}</button>
                <button
                    @click="sortBy = 'name'"
                    :class="sortBy === 'name'
                        ? 'bg-primary-100 dark:bg-primary-900/40 text-primary-700 dark:text-primary-300 font-semibold'
                        : 'text-neutral-600 dark:text-neutral-400 hover:text-neutral-800 dark:hover:text-neutral-200'"
                    class="px-3 py-1.5 text-sm rounded-lg transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500"
                >{{ __('A–Z') }}</button>
            </div>
        </div>
    </div>

    {{-- Content --}}
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

        {{-- Results summary — count is Alpine-reactive --}}
        <p class="text-sm text-neutral-600 dark:text-neutral-400 mb-5 tabular-nums">
            <span class="font-semibold text-neutral-800 dark:text-neutral-200" x-text="visibleCount"></span>
            {{ __('products') }}
            @if ($city)
                · <span class="font-semibold text-neutral-800 dark:text-neutral-200">{{ $city->name }}</span>
            @endif
            · <span class="font-medium">{{ $days }}-{{ __('day average') }}</span>
        </p>

        {{-- Products grouped by category, each category alphabetical, products within alphabetical --}}
        @php $grouped = $products->groupBy('category_id'); @endphp
        <div class="space-y-8" x-show="sortBy === 'category'">
            @foreach ($grouped as $categoryId => $categoryProducts)
                @php $cat = $categoryProducts->first()->category; @endphp
                <div x-show="showCategory({{ $categoryId }})">

                    {{-- Category header --}}
                    <div class="flex items-center gap-2.5 mb-3">
                        <span class="w-2.5 h-2.5 rounded-full flex-shrink-0"
                              style="background-color: {{ $cat->color }}"></span>
                        <h2 class="text-xs font-bold uppercase tracking-widest"
                            style="color: {{ $cat->color }}">
                            {{ $cat->name }}
                        </h2>
                        <div class="flex-1 h-px bg-neutral-200 dark:bg-white/[0.06]"></div>
                    </div>

                    {{-- Product grid for this category --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-3">
                        @foreach ($categoryProducts as $product)
                            @if ($city && $currency)
                                @php
                                    $productMetrics = $bulkMetrics[$product->id]  ?? [];
                                    $productStatus  = $userStatuses[$product->id] ?? [];
                                @endphp
                                <div
                                    x-show="showProduct($el)"
                                    data-name="{{ strtolower(implode(' ', array_filter(array_values($product->getRawTranslations('name'))))) }}"
                                    data-category="{{ $product->category_id }}"
                                >
                                    <x-product-card
                                        :product="$product"
                                        :city="$city"
                                        :currency="$currency"
                                        :days="$days"
                                        :average-price="$productMetrics['average'] ?? null"
                                        :average3x-days-price="$productMetrics['average3x'] ?? null"
                                        :has-city-data="$productMetrics['has_city_data'] ?? false"
                                        :user-status="$productStatus['status'] ?? null"
                                        :user-estimate-formatted="$productStatus['formattedEstimate'] ?? null"
                                    />
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Flat A–Z view --}}
        @php $productsByName = $products->sortBy(fn ($p) => $p->getRawTranslations('name')['en'] ?? '')->values(); @endphp
        <div x-show="sortBy === 'name'" x-cloak
             class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-3">
            @foreach ($productsByName as $product)
                @if ($city && $currency)
                    @php
                        $productMetrics = $bulkMetrics[$product->id]  ?? [];
                        $productStatus  = $userStatuses[$product->id] ?? [];
                    @endphp
                    <div
                        x-show="showProduct($el)"
                        data-name="{{ strtolower(implode(' ', array_filter(array_values($product->getRawTranslations('name'))))) }}"
                        data-category="{{ $product->category_id }}"
                    >
                        <x-product-card
                            :product="$product"
                            :city="$city"
                            :currency="$currency"
                            :days="$days"
                            :average-price="$productMetrics['average'] ?? null"
                            :average3x-days-price="$productMetrics['average3x'] ?? null"
                            :has-city-data="$productMetrics['has_city_data'] ?? false"
                            :user-status="$productStatus['status'] ?? null"
                            :user-estimate-formatted="$productStatus['formattedEstimate'] ?? null"
                        />
                    </div>
                @endif
            @endforeach
        </div>

        {{-- Empty state (no products match the filter) --}}
        <div x-show="visibleCount === 0"
             class="text-center py-20 flex flex-col items-center">
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

    </div>

</div>
