<div x-data="{
    sidebarOpen: window.innerWidth >= 640,
    sidebarWidth: parseInt(localStorage.getItem('map-sidebar-width') || '288'),
    isResizing: false,
    startResize(e) {
        this.isResizing = true;
        const startX = e.clientX;
        const startWidth = this.sidebarWidth;
        const onMove = (ev) => {
            this.sidebarWidth = Math.max(200, Math.min(520, startWidth + ev.clientX - startX));
            window.dispatchEvent(new CustomEvent('sidebar-toggled'));
        };
        const onUp = () => {
            this.isResizing = false;
            localStorage.setItem('map-sidebar-width', this.sidebarWidth);
            document.removeEventListener('mousemove', onMove);
            document.removeEventListener('mouseup', onUp);
        };
        document.addEventListener('mousemove', onMove);
        document.addEventListener('mouseup', onUp);
    },
    init() {
        const saved = localStorage.getItem('map-recompute-on-change');
        if (saved !== null) {
            $wire.set('recomputeOnChange', saved === 'true');
        }
    }
}"
     @keydown.window="if ($event.key === '[' && !$event.ctrlKey && !$event.metaKey && !['INPUT','TEXTAREA','SELECT'].includes($event.target.tagName)) { sidebarOpen = !sidebarOpen; setTimeout(() => window.dispatchEvent(new CustomEvent('sidebar-toggled')), 310); }"
     class="flex h-[calc(100vh-3.5rem)] overflow-hidden relative">

    {{-- Mobile backdrop --}}
    <div x-show="sidebarOpen" x-cloak
         x-transition:enter="transition-opacity ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="sidebarOpen = false"
         class="sm:hidden fixed inset-0 z-30 bg-black/50">
    </div>

    {{-- ─── Tutorial modal ─── --}}
    <div x-data="{
             show: !localStorage.getItem('map-tutorial-dismissed'),
             dontShow: false,
             dismiss() {
                 if (this.dontShow) localStorage.setItem('map-tutorial-dismissed', '1');
                 this.show = false;
             }
         }"
         x-show="show"
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-[1000] flex items-center justify-center p-4 bg-black/50">

        <div @click.outside="dismiss()"
             class="w-full max-w-md bg-white dark:bg-[#1a1e2d]
                    rounded-2xl shadow-card-md border border-neutral-200 dark:border-white/[0.1]
                    overflow-hidden">

            {{-- Header --}}
            <div class="px-6 py-5 border-b border-neutral-200 dark:border-white/[0.06]">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-primary-100 dark:bg-primary-900/30
                                flex items-center justify-center shrink-0">
                        <svg class="h-5 w-5 text-primary-600 dark:text-primary-400"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-base font-semibold text-neutral-900 dark:text-white">
                            {{ __('How the map works') }}
                        </h2>
                        <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-0.5">
                            {{ __('A quick guide to the two modes') }}
                        </p>
                    </div>
                </div>
            </div>

            {{-- Body --}}
            <div class="px-6 py-5 space-y-4">
                <div class="flex gap-3">
                    <div class="w-7 h-7 rounded-lg bg-success-100 dark:bg-success-900/30
                                flex items-center justify-center shrink-0 mt-0.5">
                        <svg class="h-3.5 w-3.5 text-success-600 dark:text-success-400"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                            {{ __('Price mode') }}
                        </p>
                        <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-0.5 leading-relaxed">
                            {{ __('Build a basket of products with quantities, then click "Compute prices" to see the combined basket cost for each city on the map.') }}
                        </p>
                    </div>
                </div>

                <div class="flex gap-3">
                    <div class="w-7 h-7 rounded-lg bg-accent-100 dark:bg-accent-900/30
                                flex items-center justify-center shrink-0 mt-0.5">
                        <svg class="h-3.5 w-3.5 text-accent-600 dark:text-accent-400"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  {{-- d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064"/> --}}
                                  d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 0 1 3 12c0-1.605.42-3.113 1.157-4.418"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                            {{ __('Coverage mode') }}
                        </p>
                        <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-0.5 leading-relaxed">
                            {{ __('Visualize data density and see how many price submissions or distinct products each city has. Useful for spotting where data is sparse.') }}
                        </p>
                    </div>
                </div>

                <div class="border-t border-neutral-200 dark:border-white/[0.06] pt-4 space-y-2">
                    <p class="text-[10px] font-bold uppercase tracking-widest
                               text-neutral-400 dark:text-neutral-500 mb-2">
                        {{ __('Tips') }}
                    </p>
                    @foreach ([
                        __('Click any city marker to see a price breakdown or count.'),
                        __('Use the Display panel (bottom-left) to adjust marker size and colors.'),
                        __('Toggle the sidebar with the Hide button or press the "[" key.'),
                    ] as $tip)
                        <div class="flex items-start gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-primary-400 shrink-0 mt-1.5"></span>
                            <p class="text-xs text-neutral-500 dark:text-neutral-400">{{ $tip }}</p>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Footer --}}
            <div class="px-6 py-4 border-t border-neutral-200 dark:border-white/[0.06]
                        bg-neutral-50 dark:bg-white/[0.02]
                        flex items-center justify-between gap-4">
                <label class="flex items-center gap-2 cursor-pointer select-none">
                    <input type="checkbox" x-model="dontShow"
                           class="rounded text-primary-600 border-neutral-300 dark:border-white/[0.1]
                                  bg-neutral-50 dark:bg-white/[0.04] focus:ring-primary-500/30"/>
                    <span class="text-xs text-neutral-500 dark:text-neutral-400">
                        {{ __("Don't show again") }}
                    </span>
                </label>
                <button @click="dismiss()"
                        class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white
                               text-sm font-semibold rounded-lg transition
                               focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500">
                    {{ __('Got it') }}
                </button>
            </div>
        </div>
    </div>

    {{-- ─── Sidebar ─── --}}
    <div class="fixed sm:relative z-40 sm:z-auto top-14 bottom-0 sm:top-auto sm:bottom-auto left-0
                w-72 flex-shrink-0 bg-surface-card border-r border-neutral-200 dark:border-white/[0.06]
                flex flex-col overflow-hidden"
         :class="[
             sidebarOpen ? 'translate-x-0' : '-translate-x-full sm:translate-x-0',
             isResizing ? '' : 'transition-[width,transform] duration-300 ease-in-out'
         ]"
         :style="window.innerWidth >= 640 ? 'width: ' + (sidebarOpen ? sidebarWidth : 0) + 'px' : ''">
        <div class="w-full flex flex-col flex-1 overflow-hidden min-h-0" style="min-width: 200px">

        {{-- ── Mode toggle ── --}}
        <div class="p-3 border-b border-neutral-200 dark:border-white/[0.06]">
            <div class="flex rounded-lg border border-neutral-200 dark:border-white/[0.08]
                        bg-neutral-100 dark:bg-white/[0.04] p-0.5 gap-0.5">
                @foreach ($modes as $modeKey => $modeLabel)
                    <button
                        wire:click="$set('mapMode', '{{ $modeKey }}')"
                        class="flex-1 py-1.5 text-xs font-semibold rounded-md transition
                               {{ $mapMode === $modeKey
                                   ? 'bg-white dark:bg-[#252938] shadow-sm text-neutral-900 dark:text-neutral-100'
                                   : 'text-neutral-500 dark:text-neutral-400 hover:text-neutral-700 dark:hover:text-neutral-300' }}"
                    >{{ __($modeLabel) }}</button>
                @endforeach
            </div>
        </div>

        {{-- ── Price mode: product picker ── --}}
        @if ($mapMode === 'price')
        <div class="p-4 border-b border-neutral-200 dark:border-white/[0.06]">
            <h2 class="text-xs font-semibold uppercase tracking-wider text-neutral-500 dark:text-neutral-400 mb-3">
                {{ __('Build your basket') }}
            </h2>

            <div
                x-data="{
                    open: false,
                    search: '',
                    activeIndex: -1,
                    selectedProductId: null,
                    selectedUnit: '',
                    qty: 1,
                    locale: document.documentElement.lang ?? 'en',
                    products: {{ Js::from($categories) }},
                    getName(obj) {
                        if (!obj) return '';
                        if (typeof obj === 'string') return obj;
                        return obj[this.locale] ?? obj.en ?? Object.values(obj)[0] ?? '';
                    },
                    get filteredCategories() {
                        return this.products.map(c => ({
                            ...c,
                            products: c.products
                                .filter(p => !this.search || this.getName(p.name).toLowerCase().includes(this.search.toLowerCase()))
                                .sort((a, b) => this.getName(a.name).localeCompare(this.getName(b.name)))
                        })).filter(c => c.products.length > 0);
                    },
                    get flatProducts() {
                        return this.filteredCategories.flatMap(c => c.products);
                    },
                    selectProduct(product) {
                        this.selectedProductId = product.id;
                        this.selectedUnit = product.unit ? product.unit.symbol : '';
                        this.search = this.getName(product.name);
                        this.open = false;
                        this.activeIndex = -1;
                        $wire.set('selectedProductId', product.id);
                    },
                    init() {
                        window.addEventListener('keydown', e => {
                            if (e.key === '/' && !e.ctrlKey && !e.metaKey && !['INPUT','TEXTAREA','SELECT'].includes(e.target.tagName)) {
                                e.preventDefault();
                                this.$refs.search.focus();
                                this.open = true;
                            }
                        });
                    }
                }"
                @product-added-to-basket.window="search = ''; selectedProductId = null; selectedUnit = ''; qty = 1; open = false; activeIndex = -1"
                class="relative"
            >
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-neutral-400 pointer-events-none"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input
                        type="text"
                        x-ref="search"
                        x-model="search"
                        @focus="open = true"
                        @click.outside="open = false; activeIndex = -1"
                        @input="open = true; activeIndex = -1"
                        @keydown.down.prevent="open = true; activeIndex = Math.min(activeIndex + 1, flatProducts.length - 1)"
                        @keydown.up.prevent="activeIndex = activeIndex > 0 ? activeIndex - 1 : activeIndex"
                        @keydown.enter.prevent="if (activeIndex >= 0 && flatProducts[activeIndex]) selectProduct(flatProducts[activeIndex])"
                        @keydown.escape="open = false; activeIndex = -1"
                        placeholder="{{ __('Search products… (/)') }}"
                        class="w-full pl-8 text-sm rounded-lg border-neutral-300 dark:border-white/[0.1]
                               bg-neutral-50 dark:bg-white/[0.04] text-neutral-800 dark:text-neutral-100
                               placeholder-neutral-400 dark:placeholder-neutral-500
                               focus:ring-2 focus:ring-primary-500/30 focus:border-primary-500
                               focus:bg-white dark:focus:bg-white/[0.07] transition mb-2"
                    />
                </div>
                <input type="hidden" :value="selectedProductId">

                <div x-show="open" x-cloak
                     x-transition:enter="transition ease-out duration-100"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     class="absolute z-20 w-full mt-1 bg-surface-raised border border-neutral-200
                            dark:border-white/[0.08] rounded-xl shadow-card-md max-h-60 overflow-y-auto">
                    <template x-for="category in filteredCategories" :key="category.id">
                        <div>
                            <div class="px-3 pt-2.5 pb-1 text-[10px] font-bold uppercase tracking-widest"
                                 :style="'color: ' + (category.color ?? '#9ca3af')">
                                <span x-text="getName(category.name)"></span>
                            </div>
                            <template x-for="product in category.products" :key="product.id">
                                <div @click="selectProduct(product)"
                                     :class="activeIndex >= 0 && flatProducts[activeIndex]?.id === product.id
                                         ? 'bg-primary-50 dark:bg-primary-900/30'
                                         : 'hover:bg-neutral-100 dark:hover:bg-white/[0.06]'"
                                     class="px-3 py-2 text-sm cursor-pointer flex items-center justify-between transition">
                                    <span x-text="getName(product.name)"
                                          class="text-neutral-800 dark:text-neutral-200"></span>
                                    <template x-if="product.unit">
                                        <span class="text-xs text-neutral-400 dark:text-neutral-500 ml-2">
                                            <span x-text="product.unit.symbol"></span>
                                        </span>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>

                {{-- Quantity slider + input + Add --}}
                <div class="mt-3">
                    <div class="flex items-center gap-2 mb-2">
                        <input type="range"
                               x-model.number="qty"
                               min="0.1" max="10" step="0.1"
                               :disabled="!selectedProductId"
                               class="flex-1 min-w-0 h-1.5 rounded-full appearance-none cursor-pointer
                                      accent-primary-500 disabled:opacity-40 disabled:cursor-not-allowed"/>
                        <input type="number"
                               x-model.number="qty"
                               min="0.01" step="0.01"
                               class="w-16 text-sm text-center rounded-lg
                                      border-neutral-300 dark:border-white/[0.1]
                                      bg-neutral-50 dark:bg-white/[0.04] text-neutral-800 dark:text-neutral-100
                                      focus:ring-2 focus:ring-primary-500/30 focus:border-primary-500
                                      focus:bg-white dark:focus:bg-white/[0.07] transition"/>
                        <span x-show="selectedUnit" x-text="selectedUnit"
                              class="text-sm font-bold text-neutral-700 dark:text-neutral-200
                                     shrink-0 min-w-[2rem] text-left">
                        </span>
                    </div>
                    <button
                        @click="selectedProductId && $wire.addToBasket(qty)"
                        :disabled="!selectedProductId"
                        class="w-full px-3 py-2 bg-primary-600 hover:bg-primary-700 active:bg-primary-800
                               text-white text-sm font-semibold rounded-lg transition
                               focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500
                               disabled:opacity-40 disabled:cursor-not-allowed"
                    >
                        {{ __('Add to basket') }}
                    </button>
                </div>
            </div>
        </div>

        {{-- ── Coverage mode: metric selector ── --}}
        @elseif ($mapMode === 'coverage')
        <div class="p-4 border-b border-neutral-200 dark:border-white/[0.06]">
            <h2 class="text-xs font-semibold uppercase tracking-wider text-neutral-500 dark:text-neutral-400 mb-3">
                {{ __('Coverage metric') }}
            </h2>
            <div class="space-y-2">
                <label class="flex items-center gap-3 p-3 rounded-lg cursor-pointer transition border
                              {{ $coverageMetric === 'estimates'
                                  ? 'border-primary-400 dark:border-primary-500/60 bg-primary-50 dark:bg-primary-900/20'
                                  : 'border-neutral-200 dark:border-white/[0.08] bg-neutral-50 dark:bg-white/[0.02] hover:border-neutral-300 dark:hover:border-white/[0.14]' }}">
                    <input type="radio" wire:model.live="coverageMetric" value="estimates"
                           class="text-primary-600 border-neutral-300 dark:border-white/[0.1]
                                  bg-neutral-50 dark:bg-white/[0.04] focus:ring-primary-500/30"/>
                    <div class="min-w-0">
                        <p class="text-xs font-semibold text-neutral-800 dark:text-neutral-100">{{ __('Submissions') }}</p>
                        <p class="text-[11px] text-neutral-500 dark:text-neutral-400 mt-0.5 leading-tight">
                            {{ __('Total price estimates submitted per city') }}
                        </p>
                    </div>
                </label>
                <label class="flex items-center gap-3 p-3 rounded-lg cursor-pointer transition border
                              {{ $coverageMetric === 'products'
                                  ? 'border-primary-400 dark:border-primary-500/60 bg-primary-50 dark:bg-primary-900/20'
                                  : 'border-neutral-200 dark:border-white/[0.08] bg-neutral-50 dark:bg-white/[0.02] hover:border-neutral-300 dark:hover:border-white/[0.14]' }}">
                    <input type="radio" wire:model.live="coverageMetric" value="products"
                           class="text-primary-600 border-neutral-300 dark:border-white/[0.1]
                                  bg-neutral-50 dark:bg-white/[0.04] focus:ring-primary-500/30"/>
                    <div class="min-w-0">
                        <p class="text-xs font-semibold text-neutral-800 dark:text-neutral-100">{{ __('Products with data') }}</p>
                        <p class="text-[11px] text-neutral-500 dark:text-neutral-400 mt-0.5 leading-tight">
                            {{ __('Distinct products with at least one estimate') }}
                        </p>
                    </div>
                </label>
            </div>
        </div>
        @endif

        {{-- ── Scrollable middle section ── --}}
        <div class="flex-1 overflow-y-auto p-3 space-y-1.5">

            @if ($mapMode === 'price')

                {{-- Basket items --}}
                @forelse ($basket as $item)
                    <div class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm border-l-[3px]
                                bg-neutral-50 dark:bg-white/[0.03] hover:bg-neutral-100 dark:hover:bg-white/[0.06]
                                transition group"
                         style="border-color: {{ $item['category_color'] }}">
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-neutral-800 dark:text-neutral-100 truncate text-xs leading-snug">
                                {{ $item['name'] }}
                            </p>
                            <p class="text-[11px] text-neutral-500 dark:text-neutral-400 mt-0.5 flex items-baseline gap-1">
                                <span>× {{ $item['quantity'] }}</span>
                                @if ($item['unit'])
                                    <span class="text-sm font-bold text-neutral-700 dark:text-neutral-200">{{ $item['unit'] }}</span>
                                @endif
                            </p>
                        </div>
                        <button
                            wire:click="removeFromBasket({{ $item['product_id'] }})"
                            class="flex-shrink-0 p-1 rounded-md opacity-0 group-hover:opacity-100
                                   text-neutral-400 hover:text-error-500 hover:bg-error-50 dark:hover:bg-error-900/20
                                   transition focus-visible:opacity-100 focus-visible:outline-none"
                        >
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                @empty
                    <div class="flex flex-col items-center justify-center py-10 text-center">
                        <div class="w-10 h-10 rounded-xl bg-neutral-100 dark:bg-white/[0.04] flex items-center justify-center mb-3">
                            <svg class="h-5 w-5 text-neutral-400 dark:text-neutral-500"
                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                      d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                        </div>
                        <p class="text-xs font-medium text-neutral-600 dark:text-neutral-400">
                            {{ __('Basket is empty') }}
                        </p>
                        <p class="text-[11px] text-neutral-400 dark:text-neutral-500 mt-0.5 max-w-[160px]">
                            {{ __('Add products to compare prices across cities') }}
                        </p>
                    </div>
                @endforelse

                @if (!empty($basket) && empty($results))
                    <div class="rounded-lg border border-dashed border-neutral-300 dark:border-white/[0.1]
                                bg-neutral-50 dark:bg-white/[0.02] px-3 py-2.5 text-[11px]
                                text-neutral-500 dark:text-neutral-400 flex items-start gap-2 mt-2">
                        <svg class="h-3.5 w-3.5 flex-shrink-0 mt-0.5 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 110 20A10 10 0 0112 2z"/>
                        </svg>
                        {{ __('Press "Compute" to show results on the map') }}
                    </div>
                @endif

            @elseif ($mapMode === 'coverage')

                @if (empty($results))
                    <div class="flex flex-col items-center justify-center py-10 text-center">
                        <div class="w-10 h-10 rounded-xl bg-neutral-100 dark:bg-white/[0.04] flex items-center justify-center mb-3">
                            <svg class="h-5 w-5 text-neutral-400 dark:text-neutral-500"
                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                      d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064"/>
                            </svg>
                        </div>
                        <p class="text-xs font-medium text-neutral-600 dark:text-neutral-400">
                            {{ __('No data yet') }}
                        </p>
                        <p class="text-[11px] text-neutral-400 dark:text-neutral-500 mt-0.5 max-w-[160px]">
                            {{ __('Press "Compute" to visualize coverage across cities') }}
                        </p>
                    </div>

                @elseif ($coverageMetric === 'estimates')
                    {{-- Estimates metric: simple submission counts --}}
                    <div class="rounded-lg border border-neutral-200 dark:border-white/[0.08]
                                bg-neutral-50 dark:bg-white/[0.02] p-3 space-y-3">
                        <p class="text-[10px] font-bold uppercase tracking-widest text-neutral-500 dark:text-neutral-400">
                            {{ __('Summary') }}
                        </p>
                        <div class="flex items-baseline gap-1.5">
                            <span class="text-2xl font-bold text-neutral-900 dark:text-neutral-100 tabular-nums">
                                {{ count($results) }}
                            </span>
                            <span class="text-xs text-neutral-500 dark:text-neutral-400">{{ __('cities with data') }}</span>
                        </div>
                        <div class="flex items-baseline gap-1.5">
                            <span class="text-2xl font-bold text-neutral-900 dark:text-neutral-100 tabular-nums">
                                {{ number_format(collect($results)->sum('value')) }}
                            </span>
                            <span class="text-xs text-neutral-500 dark:text-neutral-400">{{ __('total submissions') }}</span>
                        </div>
                    </div>

                @elseif ($coverageMetric === 'products' && !empty($coverageSummary))
                    {{-- Products metric: outlier-aware counts + top products --}}
                    <div class="rounded-lg border border-neutral-200 dark:border-white/[0.08]
                                bg-neutral-50 dark:bg-white/[0.02] p-3 space-y-3">
                        <p class="text-[10px] font-bold uppercase tracking-widest text-neutral-500 dark:text-neutral-400">
                            {{ __('Summary') }}
                        </p>

                        {{-- Cities with complete coverage --}}
                        <div>
                            <div class="flex items-baseline gap-1.5">
                                <span class="text-2xl font-bold text-neutral-900 dark:text-neutral-100 tabular-nums">
                                    {{ $coverageSummary['full_coverage_cities'] }}
                                </span>
                                <span class="text-xs text-neutral-500 dark:text-neutral-400 leading-tight">
                                    {{ __('cities with all') }}
                                    <strong class="text-neutral-700 dark:text-neutral-300">{{ $coverageSummary['total_products'] }}</strong>
                                    {{ __('products') }}
                                </span>
                            </div>
                            <p class="text-[11px] text-neutral-400 dark:text-neutral-500 mt-0.5">
                                {{ count($results) }} {{ __('cities have at least one product') }}
                            </p>
                        </div>

                        {{-- Top 5 products by city breadth --}}
                        @if (!empty($coverageSummary['top_products']))
                            <div class="border-t border-neutral-200 dark:border-white/[0.08] pt-3">
                                <p class="text-[10px] font-bold uppercase tracking-widest text-neutral-500 dark:text-neutral-400 mb-2">
                                    {{ __('Widest coverage') }}
                                </p>
                                @foreach ($coverageSummary['top_products'] as $i => $product)
                                    <div class="flex items-center gap-2 py-1">
                                        <span class="text-[10px] font-bold tabular-nums w-4 text-right
                                                     text-neutral-400 dark:text-neutral-500 flex-shrink-0">
                                            {{ $i + 1 }}
                                        </span>
                                        <span class="flex-1 text-xs text-neutral-700 dark:text-neutral-300 truncate">
                                            {{ $product['name'] }}
                                        </span>
                                        <span class="text-xs font-semibold tabular-nums
                                                     text-neutral-900 dark:text-neutral-100 flex-shrink-0">
                                            {{ $product['city_count'] }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endif

            @endif

            {{-- Stale warning (shared, mode-aware text) --}}
            @if ($resultsStale)
                <div class="rounded-lg border border-warning-300 dark:border-warning-500/40
                            bg-warning-50 dark:bg-warning-900/20 px-3 py-2.5 text-[11px]
                            text-warning-700 dark:text-warning-300 flex items-start gap-2 mt-2">
                    <svg class="h-3.5 w-3.5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                    </svg>
                    @if ($mapMode === 'price')
                        {{ __('Basket has changed, press "Compute prices" to update.') }}
                    @else
                        {{ __('Settings have changed, press "Compute coverage" to update.') }}
                    @endif
                </div>
            @endif

        </div>

        {{-- ── Bottom controls ── --}}
        <div class="p-4 border-t border-neutral-200 dark:border-white/[0.06]">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-medium text-neutral-500 dark:text-neutral-400">{{ __('Period') }}</span>
                <select
                    wire:model.live="days"
                    class="text-xs rounded-lg border border-neutral-300 dark:border-white/[0.12]
                           bg-neutral-50 dark:bg-[#222638] text-neutral-900 dark:text-neutral-100
                           focus:ring-2 focus:ring-primary-500/30 focus:border-primary-500 transition
                           py-1 pl-2 pr-6"
                >
                    <option value="30">{{ __('Last 30 days') }}</option>
                    <option value="90">{{ __('Last 3 months') }}</option>
                    <option value="180">{{ __('Last 6 months') }}</option>
                    <option value="365">{{ __('Last year') }}</option>
                    <option value="0">{{ __('All time') }}</option>
                </select>
            </div>
            <label class="flex items-center gap-2 mb-3 cursor-pointer select-none">
                <input
                    type="checkbox"
                    wire:model.live="recomputeOnChange"
                    @change="localStorage.setItem('map-recompute-on-change', $event.target.checked.toString())"
                    class="rounded text-primary-600 border-neutral-300 dark:border-white/[0.1]
                           bg-neutral-50 dark:bg-white/[0.04] focus:ring-primary-500/30"
                />
                <span class="text-xs text-neutral-600 dark:text-neutral-400">
                    {{ __('Recompute on change') }}
                </span>
            </label>
            <button
                wire:click="compute"
                wire:loading.attr="disabled"
                wire:loading.class="opacity-50 cursor-not-allowed"
                class="w-full py-2.5 bg-primary-600 hover:bg-primary-700 active:bg-primary-800
                       text-white text-sm font-semibold rounded-lg transition
                       flex items-center justify-center gap-2
                       focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500
                       disabled:opacity-50 disabled:cursor-not-allowed"
            >
                <svg wire:loading class="animate-spin h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                <span wire:loading.remove>
                    @if ($mapMode === 'price')
                        {{ __('Compute prices') }}
                    @else
                        {{ __('Compute coverage') }}
                    @endif
                </span>
                <span wire:loading>{{ __('Computing…') }}</span>
            </button>

            @if ($error)
                <p class="text-[16px] mt-2 text-center text-error-500 dark:text-error-400">{{ $error }}</p>
            @endif
        </div>
        </div>{{-- /inner wrapper --}}
    </div>{{-- /sidebar --}}

    {{-- ─── Resize handle (desktop only) ─── --}}
    <div x-show="sidebarOpen" x-cloak
         class="hidden sm:flex w-2 shrink-0 relative cursor-col-resize z-40 group items-stretch"
         @mousedown.prevent="startResize($event)">
        <div class="absolute inset-y-0 left-1/2 -translate-x-1/2 w-0.5
                    bg-neutral-200 dark:bg-white/[0.08]
                    group-hover:bg-primary-400 group-hover:w-1
                    opacity-0 group-hover:opacity-100
                    transition-all duration-150 rounded-full">
        </div>
    </div>

    {{-- ─── Map area ─── --}}
    <div class="flex-1 relative min-w-0">
        <div id="map" wire:ignore class="w-full h-full z-0"></div>

        {{-- Legend (bottom-right) --}}
        @if (!empty($results))
            @php
                $minVal = collect($results)->min('value');
                $maxVal = collect($results)->max('value');
            @endphp
            <div class="absolute bottom-4 right-4 z-[500]
                        bg-white dark:bg-[#1a1e2d]
                        border border-neutral-200 dark:border-white/[0.1]
                        rounded-xl shadow-card-md p-3 w-48">
                <p class="text-[10px] font-semibold uppercase tracking-wider text-neutral-500 dark:text-neutral-400 mb-2">
                    @if ($mapMode === 'price')
                        {{ __('Basket total') }}
                    @elseif ($coverageMetric === 'products')
                        {{ __('Products with data') }}
                    @else
                        {{ __('Submissions') }}
                    @endif
                </p>
                <div class="w-full h-2 rounded-full mb-2"
                     style="background: linear-gradient(to right, {{ $colorMin }}, {{ $colorMax }})">
                </div>
                <div class="flex items-center justify-between text-xs tabular-nums">
                    @if ($mapMode === 'price')
                        @php $currency = auth()->user()->effectiveCurrency(); @endphp
                        <div class="flex items-center gap-1">
                            <span class="w-2 h-2 rounded-full" style="background: {{ $colorMin }}"></span>
                            <span class="text-neutral-700 dark:text-neutral-200">{{ $currency->format($minVal) }}</span>
                        </div>
                        <div class="flex items-center gap-1">
                            <span class="text-neutral-700 dark:text-neutral-200">{{ $currency->format($maxVal) }}</span>
                            <span class="w-2 h-2 rounded-full" style="background: {{ $colorMax }}"></span>
                        </div>
                    @else
                        <div class="flex items-center gap-1">
                            <span class="w-2 h-2 rounded-full" style="background: {{ $colorMin }}"></span>
                            <span class="text-neutral-700 dark:text-neutral-200">{{ $minVal }}</span>
                        </div>
                        <div class="flex items-center gap-1">
                            <span class="text-neutral-700 dark:text-neutral-200">{{ $maxVal }}</span>
                            <span class="w-2 h-2 rounded-full" style="background: {{ $colorMax }}"></span>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        {{-- ─── Grouped bottom-left controls: sidebar toggle + display widget ─── --}}
        <div class="absolute bottom-4 left-4 z-[500] flex flex-col items-start gap-2">

        {{-- Sidebar toggle --}}
        <button
            @click="sidebarOpen = !sidebarOpen; setTimeout(() => window.dispatchEvent(new CustomEvent('sidebar-toggled')), 310)"
            :title="sidebarOpen ? '{{ __('Hide sidebar') }} ([)' : '{{ __('Show sidebar') }} ([)'"
            :class="sidebarOpen
                ? 'bg-white dark:bg-[#1a1e2d] border-neutral-200 dark:border-white/[0.1] text-neutral-700 dark:text-neutral-200 hover:border-neutral-300 dark:hover:border-white/[0.18]'
                : 'bg-primary-600 border-primary-600 text-white hover:bg-primary-700'"
            class="inline-flex items-center gap-2 px-3 py-2 text-xs font-semibold rounded-xl
                   border shadow-card transition"
        >
            <svg class="h-3.5 w-3.5 transition-transform duration-300"
                 :class="sidebarOpen ? '' : 'rotate-180'"
                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            <span x-text="sidebarOpen ? '{{ __('Hide') }}' : '{{ __('Show') }}'"></span>
        </button>

        {{-- Display widget --}}
        <div
            x-data="{ open: false, opacity: 0.85, stroke: 2, scale: 10 }"
            class="relative"
        >
            {{-- Expandable panel (opens upward) --}}
            <div
                x-show="open"
                x-cloak
                x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0 translate-y-1"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-100"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 translate-y-1"
                class="absolute bottom-full mb-2 left-0 w-64 origin-bottom-left
                       bg-white dark:bg-[#1a1e2d]
                       border border-neutral-200 dark:border-white/[0.1]
                       rounded-xl shadow-card-md p-4 space-y-4"
            >
                {{-- Marker style --}}
                <div class="space-y-3">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-neutral-500 dark:text-neutral-400">
                        {{ __('Marker') }}
                    </p>
                    <div>
                        <div class="flex items-center justify-between text-xs text-neutral-700 dark:text-neutral-300 mb-1.5">
                            <label>{{ __('Opacity') }}</label>
                            <span class="tabular-nums font-mono text-neutral-900 dark:text-neutral-100"
                                  x-text="Math.round(opacity * 100) + '%'"></span>
                        </div>
                        <input type="range" x-model="opacity" min="0.2" max="1" step="0.05"
                               @input="$dispatch('marker-style-changed', { opacity: parseFloat(opacity), stroke: parseFloat(stroke), scale: parseFloat(scale) })"
                               class="w-full h-1.5 accent-primary-500 cursor-pointer"/>
                    </div>
                    <div>
                        <div class="flex items-center justify-between text-xs text-neutral-700 dark:text-neutral-300 mb-1.5">
                            <label>{{ __('Stroke') }}</label>
                            <span class="tabular-nums font-mono text-neutral-900 dark:text-neutral-100"
                                  x-text="stroke + 'px'"></span>
                        </div>
                        <input type="range" x-model="stroke" min="0" max="6" step="0.5"
                               @input="$dispatch('marker-style-changed', { opacity: parseFloat(opacity), stroke: parseFloat(stroke), scale: parseFloat(scale) })"
                               class="w-full h-1.5 accent-primary-500 cursor-pointer"/>
                    </div>
                    <div>
                        <div class="flex items-center justify-between text-xs text-neutral-700 dark:text-neutral-300 mb-1.5">
                            <label>{{ __('Scale') }}</label>
                            <span class="tabular-nums font-mono text-neutral-900 dark:text-neutral-100"
                                  x-text="scale + 'px'"></span>
                        </div>
                        <input type="range" x-model="scale" min="3" max="30" step="1"
                               @input="$dispatch('marker-style-changed', { opacity: parseFloat(opacity), stroke: parseFloat(stroke), scale: parseFloat(scale) })"
                               class="w-full h-1.5 accent-primary-500 cursor-pointer"/>
                    </div>
                </div>

                {{-- Color scale --}}
                <div class="space-y-3 border-t border-neutral-200 dark:border-white/[0.08] pt-3">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-neutral-500 dark:text-neutral-400">
                        {{ __('Color scale') }}
                    </p>
                    <select
                        wire:model.live="colorScale"
                        class="w-full text-sm rounded-lg
                               border border-neutral-300 dark:border-white/[0.12]
                               bg-neutral-50 dark:bg-[#222638]
                               text-neutral-900 dark:text-neutral-100
                               focus:ring-2 focus:ring-primary-500/30 focus:border-primary-500 transition"
                    >
                        @foreach ($colorScales as $key => $scale)
                            <option value="{{ $key }}">{{ $scale['label'] }}</option>
                        @endforeach
                    </select>

                    @if ($colorScale === 'custom')
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <p class="text-xs text-neutral-600 dark:text-neutral-400 mb-1">{{ __('Min') }}</p>
                                <input type="color" wire:model.live="colorMin"
                                       class="h-8 w-full rounded-lg cursor-pointer border border-neutral-300 dark:border-white/[0.1]"/>
                            </div>
                            <div>
                                <p class="text-xs text-neutral-600 dark:text-neutral-400 mb-1">{{ __('Max') }}</p>
                                <input type="color" wire:model.live="colorMax"
                                       class="h-8 w-full rounded-lg cursor-pointer border border-neutral-300 dark:border-white/[0.1]"/>
                            </div>
                        </div>
                    @endif

                    {{-- Scale preview --}}
                    <div class="w-full h-2 rounded-full"
                         style="background: linear-gradient(to right, {{ $colorMin }}, {{ $colorMax }})">
                    </div>
                </div>
            </div>

            {{-- Toggle button --}}
            <button
                @click="open = !open"
                :class="open ? 'bg-primary-50 dark:bg-primary-900/30 border-primary-400 dark:border-primary-500/60' : 'bg-white dark:bg-[#1a1e2d] border-neutral-200 dark:border-white/[0.1]'"
                class="inline-flex items-center gap-2 px-3 py-2 text-xs font-semibold rounded-xl
                       border shadow-card
                       text-neutral-700 dark:text-neutral-200
                       hover:border-neutral-300 dark:hover:border-white/[0.18] transition"
            >
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                </svg>
                {{ __('Display') }}
                <svg class="h-3 w-3 transition-transform duration-150" :class="{'rotate-180': open}"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
        </div>{{-- /display widget --}}
        </div>{{-- /grouped bottom-left controls --}}

    </div>{{-- /map area --}}

</div>

@push('scripts')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
(function () {
    const map = L.map('map').setView([20, 10], 2);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 18,
    }).addTo(map);

    let markers         = [];
    let currentResults  = [];
    let currentStyle    = { opacity: 0.85, stroke: 2, scale: 10 };
    let currentColorMin = '#22c55e';
    let currentColorMax = '#ef4444';

    function hexToRgb(hex) {
        return [parseInt(hex.slice(1,3),16), parseInt(hex.slice(3,5),16), parseInt(hex.slice(5,7),16)];
    }

    function interpolateColor(value, min, max, hexMin, hexMax) {
        if (max === min) return hexMin;
        const t = (value - min) / (max - min);
        const [r1,g1,b1] = hexToRgb(hexMin);
        const [r2,g2,b2] = hexToRgb(hexMax);
        return `rgb(${Math.round(r1+t*(r2-r1))},${Math.round(g1+t*(g2-g1))},${Math.round(b1+t*(b2-b1))})`;
    }

    function drawMarkers(results, style, hexMin, hexMax) {
        markers.forEach(m => m.remove());
        markers = [];
        const values = results.map(r => r.value);
        const min = Math.min(...values);
        const max = Math.max(...values);

        results.forEach(r => {
            const color = interpolateColor(r.value, min, max, hexMin, hexMax);
            const marker = L.circleMarker([r.lat, r.lng], {
                radius:      style.scale ?? 10,
                fillColor:   color,
                color:       color,
                weight:      style.stroke,
                fillOpacity: style.opacity,
                opacity:     1,
            }).addTo(map);
            marker.bindPopup(r.popup_html);
            markers.push(marker);
        });
    }

    window.addEventListener('markers-updated', e => {
        currentResults  = e.detail.results;
        currentColorMin = e.detail.colorMin ?? '#22c55e';
        currentColorMax = e.detail.colorMax ?? '#ef4444';
        drawMarkers(currentResults, currentStyle, currentColorMin, currentColorMax);
    });

    window.addEventListener('markers-cleared', () => {
        markers.forEach(m => m.remove());
        markers = [];
        currentResults = [];
    });

    window.addEventListener('marker-style-changed', e => {
        currentStyle = e.detail;
        if (currentResults.length > 0) {
            drawMarkers(currentResults, currentStyle, currentColorMin, currentColorMax);
        }
    });

    window.addEventListener('sidebar-toggled', () => {
        map.invalidateSize();
    });
})();
</script>
@endpush
