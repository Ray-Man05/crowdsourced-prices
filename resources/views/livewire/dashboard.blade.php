<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

    {{-- Header --}}
    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold tracking-tight text-neutral-900 dark:text-neutral-50">
                {{ __('Welcome back') }}, {{ $user->name }}
            </h1>
            <p class="mt-0.5 text-sm text-neutral-500 dark:text-neutral-400 flex items-center gap-1.5">
                <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                {{ $user->city?->name ?? __('No city set') }}
                @if ($user->city)
                    <span class="text-neutral-300 dark:text-neutral-600">·</span>
                    {{ $user->city->country->name }}
                @endif
            </p>
        </div>
        <a href="{{ route('catalog') }}"
           class="shrink-0 inline-flex items-center gap-1 text-xs font-semibold
                  text-primary-600 dark:text-primary-400
                  hover:text-primary-700 dark:hover:text-primary-300 transition">
            {{ __('Browse products') }}
            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </a>
    </div>

    {{-- Stats strip --}}
    @php
        $onCooldown = collect($recentEstimates)->filter(fn($r) => $r['cooldown_ends'] > now())->count();
    @endphp
    <div class="grid grid-cols-3 gap-4">
        @foreach ([
            ['label' => __('Total estimates'), 'value' => $totalEstimates,            'sub' => null],
            ['label' => __('This week'),        'value' => $recentEstimates->count(),  'sub' => null],
            ['label' => __('On cooldown'),      'value' => $onCooldown,                'sub' => __('products')],
        ] as $stat)
            <div class="bg-surface-card rounded-2xl border border-neutral-200 dark:border-white/[0.06]
                        shadow-card px-5 py-4">
                <p class="text-[10px] font-bold uppercase tracking-widest text-neutral-400 dark:text-neutral-500">
                    {{ $stat['label'] }}
                </p>
                <p class="mt-1.5 text-3xl font-bold tabular-nums tracking-tight
                           text-neutral-900 dark:text-neutral-50">
                    {{ $stat['value'] }}
                </p>
                @if ($stat['sub'])
                    <p class="text-xs text-neutral-400 dark:text-neutral-500 mt-0.5">{{ $stat['sub'] }}</p>
                @endif
            </div>
        @endforeach
    </div>

    {{-- Recent submissions --}}
    <div class="bg-surface-card rounded-2xl border border-neutral-200 dark:border-white/[0.06]
                shadow-card overflow-hidden">

        <div class="px-5 py-4 border-b border-neutral-100 dark:border-white/[0.05]">
            <h2 class="text-sm font-semibold tracking-tight text-neutral-900 dark:text-neutral-100">
                {{ __('Recent submissions') }}
            </h2>
            <p class="text-xs text-neutral-400 dark:text-neutral-500 mt-0.5">
                {{ __('Last :days days · editing unavailable during cooldown', ['days' => \App\Models\PriceEstimate::ESTIMATE_COOLDOWN_DAYS]) }}
            </p>
        </div>

        @if ($recentEstimates->isEmpty())
            <div class="py-14 flex flex-col items-center text-center">
                <div class="w-10 h-10 rounded-xl bg-neutral-100 dark:bg-white/[0.04]
                            flex items-center justify-center mb-3">
                    <svg class="h-5 w-5 text-neutral-300 dark:text-neutral-600"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
                <p class="text-sm font-medium text-neutral-500 dark:text-neutral-400">
                    {{ __('No submissions this week') }}
                </p>
                <a href="{{ route('home') }}"
                   class="mt-2 text-xs text-primary-600 dark:text-primary-400 hover:underline transition">
                    {{ __('Browse products →') }}
                </a>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-neutral-100 dark:border-white/[0.05]">
                            <th class="text-left px-5 py-3 text-[10px] font-bold uppercase tracking-widest
                                       text-neutral-400 dark:text-neutral-500">
                                {{ __('Product') }}
                            </th>
                            <th class="text-right px-5 py-3 text-[10px] font-bold uppercase tracking-widest
                                       text-neutral-400 dark:text-neutral-500">
                                {{ __('Your price') }}
                            </th>
                            <th class="text-right px-5 py-3 text-[10px] font-bold uppercase tracking-widest
                                       text-neutral-400 dark:text-neutral-500 hidden sm:table-cell">
                                {{ __('City avg') }}
                            </th>
                            <th class="px-5 py-3 text-[10px] font-bold uppercase tracking-widest
                                       text-neutral-400 dark:text-neutral-500 hidden md:table-cell">
                                {{ __('Status') }}
                            </th>
                            <th class="text-right px-5 py-3 text-[10px] font-bold uppercase tracking-widest
                                       text-neutral-400 dark:text-neutral-500 hidden sm:table-cell">
                                {{ __('Cooldown') }}
                            </th>
                            <th class="w-14"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-50 dark:divide-white/[0.03]">
                        @foreach ($recentEstimates as $row)
                            @php
                                $positionClasses = match($row['position']) {
                                    'low'     => 'bg-success-50 dark:bg-success-900/20 text-success-700 dark:text-success-400',
                                    'high'    => 'bg-error-50 dark:bg-error-900/20 text-error-700 dark:text-error-400',
                                    'average' => 'bg-neutral-100 dark:bg-white/[0.06] text-neutral-600 dark:text-neutral-300',
                                    default   => 'bg-neutral-50 dark:bg-white/[0.03] text-neutral-400 dark:text-neutral-500',
                                };
                                $positionLabel = match($row['position']) {
                                    'low'     => '↓ ' . __('Below avg'),
                                    'high'    => '↑ ' . __('Above avg'),
                                    'average' => '~ ' . __('On avg'),
                                    default   => __('No data'),
                                };
                                $daysLeft = max(0, (int) ceil(
                                    now()->diffInHours($row['cooldown_ends'], false) / 24
                                ));
                            @endphp
                            <tr class="group hover:bg-neutral-50 dark:hover:bg-white/[0.03] transition-colors">

                                {{-- Product --}}
                                <td class="px-5 py-3">
                                    <a href="{{ route('products.show', $row['estimate']->product) }}"
                                       class="font-medium text-neutral-800 dark:text-neutral-100
                                              hover:text-primary-600 dark:hover:text-primary-400 transition">
                                        {{ $row['estimate']->product->name }}
                                    </a>
                                    <p class="text-xs text-neutral-400 dark:text-neutral-500 mt-0.5
                                               flex items-center gap-1">
                                        <span class="inline-block w-1.5 h-1.5 rounded-full shrink-0"
                                              style="background-color: {{ $row['estimate']->product->category->color }}">
                                        </span>
                                        {{ $row['estimate']->product->category->name }}
                                        @if ($row['estimate']->product->unit)
                                            <span class="text-neutral-300 dark:text-neutral-600">·</span>
                                            {{ $row['estimate']->product->unit->symbol }}
                                        @endif
                                    </p>
                                </td>

                                {{-- Your price --}}
                                <td class="px-5 py-3 text-right font-semibold tabular-nums
                                           text-primary-600 dark:text-primary-400">
                                    {{ $row['symbol'] }}{{ number_format($row['converted_price'] ?? $row['estimate']->price, 2) }}
                                </td>

                                {{-- City average --}}
                                <td class="px-5 py-3 text-right tabular-nums
                                           text-neutral-500 dark:text-neutral-400 hidden sm:table-cell">
                                    @if ($row['city_average'] !== null)
                                        {{ $row['symbol'] }}{{ number_format($row['city_average'], 2) }}
                                    @else
                                        <span class="text-neutral-300 dark:text-neutral-700">—</span>
                                    @endif
                                </td>

                                {{-- Status --}}
                                <td class="px-5 py-3 hidden md:table-cell">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        @if ($row['position'] !== null)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full
                                                         text-[11px] font-medium {{ $positionClasses }}">
                                                {{ $positionLabel }}
                                                @if ($row['deviation'] !== null)
                                                    ({{ number_format(abs($row['deviation']), 1) }}%)
                                                @endif
                                            </span>
                                        @endif
                                        @if ($row['is_outlier'])
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full
                                                         text-[11px] font-medium
                                                         bg-warning-50 dark:bg-warning-900/20
                                                         text-warning-700 dark:text-warning-400">
                                                ⚠ {{ __('Flagged') }}
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                {{-- Cooldown --}}
                                <td class="px-5 py-3 text-right hidden sm:table-cell">
                                    @if ($daysLeft > 0)
                                        <span class="text-xs text-neutral-500 dark:text-neutral-400">
                                            {{ $daysLeft === 1
                                                ? __('1 day left')
                                                : __(':d days left', ['d' => $daysLeft]) }}
                                        </span>
                                    @else
                                        <span class="text-xs text-success-600 dark:text-success-400">
                                            {{ __('Ready') }}
                                        </span>
                                    @endif
                                </td>

                                {{-- Delete --}}
                                <td class="px-5 py-3 text-right">
                                    <button
                                        wire:click="deleteEstimate({{ $row['estimate']->id }})"
                                        wire:confirm="{{ __('Delete this estimate?') }}"
                                        class="text-xs text-neutral-400 dark:text-neutral-500
                                               hover:text-error-500 dark:hover:text-error-400
                                               opacity-0 group-hover:opacity-100 transition
                                               focus-visible:opacity-100 focus-visible:outline-none">
                                        {{ __('Delete') }}
                                    </button>
                                </td>

                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- City comparison --}}
    <div class="bg-surface-card rounded-2xl border border-neutral-200 dark:border-white/[0.06]
                shadow-card overflow-hidden">

        <div class="px-5 py-4 border-b border-neutral-100 dark:border-white/[0.05]
                    flex flex-wrap items-start justify-between gap-4">
            <div>
                <h2 class="text-sm font-semibold tracking-tight text-neutral-900 dark:text-neutral-100">
                    {{ __('How does your city compare?') }}
                </h2>
                <p class="text-xs text-neutral-400 dark:text-neutral-500 mt-0.5">
                    {{ __('30-day averages') }}
                </p>
            </div>

            {{-- Product picker (same pattern as map page) --}}
            <div
                x-data="{
                    open: false,
                    search: {{ Js::from($comparison ? $comparison['product']->name : '') }},
                    activeIndex: -1,
                    locale: document.documentElement.lang ?? 'en',
                    categories: {{ Js::from($categories) }},
                    getName(obj) {
                        if (!obj) return '';
                        if (typeof obj === 'string') return obj;
                        return obj[this.locale] ?? obj.en ?? Object.values(obj)[0] ?? '';
                    },
                    get filteredCategories() {
                        return this.categories.map(c => ({
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
                        this.search = this.getName(product.name);
                        this.open = false;
                        this.activeIndex = -1;
                        $wire.set('comparisonProductId', product.id);
                    }
                }"
                @click.outside="open = false; activeIndex = -1"
                class="relative w-56"
            >
                <div class="relative">
                    <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-neutral-400 pointer-events-none"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input
                        type="text"
                        x-model="search"
                        @focus="open = true"
                        @input="open = true; activeIndex = -1"
                        @keydown.down.prevent="open = true; activeIndex = Math.min(activeIndex + 1, flatProducts.length - 1)"
                        @keydown.up.prevent="activeIndex = activeIndex > 0 ? activeIndex - 1 : activeIndex"
                        @keydown.enter.prevent="if (activeIndex >= 0 && flatProducts[activeIndex]) selectProduct(flatProducts[activeIndex])"
                        @keydown.escape="open = false; activeIndex = -1"
                        placeholder="{{ __('Search products…') }}"
                        class="w-full pl-8 text-sm rounded-lg border-neutral-300 dark:border-white/[0.1]
                               bg-neutral-50 dark:bg-white/[0.04] text-neutral-800 dark:text-neutral-100
                               placeholder-neutral-400 dark:placeholder-neutral-500
                               focus:ring-2 focus:ring-primary-500/30 focus:border-primary-500
                               focus:bg-white dark:focus:bg-white/[0.07] transition"
                    />
                </div>

                <div x-show="open" x-cloak
                     x-transition:enter="transition ease-out duration-100"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     class="absolute right-0 z-20 mt-1 w-64 bg-surface-raised border border-neutral-200
                            dark:border-white/[0.08] rounded-xl shadow-card-md max-h-64 overflow-y-auto">
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
                                     class="px-3 py-2 text-sm cursor-pointer flex items-center
                                            justify-between transition">
                                    <span x-text="getName(product.name)"
                                          class="text-neutral-800 dark:text-neutral-200"></span>
                                    <template x-if="product.unit">
                                        <span class="text-xs text-neutral-400 dark:text-neutral-500 ml-2 shrink-0"
                                              x-text="product.unit.symbol"></span>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </template>
                    <div x-show="filteredCategories.length === 0"
                         class="px-3 py-4 text-sm text-center text-neutral-400 dark:text-neutral-500">
                        {{ __('No products found') }}
                    </div>
                </div>
            </div>
        </div>

        @if ($comparison === null)
            <div class="py-14 flex flex-col items-center text-center">
                <div class="w-10 h-10 rounded-xl bg-neutral-100 dark:bg-white/[0.04]
                            flex items-center justify-center mb-3">
                    <svg class="h-5 w-5 text-neutral-300 dark:text-neutral-600"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
                <p class="text-sm font-medium text-neutral-500 dark:text-neutral-400">
                    {{ __('No price data available for this product') }}
                </p>
                @if (!$user->city)
                    <p class="text-xs text-neutral-400 dark:text-neutral-500 mt-1">
                        {{ __('Set your city in your profile to see a local comparison.') }}
                    </p>
                @endif
            </div>
        @else
            @php
                $bars = [
                    ['label' => $comparison['city']->name,    'value' => $comparison['city_avg'],    'primary' => true],
                    ['label' => $comparison['country']->name, 'value' => $comparison['country_avg'], 'primary' => false],
                    ['label' => __('Global'),                 'value' => $comparison['global_avg'],  'primary' => false],
                ];
                $maxValue = collect($bars)->pluck('value')->filter()->max();
            @endphp

            <div class="px-5 py-5 space-y-4">
                @foreach ($bars as $bar)
                    @if ($bar['value'] !== null)
                        <div>
                            <div class="flex items-baseline justify-between mb-1.5 gap-2">
                                <span class="text-sm truncate {{ $bar['primary']
                                    ? 'font-semibold text-neutral-800 dark:text-neutral-100'
                                    : 'text-neutral-500 dark:text-neutral-400' }}">
                                    {{ $bar['label'] }}
                                </span>
                                <span class="text-sm tabular-nums shrink-0 {{ $bar['primary']
                                    ? 'font-semibold text-neutral-800 dark:text-neutral-100'
                                    : 'text-neutral-500 dark:text-neutral-400' }}">
                                    {{ $comparison['symbol'] }}{{ number_format($bar['value'], 2) }}
                                    @if ($bar['primary'] && $comparison['vs_country'] !== null)
                                        <span class="text-xs font-normal ml-1
                                                     {{ $comparison['vs_country'] > 0 ? 'text-error-500' : 'text-success-500' }}">
                                            {{ $comparison['vs_country'] > 0 ? '+' : '' }}{{ number_format($comparison['vs_country'], 1) }}%
                                        </span>
                                    @endif
                                </span>
                            </div>
                            <div class="w-full bg-neutral-100 dark:bg-white/[0.06] rounded-full h-1.5 overflow-hidden">
                                <div class="h-1.5 rounded-full transition-all duration-500
                                            {{ $bar['primary'] ? 'bg-primary-500' : 'bg-neutral-300 dark:bg-neutral-600' }}"
                                     style="width: {{ $maxValue > 0 ? round(($bar['value'] / $maxValue) * 100) : 0 }}%">
                                </div>
                            </div>
                        </div>
                    @endif
                @endforeach

                @if ($comparison['vs_country'] !== null)
                    <p class="text-xs text-neutral-400 dark:text-neutral-500 pt-3
                               border-t border-neutral-100 dark:border-white/[0.05]">
                        {{ $comparison['city']->name }} {{ __('is') }}
                        <span class="font-semibold {{ $comparison['vs_country'] > 0 ? 'text-error-500' : 'text-success-500' }}">
                            {{ abs(round($comparison['vs_country'], 1)) }}%
                            {{ $comparison['vs_country'] > 0 ? __('above') : __('below') }}
                        </span>
                        {{ __('the country average for') }} {{ $comparison['product']->name }}.
                    </p>
                @endif
                @if ($comparison['vs_global'] !== null)
                    <p class="text-xs text-neutral-400 dark:text-neutral-500 pt-3
                               border-t border-neutral-100 dark:border-white/[0.05]">
                        {{ $comparison['city']->name }} {{ __('is') }}
                        <span class="font-semibold {{ $comparison['vs_global'] > 0 ? 'text-error-500' : 'text-success-500' }}">
                            {{ abs(round($comparison['vs_global'], 1)) }}%
                            {{ $comparison['vs_global'] > 0 ? __('above') : __('below') }}
                        </span>
                        {{ __('the global average for') }} {{ $comparison['product']->name }}.
                    </p>
                @endif
            </div>
        @endif
    </div>

</div>
