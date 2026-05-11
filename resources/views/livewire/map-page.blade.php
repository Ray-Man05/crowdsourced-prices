<div class="flex h-[calc(100vh-4rem)] overflow-hidden">

    {{-- Sidebar --}}
    <div class="w-80 flex-shrink-0 bg-white dark:bg-neutral-800 border-r border-neutral-200
                dark:border-neutral-700 flex flex-col overflow-hidden">

        {{-- Product picker --}}
        <div class="p-4 border-b border-neutral-200 dark:border-neutral-700">
            <h2 class="text-sm font-semibold text-neutral-800 dark:text-neutral-100 mb-3">
                {{ __('Build your basket') }}
            </h2>
{{-- 
            <select
                wire:model="selectedProductId"
                class="w-full text-sm rounded-lg border-neutral-300 dark:border-neutral-600
                    bg-white dark:bg-neutral-800 text-neutral-800 dark:text-neutral-100
                    focus:ring focus:ring-primary-300 focus:border-primary-500 mb-2"
            >
                <option value="0">{{ __('Select a product...') }}</option>

                @foreach ($categories as $category)
                    <optgroup label="{{ $category->name }}">
                        @foreach ($category->products as $product)
                            <option value="{{ $product->id }}">
                                {{ $product->name }}
                                @if ($product->unit)
                                    ({{ $product->unit->symbol }})
                                @endif
                            </option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>

     --}}

                <div
                    x-data="{
                        open: false,
                        search: '',
                        selectedProductId: null,
                        selectedProductName: '',
                        locale: document.documentElement.lang ?? 'en',
                        products: {{ Js::from($categories) }},

                        getName(obj) {
                            if (!obj) return '';
                            if (typeof obj === 'string') return obj;
                            return obj[this.locale] ?? obj.en ?? Object.values(obj)[0] ?? '';
                        },

                        get filteredCategories() {
                            return this.products.map(category => {
                                return {
                                    ...category,
                                    products: category.products.filter(p =>
                                        !this.search ||
                                        this.getName(p.name).toLowerCase().includes(this.search.toLowerCase())
                                    )
                                };
                            }).filter(category => category.products.length > 0);
                        },

                        selectProduct(product) {
                            this.selectedProductId = product.id;

                            const name = this.getName(product.name);

                            this.search = name; // IMPORTANT: drive UI from single source of truth
                            this.open = false;

                            $wire.set('selectedProductId', product.id);
                        }
                    }"
                    class="relative"
                >
                    {{-- Input --}}
                    <input
                        type="text"
                        x-model="search"
                        @focus="open = true"
                        @click.outside="open = false"
                        @input="open = true"
                        placeholder="Select a product..."
                        class="w-full text-sm rounded-lg border-neutral-300 dark:border-neutral-600
                            bg-white dark:bg-neutral-800 text-neutral-800 dark:text-neutral-100
                            focus:ring focus:ring-primary-300 focus:border-primary-500 mb-2"
                    />

                    {{-- Hidden field --}}
                    <input type="hidden" :value="selectedProductId">

                    {{-- Dropdown --}}
                    <div
                        x-show="open"
                        x-cloak
                        class="absolute z-10 w-full mt-1 bg-white dark:bg-neutral-800 border border-neutral-200 dark:border-neutral-700 rounded-lg shadow-lg max-h-60 overflow-y-auto"
                    >
                        <template x-for="category in filteredCategories" :key="category.id">
                            <div>

                                {{-- Category header with custom color --}}
                                <div
                                    class="px-3 py-2 text-xs font-semibold uppercase"
                                    :style="'color: ' + (category.color ?? '#9ca3af')"
                                >
                                    <span x-text="getName(category.name)"></span>
                                </div>

                                {{-- Products --}}
                                <template x-for="product in category.products" :key="product.id">
                                    <div
                                        @click="selectProduct(product)"
                                        class="px-4 py-2 text-sm cursor-pointer hover:bg-neutral-100 dark:hover:bg-neutral-700"
                                    >
                                        <span x-text="getName(product.name)" class="text-neutral-800 dark:text-neutral-200"></span>
                                        <template x-if="product.unit">
                                            <span class="text-neutral-400">
                                                (<span x-text="product.unit.symbol"></span>)
                                            </span>
                                        </template>
                                    </div>
                                </template>

                            </div>
                        </template>
                    </div>
                </div>




                <div class="flex gap-2">
                    <input
                        type="number"
                        wire:model="selectedQuantity"
                        min="0.01"
                        step="0.01"
                        class="w-24 text-sm rounded-lg border-neutral-300 dark:border-neutral-600
                            bg-white dark:bg-neutral-800 text-neutral-800 dark:text-neutral-100
                            focus:ring focus:ring-primary-300"
                    />
                    <button
                        wire:click="addToBasket"
                        class="flex-1 px-3 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition"
                        {{-- style="background-color: var(--color-theme-primary)" --}}
                    >
                        {{ __('Add') }}
                    </button>
                </div>
            </div>

        {{-- Basket items --}}
        <div class="flex-1 overflow-y-auto p-4 space-y-2">
            @forelse ($basket as $item)
                <div 
                    class="flex items-center justify-between bg-neutral-50 dark:bg-neutral-700/50
                        rounded-lg px-3 py-2 text-sm border-l-4"
                    style="border-color: {{ $item['category_color'] }};"
                >
                    <div class="flex-1 min-w-0">
                        <p class="font-medium text-neutral-800 dark:text-neutral-100 truncate" >
                            {{ $item['name'] }}
                        </p>
                        <p class="text-xs text-neutral-500 dark:text-neutral-400">
                            {{ $item['quantity'] }} {{ $item['unit'] }}
                        </p>
                    </div>
                    <button
                        wire:click="removeFromBasket({{ $item['product_id'] }})"
                        class="ml-2 hover:opacity-75 transition"
                        style="color: var(--color-theme-error)"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            @empty
                <div class="flex flex-col items-center justify-center py-8 text-center gap-2">
                    <svg class="h-8 w-8 text-neutral-300 dark:text-neutral-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    <p class="text-sm text-neutral-500 dark:text-neutral-400">
                        {{ __('Your basket is empty') }}
                    </p>
                    <p class="text-xs text-neutral-400 dark:text-neutral-500">
                        {{ __('Add products above to compare prices across cities') }}
                    </p>
                </div>
            @endforelse

            {{-- Hint shown after at least one item is added --}}
            @if (!empty($basket) && empty($results))
                <div class="mt-2 rounded-lg border border-dashed border-neutral-300 dark:border-neutral-600
                            bg-neutral-50 dark:bg-neutral-700/30 px-3 py-2 text-xs text-neutral-500 dark:text-neutral-400
                            flex items-center gap-2">
                    <svg class="h-4 w-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 110 20A10 10 0 0112 2z"/>
                    </svg>
                    {{ __('Hit "Compute prices" to display results on the map') }}
                </div>
            @endif
        </div>

        {{-- Marker style controls --}}
        <div class="p-4 border-t border-neutral-200 dark:border-neutral-700 space-y-3"
             x-data="{ opacity: 0.85, stroke: 2 }">
            <h3 class="text-xs font-semibold uppercase tracking-wide text-neutral-500 dark:text-neutral-400">
                {{ __('Marker style') }}
            </h3>
            <div class="flex items-center justify-between text-sm text-neutral-700 dark:text-neutral-300">
                <label>{{ __('Opacity') }}</label>
                <input type="range" x-model="opacity" min="0.2" max="1" step="0.05"
                       @input="$dispatch('marker-style-changed', { opacity: parseFloat(opacity), stroke: parseFloat(stroke) })"
                       class="w-32 accent-primary-500"/>
            </div>
            <div class="flex items-center justify-between text-sm text-neutral-700 dark:text-neutral-300">
                <label>{{ __('Stroke') }}</label>
                <input type="range" x-model="stroke" min="0" max="6" step="0.5"
                       @input="$dispatch('marker-style-changed', { opacity: parseFloat(opacity), stroke: parseFloat(stroke) })"
                       class="w-32 accent-primary-500"/>
            </div>
        </div>

        {{-- Color scale --}}
        <div class="p-4 border-t border-neutral-200 dark:border-neutral-700 space-y-3">
            <h3 class="text-xs font-semibold uppercase tracking-wide text-neutral-500 dark:text-neutral-400">
                {{ __('Color scale') }}
            </h3>

            <select
                wire:model.live="colorScale"
                class="w-full text-sm rounded-lg border-neutral-300 dark:border-neutral-600
                       bg-white dark:bg-neutral-800 text-neutral-800 dark:text-neutral-100
                       focus:ring focus:ring-primary-300"
            >
                @foreach ($colorScales as $key => $scale)
                    <option value="{{ $key }}">{{ $scale['label'] }}</option>
                @endforeach
            </select>

            @if ($colorScale === 'custom')
                <div class="flex items-center justify-between text-sm text-neutral-700 dark:text-neutral-300">
                    <label>{{ __('Min (cheap)') }}</label>
                    <input type="color" wire:model.live="colorMin"
                           class="h-7 w-16 rounded cursor-pointer border-0 bg-transparent"/>
                </div>
                <div class="flex items-center justify-between text-sm text-neutral-700 dark:text-neutral-300">
                    <label>{{ __('Max (expensive)') }}</label>
                    <input type="color" wire:model.live="colorMax"
                           class="h-7 w-16 rounded cursor-pointer border-0 bg-transparent"/>
                </div>
            @endif
        </div>

        {{-- Compute button --}}
        <div class="p-4 border-t border-neutral-200 dark:border-neutral-700">
            <button
                wire:click="compute"
                wire:loading.attr="disabled"
                wire:loading.class="opacity-50 cursor-not-allowed"
                class="w-full py-2.5 bg-primary-600 hover:bg-primary-700 text-white text-sm font-semibold rounded-lg transition"
            >
                <span wire:loading.remove>{{ __('Compute prices') }}</span>
                <span wire:loading>{{ __('Computing...') }}</span>
            </button>

            @if ($error)
            
                <p class="text-xs mt-2 text-center text-error-500" style="color: var(--color-theme-error)">
                    {{ $error }}
                </p>
            @endif
        </div>
    </div>

    {{-- Map --}}
    <div class="flex-1 relative">
        <div id="map" wire:ignore class="w-full h-full z-0"></div>

        {{-- Legend --}}
        @if (!empty($results))
            @php
                $minTotal = collect($results)->min('total');
                $maxTotal = collect($results)->max('total');
                $currency = auth()->user()->effectiveCurrency();
            @endphp
            <div class="absolute bottom-6 right-4 z-10 bg-white dark:bg-neutral-800 rounded-xl
                        shadow-lg border border-neutral-200 dark:border-neutral-700 p-3 w-52">
                <p class="text-xs font-semibold text-neutral-700 dark:text-neutral-200 mb-2">
                    {{ __('Basket total') }}
                </p>

                {{-- Gradient bar using the current scale colors --}}
                <div class="w-full h-3 rounded-full mb-1"
                     style="background: linear-gradient(to right, {{ $colorMin }}, {{ $colorMax }})">
                </div>

                <div class="flex items-center justify-between text-xs">
                    <div class="flex items-center gap-1">
                        <span class="inline-block w-2.5 h-2.5 rounded-full"
                              style="background: {{ $colorMin }}"></span>
                        <span class="text-neutral-600 dark:text-neutral-300">{{ $currency->format($minTotal) }}</span>
                    </div>
                    <div class="flex items-center gap-1">
                        <span class="text-neutral-600 dark:text-neutral-300">{{ $currency->format($maxTotal) }}</span>
                        <span class="inline-block w-2.5 h-2.5 rounded-full"
                              style="background: {{ $colorMax }}"></span>
                    </div>
                </div>

                @if (collect($results)->contains('complete', false))
                    <p class="text-xs text-neutral-400 dark:text-neutral-500 mt-2 italic border-t border-neutral-100 dark:border-neutral-700 pt-2">
                        * {{ __('Partial data') }}
                    </p>
                @endif
            </div>
        @endif
    </div>

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

    let markers       = [];
    let currentResults  = [];
    let currentStyle    = { opacity: 0.85, stroke: 2 };
    let currentColorMin = '#22c55e';
    let currentColorMax = '#ef4444';

    function hexToRgb(hex) {
        return [
            parseInt(hex.slice(1, 3), 16),
            parseInt(hex.slice(3, 5), 16),
            parseInt(hex.slice(5, 7), 16),
        ];
    }

    function priceColor(value, min, max, hexMin, hexMax) {
        if (max === min) return hexMin;
        const t          = (value - min) / (max - min);
        const [r1,g1,b1] = hexToRgb(hexMin);
        const [r2,g2,b2] = hexToRgb(hexMax);
        return `rgb(${Math.round(r1 + t*(r2-r1))},${Math.round(g1 + t*(g2-g1))},${Math.round(b1 + t*(b2-b1))})`;
    }

    function drawMarkers(results, style, hexMin, hexMax) {
        markers.forEach(m => m.remove());
        markers = [];

        const totals = results.map(r => r.total);
        const min    = Math.min(...totals);
        const max    = Math.max(...totals);

        results.forEach(r => {
            const color  = priceColor(r.total, min, max, hexMin, hexMax);
            const marker = L.circleMarker([r.lat, r.lng], {
                radius:      10,
                fillColor:   color,
                color:       color,
                weight:      style.stroke,
                fillOpacity: style.opacity,
                opacity:     1,
            }).addTo(map);

            marker.bindPopup(
                `<strong>${r.city_name}</strong>, ${r.country}<br>` +
                `${r.symbol}${r.total.toFixed(2)}` +
                (r.complete ? '' : ' <em>({{ __('partial') }})</em>')
            );

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
        markers      = [];
        currentResults = [];
    });

    window.addEventListener('marker-style-changed', e => {
        currentStyle = e.detail;
        if (currentResults.length > 0) {
            drawMarkers(currentResults, currentStyle, currentColorMin, currentColorMax);
        }
    });
})();
</script>
@endpush