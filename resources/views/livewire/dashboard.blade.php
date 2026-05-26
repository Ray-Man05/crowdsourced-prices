<div class="relative min-h-screen">

    @if ($user->city)
        <div id="dashboard-map" wire:ignore
             class="fixed inset-0 z-0 pointer-events-none"></div>

        <div class="fixed inset-0 z-10 pointer-events-none dashboard-overlay"></div>
    @endif

    <div class="relative z-20 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-5">

        {{-- Header --}}
        <div class="relative rounded-2xl overflow-hidden
                    border border-neutral-200/80 dark:border-white/[0.06] shadow-card">
            <div class="absolute inset-0 backdrop-blur-sm transition-colors duration-300
                        bg-white dark:bg-[#12151f]"></div>
            <div class="relative px-5 py-5 flex items-start justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight
                               text-neutral-900 dark:text-white">
                        {{ __('Welcome back') }}, {{ $user->name }}
                    </h1>
                    <p class="mt-1 text-sm flex items-center gap-1.5
                               text-neutral-500 dark:text-neutral-400">
                        <svg class="h-3.5 w-3.5 shrink-0" fill="none"
                             stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0
                                     l-4.244-4.243a8 8 0 1111.314 0z"/>
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
                   class="shrink-0 inline-flex items-center gap-1 text-sm font-semibold
                          text-primary-600 dark:text-primary-400
                          hover:text-primary-700 dark:hover:text-primary-300 transition">
                    {{ __('Browse products') }}
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>
        </div>

        {{-- Section nav --}}
        <div class="flex p-1 gap-1 rounded-2xl shadow-card
                    border border-neutral-200/80 dark:border-white/[0.06]
                    bg-neutral-100/80 dark:bg-[#0e1117]/80 backdrop-blur-sm">
            @foreach ([
                ['key' => 'estimates', 'label' => __('My Estimates')],
                ['key' => 'baskets',   'label' => __('My Baskets')],
            ] as $tab)
                <button wire:click="setSection('{{ $tab['key'] }}')"
                        class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl
                               text-sm font-medium transition-all
                               {{ $activeSection === $tab['key']
                                   ? 'bg-white dark:bg-[#12151f] shadow-sm text-neutral-900 dark:text-white'
                                   : 'text-neutral-500 dark:text-neutral-400 hover:text-neutral-700 dark:hover:text-neutral-200' }}">
                    @if ($tab['key'] === 'estimates')
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                    @else
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                        </svg>
                    @endif
                    {{ $tab['label'] }}
                </button>
            @endforeach
        </div>

        {{-- ─── ESTIMATES SECTION ─── --}}
        @if ($activeSection === 'estimates')

            {{-- Activity calendar --}}
            @php
                $today     = \Illuminate\Support\Carbon::today();
                $gridStart = $today->copy()->subWeeks(52)->startOfWeek(\Illuminate\Support\Carbon::MONDAY);
                $calWeeks  = [];
                $calMonths = [];
                $cur       = $gridStart->copy();
                $lastMonth = null;
                $weekIdx   = 0;

                while ($cur->lte($today)) {
                    $week = [];
                    for ($d = 0; $d < 7; $d++) {
                        $day    = $cur->copy()->addDays($d);
                        $week[] = [
                            'date'   => $day->toDateString(),
                            'count'  => $activityMap[$day->toDateString()] ?? 0,
                            'future' => $day->gt($today),
                        ];
                    }
                    if ($cur->month !== $lastMonth) {
                        $calMonths[] = ['label' => $cur->format('M'), 'col' => $weekIdx];
                        $lastMonth   = $cur->month;
                    }
                    $calWeeks[] = $week;
                    $cur->addWeek();
                    $weekIdx++;
                }
                foreach ($calMonths as $i => &$m) {
                    $m['width'] = (($calMonths[$i + 1]['col'] ?? count($calWeeks)) - $m['col']) * 12;
                }
                unset($m);
            @endphp

            <div class="relative rounded-2xl overflow-hidden shadow-card
                        border border-neutral-200/80 dark:border-white/[0.06]">
                <div class="absolute inset-0 backdrop-blur-sm transition-colors duration-300
                            bg-white dark:bg-[#12151f]"></div>
                <div class="relative">
                    <div class="px-5 py-4 border-b border-black/[0.06] dark:border-white/[0.06]
                                flex items-center justify-between">
                        <div>
                            <h2 class="text-base font-semibold tracking-tight
                                       text-neutral-900 dark:text-white">
                                {{ __('Submission activity') }}
                            </h2>
                            <p class="text-xs mt-0.5 text-neutral-500 dark:text-neutral-400">
                                {{ __('Last 52 weeks') }}
                            </p>
                        </div>
                        <span class="text-xs font-semibold tabular-nums
                                     text-neutral-500 dark:text-neutral-400">
                            {{ $totalEstimates }} {{ __('total') }}
                        </span>
                    </div>

                    <div class="px-5 py-5">
                        <div class="overflow-x-auto">
                            <div style="min-width: max-content">

                                {{-- Month labels --}}
                                <div class="flex mb-1.5" style="padding-left: 28px">
                                    @foreach ($calMonths as $month)
                                        <div class="text-[10px] font-medium overflow-hidden whitespace-nowrap
                                                    text-neutral-400 dark:text-neutral-500"
                                             style="width: {{ $month['width'] }}px">
                                            {{ $month['label'] }}
                                        </div>
                                    @endforeach
                                </div>

                                {{-- Grid --}}
                                <div class="flex gap-0.5">
                                    {{-- Day-of-week labels --}}
                                    <div class="flex flex-col gap-0.5 mr-1.5 shrink-0">
                                        @foreach (['Mon', '', 'Wed', '', 'Fri', '', ''] as $dayLabel)
                                            <div class="h-[10px] text-[9px] leading-none flex items-center
                                                        text-neutral-400 dark:text-neutral-500 whitespace-nowrap">
                                                {{ $dayLabel }}
                                            </div>
                                        @endforeach
                                    </div>

                                    {{-- Week columns --}}
                                    @foreach ($calWeeks as $week)
                                        <div class="flex flex-col gap-0.5 shrink-0">
                                            @foreach ($week as $cell)
                                                @php
                                                    $cellClass = match(true) {
                                                        $cell['future']      => 'opacity-0 pointer-events-none',
                                                        $cell['count'] === 0 => 'bg-neutral-100 dark:bg-white/[0.06]',
                                                        $cell['count'] === 1 => 'bg-primary-200 dark:bg-primary-900',
                                                        $cell['count'] === 2 => 'bg-primary-400 dark:bg-primary-700',
                                                        $cell['count'] >= 3  => 'bg-primary-600 dark:bg-primary-500',
                                                    };
                                                @endphp
                                                <div class="w-[10px] h-[10px] rounded-sm {{ $cellClass }} transition-colors"
                                                     title="{{ $cell['future'] ? '' : ($cell['date'] . ': ' . $cell['count'] . ' submission' . ($cell['count'] !== 1 ? 's' : '')) }}">
                                                </div>
                                            @endforeach
                                        </div>
                                    @endforeach
                                </div>

                                {{-- Legend --}}
                                <div class="flex items-center gap-1.5 mt-3 justify-end">
                                    <span class="text-[10px] text-neutral-400 dark:text-neutral-500">{{ __('Less') }}</span>
                                    @foreach ([
                                        'bg-neutral-100 dark:bg-white/[0.06]',
                                        'bg-primary-200 dark:bg-primary-900',
                                        'bg-primary-400 dark:bg-primary-700',
                                        'bg-primary-600 dark:bg-primary-500',
                                    ] as $legendClass)
                                        <div class="w-[10px] h-[10px] rounded-sm {{ $legendClass }}"></div>
                                    @endforeach
                                    <span class="text-[10px] text-neutral-400 dark:text-neutral-500">{{ __('More') }}</span>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Stats strip --}}
            @php
                $onCooldown = collect($recentEstimates)->filter(fn($r) => $r['cooldown_ends'] > now())->count();
            @endphp
            <div class="grid grid-cols-3 gap-4">
                @foreach ([
                    ['label' => __('Total estimates'), 'value' => $totalEstimates,           'sub' => null,           'color' => '#10b981'],
                    ['label' => __('This week'),        'value' => $recentEstimates->count(), 'sub' => null,           'color' => '#06b6d4'],
                    ['label' => __('On cooldown'),      'value' => $onCooldown,               'sub' => __('products'), 'color' => '#f59e0b'],
                ] as $stat)
                    <div class="relative rounded-2xl overflow-hidden shadow-card
                                border border-neutral-200/80 dark:border-white/[0.06]"
                         style="border-top: 3px solid {{ $stat['color'] }}">
                        <div class="absolute inset-0 backdrop-blur-sm transition-colors duration-300
                                    bg-white dark:bg-[#12151f]"></div>
                        <div class="relative px-5 py-4 text-center">
                            <p class="text-3xl font-bold tabular-nums tracking-tight
                                       text-neutral-900 dark:text-white">
                                {{ $stat['value'] }}
                            </p>
                            <p class="text-xs mt-1 tracking-widest uppercase
                                       text-neutral-500 dark:text-neutral-500">
                                {{ $stat['label'] }}
                            </p>
                            @if ($stat['sub'])
                                <p class="text-xs text-neutral-400 dark:text-neutral-600 mt-0.5">
                                    {{ $stat['sub'] }}
                                </p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Recent submissions --}}
            <div class="relative rounded-2xl overflow-hidden shadow-card
                        border border-neutral-200/80 dark:border-white/[0.06]">
                <div class="absolute inset-0 backdrop-blur-sm transition-colors duration-300
                            bg-white dark:bg-[#12151f]"></div>
                <div class="relative">

                    <div class="px-5 py-4 border-b transition-colors duration-300
                                border-black/[0.06] dark:border-white/[0.06]">
                        <h2 class="text-base font-semibold tracking-tight
                                   text-neutral-900 dark:text-white">
                            {{ __('Recent submissions') }}
                        </h2>
                        <p class="text-xs mt-0.5 text-neutral-500 dark:text-neutral-400">
                            {{ __('Last :days days · editing unavailable during cooldown', ['days' => \App\Models\PriceEstimate::ESTIMATE_COOLDOWN_DAYS]) }}
                        </p>
                    </div>

                    @if ($recentEstimates->isEmpty())
                        <div class="py-14 flex flex-col items-center text-center">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center mb-3
                                        bg-neutral-100 dark:bg-white/[0.05]">
                                <svg class="h-5 w-5 text-neutral-300 dark:text-neutral-600"
                                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                          d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0
                                             00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2
                                             2 0 012 2"/>
                                </svg>
                            </div>
                            <p class="text-sm font-medium text-neutral-500 dark:text-neutral-400">
                                {{ __('No submissions this week') }}
                            </p>
                            <a href="{{ route('catalog') }}"
                               class="mt-2 text-sm hover:underline transition
                                      text-primary-600 dark:text-primary-400">
                                {{ __('Browse products →') }}
                            </a>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-black/[0.06] dark:border-white/[0.06]">
                                        <th class="text-left px-5 py-3 text-xs font-semibold uppercase
                                                   tracking-widest text-neutral-400 dark:text-neutral-500">
                                            {{ __('Product') }}
                                        </th>
                                        <th class="text-right px-5 py-3 text-xs font-semibold uppercase
                                                   tracking-widest text-neutral-400 dark:text-neutral-500">
                                            {{ __('Your price') }}
                                        </th>
                                        <th class="text-right px-5 py-3 text-xs font-semibold uppercase
                                                   tracking-widest text-neutral-400 dark:text-neutral-500
                                                   hidden sm:table-cell">
                                            {{ __('City avg') }}
                                        </th>
                                        <th class="px-5 py-3 text-xs font-semibold uppercase
                                                   tracking-widest text-neutral-400 dark:text-neutral-500
                                                   hidden md:table-cell">
                                            {{ __('Status') }}
                                        </th>
                                        <th class="text-right px-5 py-3 text-xs font-semibold uppercase
                                                   tracking-widest text-neutral-400 dark:text-neutral-500
                                                   hidden sm:table-cell">
                                            {{ __('Cooldown') }}
                                        </th>
                                        <th class="w-14"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-black/[0.04] dark:divide-white/[0.04]">
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
                                        <tr class="group transition-colors
                                                   hover:bg-black/[0.02] dark:hover:bg-white/[0.03]">

                                            <td class="px-5 py-3">
                                                <a href="{{ route('products.show', $row['estimate']->product) }}"
                                                   class="font-medium transition
                                                          text-neutral-800 dark:text-neutral-100
                                                          hover:text-primary-600 dark:hover:text-primary-400">
                                                    {{ $row['estimate']->product->name }}
                                                </a>
                                                <p class="mt-1 flex items-center gap-1.5 flex-wrap">
                                                    <span class="inline-flex items-center text-[10px] font-semibold
                                                                 px-1.5 py-0.5 rounded-full text-white leading-none"
                                                          style="background-color: {{ $row['estimate']->product->category->color }}">
                                                        {{ $row['estimate']->product->category->name }}
                                                    </span>
                                                    @if ($row['estimate']->product->unit)
                                                        <span class="text-xs text-neutral-400 dark:text-neutral-500">
                                                            {{ $row['estimate']->product->unit->symbol }}
                                                        </span>
                                                    @endif
                                                </p>
                                            </td>

                                            <td class="px-5 py-3 text-right font-semibold tabular-nums
                                                       text-primary-600 dark:text-primary-400">
                                                {{ $row['symbol'] }}{{ number_format($row['converted_price'] ?? $row['estimate']->price, 2) }}
                                            </td>

                                            <td class="px-5 py-3 text-right tabular-nums hidden sm:table-cell
                                                       text-neutral-500 dark:text-neutral-400">
                                                @if ($row['city_average'] !== null)
                                                    {{ $row['symbol'] }}{{ number_format($row['city_average'], 2) }}
                                                @else
                                                    <span class="text-neutral-300 dark:text-neutral-700">—</span>
                                                @endif
                                            </td>

                                            <td class="px-5 py-3 hidden md:table-cell">
                                                <div class="flex items-center gap-1.5 flex-wrap">
                                                    @if ($row['position'] !== null)
                                                        <span class="inline-flex items-center px-2 py-0.5
                                                                     rounded-full text-xs font-medium
                                                                     {{ $positionClasses }}">
                                                            {{ $positionLabel }}
                                                            @if ($row['deviation'] !== null)
                                                                ({{ number_format(abs($row['deviation']), 1) }}%)
                                                            @endif
                                                        </span>
                                                    @endif
                                                    @if ($row['is_outlier'])
                                                        <span class="inline-flex items-center px-2 py-0.5
                                                                     rounded-full text-xs font-medium
                                                                     bg-warning-50 dark:bg-warning-900/20
                                                                     text-warning-700 dark:text-warning-400">
                                                            ⚠ {{ __('Flagged') }}
                                                        </span>
                                                    @endif
                                                </div>
                                            </td>

                                            <td class="px-5 py-3 text-right hidden sm:table-cell">
                                                @if ($daysLeft > 0)
                                                    <span class="text-sm text-neutral-500 dark:text-neutral-400">
                                                        {{ $daysLeft === 1
                                                            ? __('1 day left')
                                                            : __(':d days left', ['d' => $daysLeft]) }}
                                                    </span>
                                                @else
                                                    <span class="text-sm text-success-600 dark:text-success-400">
                                                        {{ __('Ready') }}
                                                    </span>
                                                @endif
                                            </td>

                                            <td class="px-5 py-3 text-right">
                                                <button
                                                    wire:click="deleteEstimate({{ $row['estimate']->id }})"
                                                    wire:confirm="{{ __('Delete this estimate?') }}"
                                                    class="text-sm transition
                                                           text-neutral-400 dark:text-neutral-500
                                                           hover:text-error-500 dark:hover:text-error-400
                                                           opacity-0 group-hover:opacity-100
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
            </div>

            {{-- City comparison --}}
            <div class="relative rounded-2xl overflow-hidden shadow-card
                        border border-neutral-200/80 dark:border-white/[0.06]">
                <div class="absolute inset-0 backdrop-blur-sm transition-colors duration-300
                            bg-white dark:bg-[#12151f]"></div>
                <div class="relative">

                    <div class="px-5 py-4 border-b border-black/[0.06] dark:border-white/[0.06]
                                flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <h2 class="text-base font-semibold tracking-tight
                                       text-neutral-900 dark:text-white">
                                {{ __('How does your city compare?') }}
                            </h2>
                            <p class="text-xs mt-0.5 text-neutral-500 dark:text-neutral-400">
                                {{ __('30-day averages') }}
                            </p>
                        </div>

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
                                    $wire.selectComparisonProduct(product.id);
                                }
                            }"
                            @click.outside="open = false; activeIndex = -1"
                            class="relative w-56"
                        >
                            <div class="relative">
                                <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 h-3.5 w-3.5
                                            text-neutral-400 pointer-events-none"
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
                                    class="w-full pl-8 text-sm rounded-lg transition
                                           border-neutral-300 dark:border-white/[0.1]
                                           bg-neutral-50 dark:bg-white/[0.05]
                                           text-neutral-800 dark:text-neutral-100
                                           placeholder-neutral-400 dark:placeholder-neutral-500
                                           focus:ring-2 focus:ring-primary-500/30
                                           focus:border-primary-500
                                           focus:bg-white dark:focus:bg-white/[0.08]"
                                />
                            </div>

                            <div x-show="open" x-cloak
                                 x-transition:enter="transition ease-out duration-100"
                                 x-transition:enter-start="opacity-0 scale-95"
                                 x-transition:enter-end="opacity-100 scale-100"
                                 class="absolute right-0 z-20 mt-1 w-64 rounded-xl shadow-card-md
                                        max-h-64 overflow-y-auto
                                        bg-white dark:bg-neutral-900
                                        border border-neutral-200 dark:border-white/[0.08]">
                                <template x-for="category in filteredCategories" :key="category.id">
                                    <div>
                                        <div class="px-3 pt-2.5 pb-1 text-[10px] font-bold
                                                    uppercase tracking-widest"
                                             :style="'color: ' + (category.color ?? '#9ca3af')">
                                            <span x-text="getName(category.name)"></span>
                                        </div>
                                        <template x-for="product in category.products" :key="product.id">
                                            <div @click="selectProduct(product)"
                                                 :class="activeIndex >= 0 && flatProducts[activeIndex]?.id === product.id
                                                     ? 'bg-primary-50 dark:bg-primary-900/30'
                                                     : 'hover:bg-neutral-100 dark:hover:bg-white/[0.06]'"
                                                 class="px-3 py-2 text-sm cursor-pointer transition
                                                        flex items-center justify-between">
                                                <span x-text="getName(product.name)"
                                                      class="text-neutral-800 dark:text-neutral-200">
                                                </span>
                                                <template x-if="product.unit">
                                                    <span class="text-xs ml-2 shrink-0
                                                                 text-neutral-400 dark:text-neutral-500"
                                                          x-text="product.unit.symbol"></span>
                                                </template>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                                <div x-show="filteredCategories.length === 0"
                                     class="px-3 py-4 text-sm text-center
                                            text-neutral-400 dark:text-neutral-500">
                                    {{ __('No products found') }}
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Skeleton shown while a new product is loading --}}
                    <div wire:loading wire:target="selectComparisonProduct"
                         class="px-5 py-6 space-y-5">
                        @foreach ([100, 65, 80] as $w)
                            <div>
                                <div class="flex justify-between mb-2">
                                    <div class="h-3 rounded-full animate-pulse
                                                bg-neutral-200 dark:bg-white/[0.08]"
                                         style="width: {{ $w * 0.7 }}%"></div>
                                    <div class="h-3 w-14 rounded-full animate-pulse
                                                bg-neutral-200 dark:bg-white/[0.08]"></div>
                                </div>
                                <div class="h-1.5 rounded-full animate-pulse
                                            bg-neutral-200 dark:bg-white/[0.08]"
                                     style="width: {{ $w }}%"></div>
                            </div>
                        @endforeach
                    </div>

                    <div wire:loading.remove wire:target="selectComparisonProduct">
                    @if ($comparison === null)
                        <div class="py-14 flex flex-col items-center text-center">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center mb-3
                                        bg-neutral-100 dark:bg-white/[0.05]">
                                <svg class="h-5 w-5 text-neutral-300 dark:text-neutral-600"
                                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                          d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0
                                             002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2
                                             2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2
                                             2 0 01-2-2z"/>
                                </svg>
                            </div>
                            <p class="text-sm font-medium text-neutral-500 dark:text-neutral-400">
                                {{ __('No price data available for this product') }}
                            </p>
                            @if (!$user->city)
                                <p class="text-xs mt-1 text-neutral-400 dark:text-neutral-500">
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
                                <div>
                                    <div class="flex items-baseline justify-between mb-1.5 gap-2">
                                        <span class="text-sm truncate
                                                     {{ $bar['primary']
                                                         ? 'font-semibold text-neutral-900 dark:text-white'
                                                         : 'text-neutral-500 dark:text-neutral-400' }}">
                                            {{ $bar['label'] }}
                                        </span>
                                        @if ($bar['value'] !== null)
                                            <span class="text-sm tabular-nums shrink-0
                                                         {{ $bar['primary']
                                                             ? 'font-semibold text-neutral-900 dark:text-white'
                                                             : 'text-neutral-500 dark:text-neutral-400' }}">
                                                {{ $comparison['symbol'] }}{{ number_format($bar['value'], 2) }}
                                                @if ($bar['primary'] && $comparison['vs_country'] !== null)
                                                    <span class="text-xs font-normal ml-1
                                                                 {{ $comparison['vs_country'] > 0
                                                                     ? 'text-error-500'
                                                                     : 'text-success-500' }}">
                                                        {{ $comparison['vs_country'] > 0 ? '+' : '' }}{{ number_format($comparison['vs_country'], 1) }}%
                                                    </span>
                                                @endif
                                            </span>
                                        @else
                                            <span class="text-xs italic shrink-0
                                                         text-neutral-400 dark:text-neutral-500">
                                                {{ $bar['primary'] ? __('No local data yet') : '—' }}
                                            </span>
                                        @endif
                                    </div>
                                    <div class="w-full rounded-full h-1.5 overflow-hidden
                                                bg-neutral-200 dark:bg-white/[0.07]">
                                        <div class="h-1.5 rounded-full transition-all duration-500
                                                    {{ $bar['primary']
                                                        ? 'bg-primary-500'
                                                        : 'bg-neutral-300 dark:bg-neutral-700' }}"
                                             style="width: {{ ($bar['value'] !== null && $maxValue > 0) ? round(($bar['value'] / $maxValue) * 100) : 0 }}%">
                                        </div>
                                    </div>
                                    @if ($bar['primary'] && $bar['value'] === null)
                                        <p class="text-xs mt-1.5">
                                            <a href="{{ route('products.show', $comparison['product']) }}"
                                               class="text-primary-600 dark:text-primary-400 hover:underline">
                                                {{ __('Be the first to submit a price →') }}
                                            </a>
                                        </p>
                                    @endif
                                </div>
                            @endforeach

                            @if ($comparison['vs_country'] !== null)
                                <p class="text-sm pt-3 border-t border-black/[0.06] dark:border-white/[0.06]
                                           text-neutral-500 dark:text-neutral-400">
                                    {{ $comparison['city']->name }} {{ __('is') }}
                                    <span class="font-semibold
                                                 {{ $comparison['vs_country'] > 0
                                                     ? 'text-error-500'
                                                     : 'text-success-500' }}">
                                        {{ abs(round($comparison['vs_country'], 1)) }}%
                                        {{ $comparison['vs_country'] > 0 ? __('above') : __('below') }}
                                    </span>
                                    {{ __('the country average for') }} {{ $comparison['product']->name }}.
                                </p>
                            @endif
                            @if ($comparison['vs_global'] !== null)
                                <p class="text-sm pt-3 border-t border-black/[0.06] dark:border-white/[0.06]
                                           text-neutral-500 dark:text-neutral-400">
                                    {{ $comparison['city']->name }} {{ __('is') }}
                                    <span class="font-semibold
                                                 {{ $comparison['vs_global'] > 0
                                                     ? 'text-error-500'
                                                     : 'text-success-500' }}">
                                        {{ abs(round($comparison['vs_global'], 1)) }}%
                                        {{ $comparison['vs_global'] > 0 ? __('above') : __('below') }}
                                    </span>
                                    {{ __('the global average for') }} {{ $comparison['product']->name }}.
                                </p>
                            @endif
                        </div>
                    @endif
                    </div>{{-- end wire:loading.remove --}}

                </div>
            </div>

        {{-- ─── BASKETS SECTION ─── --}}
        @elseif ($activeSection === 'baskets')

            @php $basketColors = ['#10b981','#06b6d4','#3b82f6','#8b5cf6','#ec4899','#f97316','#f59e0b','#ef4444']; @endphp

            {{-- Keyboard shortcut handler — scoped to this section via conditional render --}}
            <div x-data="{}"
                 @keydown.window="
                     const t = $event.target.tagName;
                     if (['INPUT','TEXTAREA','SELECT'].includes(t) || $event.ctrlKey || $event.metaKey) return;
                     if ($event.key === 'n') { $event.preventDefault(); $wire.call('openCreateBasket'); }
                     if ($event.key === '/') { $event.preventDefault(); window.dispatchEvent(new CustomEvent('basket-focus-search')); }
                 "
                 class="hidden">
            </div>

            {{-- Section header --}}
            <div class="relative rounded-2xl overflow-hidden shadow-card
                        border border-neutral-200/80 dark:border-white/[0.06]">
                <div class="absolute inset-0 backdrop-blur-sm transition-colors duration-300
                            bg-white dark:bg-[#12151f]"></div>
                <div class="relative px-5 py-4 flex items-center justify-between gap-4">
                    <div>
                        <h2 class="text-base font-semibold tracking-tight text-neutral-900 dark:text-white">
                            {{ __('My Baskets') }}
                        </h2>
                        <p class="text-xs mt-0.5 text-neutral-500 dark:text-neutral-400">
                            {{ __('Track product bundles and compare prices across cities') }}
                        </p>
                    </div>
                    @if (!$showBasketForm)
                        <div class="flex items-center gap-2 shrink-0">
                            <kbd class="hidden sm:inline-flex h-5 items-center px-1.5 rounded text-[10px] font-mono
                                        border border-neutral-200 dark:border-white/[0.1]
                                        text-neutral-400 dark:text-neutral-500
                                        bg-neutral-50 dark:bg-white/[0.03]">N</kbd>
                            <button wire:click="openCreateBasket"
                                    class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-sm
                                           font-semibold transition-all
                                           bg-primary-500 hover:bg-primary-600 text-white shadow-sm">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                          d="M12 4v16m8-8H4"/>
                                </svg>
                                {{ __('New basket') }}
                            </button>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Basket price panel --}}
            @if ($selectedBasketId && $user->city)
                @php
                    $periods = [
                        ['value' => '30',  'label' => __('30 days')],
                        ['value' => '90',  'label' => __('90 days')],
                        ['value' => '365', 'label' => __('1 year')],
                        ['value' => '0',   'label' => __('All time')],
                    ];
                    $selectedBasket = $baskets->firstWhere('id', $selectedBasketId);
                @endphp

                <div class="relative rounded-2xl shadow-card
                            border border-neutral-200/80 dark:border-white/[0.06]"
                     style="border-top: 3px solid {{ $selectedBasket?->color ?? '#10b981' }}">
                    <div class="absolute inset-0 rounded-2xl overflow-hidden backdrop-blur-sm
                                transition-colors duration-300 bg-white dark:bg-[#12151f]"></div>
                    <div class="relative">

                        <div class="px-5 py-4 border-b border-black/[0.06] dark:border-white/[0.06]
                                    flex flex-wrap items-center justify-between gap-3">
                            <div class="flex items-center gap-2 min-w-0">
                                <div class="w-2.5 h-2.5 rounded-full shrink-0"
                                     style="background-color: {{ $selectedBasket?->color ?? '#10b981' }}"></div>
                                <span class="text-sm font-semibold text-neutral-900 dark:text-white truncate">
                                    {{ $selectedBasket?->name }}
                                </span>
                                <span class="text-xs text-neutral-400 dark:text-neutral-500 shrink-0">
                                    · {{ $user->city->name }}
                                </span>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <div class="flex p-0.5 gap-0.5 rounded-lg bg-neutral-100 dark:bg-white/[0.06]">
                                    @foreach ($periods as $p)
                                        <button wire:click="setPricePeriod('{{ $p['value'] }}')"
                                                class="px-2.5 py-1 rounded-md text-xs font-medium transition-all
                                                       {{ $pricePeriod === $p['value']
                                                           ? 'bg-white dark:bg-white/[0.12] shadow-sm text-neutral-700 dark:text-white'
                                                           : 'text-neutral-400 dark:text-neutral-500 hover:text-neutral-600 dark:hover:text-neutral-300' }}">
                                            {{ $p['label'] }}
                                        </button>
                                    @endforeach
                                </div>
                                <button wire:click="selectBasketForPricing({{ $selectedBasketId }})"
                                        class="p-1.5 rounded-lg transition
                                               text-neutral-400 dark:text-neutral-500
                                               hover:text-neutral-700 dark:hover:text-neutral-200
                                               hover:bg-neutral-100 dark:hover:bg-white/[0.06]"
                                        title="{{ __('Close') }}">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div wire:loading wire:target="setPricePeriod,selectBasketForPricing"
                             class="px-5 py-6 space-y-3">
                            <div class="h-8 w-32 rounded-lg animate-pulse bg-neutral-200 dark:bg-white/[0.08]"></div>
                            @foreach ([100, 75, 90, 60] as $w)
                                <div class="flex justify-between items-center">
                                    <div class="h-3 rounded-full animate-pulse bg-neutral-200 dark:bg-white/[0.08]"
                                         style="width: {{ $w * 0.5 }}%"></div>
                                    <div class="h-3 w-16 rounded-full animate-pulse bg-neutral-200 dark:bg-white/[0.08]"></div>
                                </div>
                            @endforeach
                        </div>

                        <div wire:loading.remove wire:target="setPricePeriod,selectBasketForPricing">
                            @if ($basketPrice === null)
                                <div class="px-5 py-10 text-center">
                                    <p class="text-sm text-neutral-500 dark:text-neutral-400">
                                        {{ __('No items in this basket yet.') }}
                                    </p>
                                </div>
                            @else
                                <div class="px-5 py-5 border-b border-black/[0.06] dark:border-white/[0.06]
                                            flex items-baseline justify-between gap-4">
                                    <div>
                                        @if ($basketPrice['complete'])
                                            <p class="text-2xl font-bold tabular-nums tracking-tight
                                                       text-neutral-900 dark:text-white">
                                                {{ $basketPrice['symbol'] }}{{ number_format($basketPrice['total'], 2) }}
                                            </p>
                                            <p class="text-xs mt-0.5 text-neutral-500 dark:text-neutral-400">
                                                {{ __('Total basket price in :city', ['city' => $basketPrice['city']->name]) }}
                                            </p>
                                        @else
                                            <p class="text-2xl font-bold tabular-nums tracking-tight
                                                       text-neutral-900 dark:text-white">
                                                {{ $basketPrice['symbol'] }}{{ number_format($basketPrice['total'], 2) }}
                                                <span class="text-sm font-normal text-neutral-400 dark:text-neutral-500 ml-1">
                                                    {{ __('partial') }}
                                                </span>
                                            </p>
                                            <p class="text-xs mt-0.5 text-warning-600 dark:text-warning-400">
                                                {{ __('Incomplete recording — missing data for :n product(s)', ['n' => count($basketPrice['missing'])]) }}
                                            </p>
                                        @endif
                                    </div>
                                </div>

                                @if (!empty($basketPrice['breakdown']))
                                    <ul class="divide-y divide-black/[0.04] dark:divide-white/[0.04]">
                                        @foreach ($basketPrice['breakdown'] as $row)
                                            <li class="px-5 py-3 flex items-center gap-3 text-sm">
                                                <span class="w-2 h-2 rounded-full shrink-0"
                                                      style="background-color: {{ $row['category_color'] }}"></span>
                                                <span class="flex-1 text-neutral-800 dark:text-neutral-100 truncate">
                                                    {{ $row['name'] }}
                                                </span>
                                                <span class="text-xs text-neutral-400 dark:text-neutral-500 shrink-0 tabular-nums">
                                                    ×&thinsp;{{ rtrim(rtrim(number_format($row['quantity'], 2), '0'), '.') }}
                                                    @if ($row['unit']){{ $row['unit'] }}@endif
                                                    &nbsp;·&nbsp;
                                                    {{ $basketPrice['symbol'] }}{{ number_format($row['avg'], 2) }}/{{ $row['unit'] ?: __('unit') }}
                                                </span>
                                                <span class="text-sm font-semibold tabular-nums shrink-0
                                                             text-neutral-700 dark:text-neutral-200">
                                                    {{ $basketPrice['symbol'] }}{{ number_format($row['subtotal'], 2) }}
                                                </span>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif

                                @if (!empty($basketPrice['missing']))
                                    <div class="px-5 py-4 border-t border-black/[0.06] dark:border-white/[0.06]
                                                bg-warning-50/60 dark:bg-warning-900/10 rounded-b-2xl">
                                        <p class="text-xs font-semibold text-warning-700 dark:text-warning-400 mb-2 flex items-center gap-1.5">
                                            <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                            </svg>
                                            {{ __('No price data for this period:') }}
                                        </p>
                                        <ul class="space-y-1">
                                            @foreach ($basketPrice['missing'] as $item)
                                                <li class="flex items-center gap-2 text-xs text-warning-600 dark:text-warning-400">
                                                    <span class="w-1.5 h-1.5 rounded-full shrink-0"
                                                          style="background-color: {{ $item->product->category?->color ?? '#9ca3af' }}"></span>
                                                    {{ $item->product->name }}
                                                    @if ($item->product->unit)
                                                        <span class="text-warning-400 dark:text-warning-600">
                                                            (×&thinsp;{{ rtrim(rtrim(number_format((float)$item->quantity, 2), '0'), '.') }}
                                                            {{ $item->product->unit->symbol }})
                                                        </span>
                                                    @endif
                                                    <a href="{{ route('products.show', $item->product) }}"
                                                       class="ml-auto text-warning-500 hover:underline shrink-0">
                                                        {{ __('Submit →') }}
                                                    </a>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                            @endif
                        </div>

                    </div>
                </div>
            @endif

            {{-- Create form (new baskets only — edits are handled inline) --}}
            @if ($showBasketForm && !$editingBasketId)
                <div class="relative rounded-2xl overflow-hidden shadow-card
                            border border-neutral-200/80 dark:border-white/[0.06]"
                     style="border-top: 3px solid {{ $basketFormColor }}"
                     x-data="{}">
                    <div class="absolute inset-0 backdrop-blur-sm transition-colors duration-300
                                bg-white dark:bg-[#12151f]"></div>
                    <div class="relative px-5 py-5">
                        <h3 class="text-sm font-semibold text-neutral-900 dark:text-white mb-4">
                            {{ __('New basket') }}
                        </h3>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-xs font-medium text-neutral-500 dark:text-neutral-400 mb-1.5">
                                    {{ __('Name') }}
                                </label>
                                <input wire:model="basketFormName"
                                       type="text"
                                       maxlength="80"
                                       placeholder="{{ __('e.g. Weekly groceries') }}"
                                       x-init="$el.focus()"
                                       @keydown.enter.prevent="$wire.call('saveBasket')"
                                       @keydown.escape.prevent="$wire.call('cancelBasketForm')"
                                       class="w-full text-sm rounded-lg transition
                                              border-neutral-300 dark:border-white/[0.1]
                                              bg-neutral-50 dark:bg-white/[0.05]
                                              text-neutral-800 dark:text-neutral-100
                                              placeholder-neutral-400 dark:placeholder-neutral-500
                                              focus:ring-2 focus:ring-primary-500/30 focus:border-primary-500
                                              focus:bg-white dark:focus:bg-white/[0.08]"/>
                                @error('basketFormName')
                                    <p class="mt-1 text-xs text-error-500">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-neutral-500 dark:text-neutral-400 mb-2">
                                    {{ __('Color') }}
                                </label>
                                <div class="flex flex-wrap gap-2">
                                    @foreach ($basketColors as $color)
                                        <button type="button"
                                                wire:click="$set('basketFormColor', '{{ $color }}')"
                                                class="w-7 h-7 rounded-full transition-all focus:outline-none
                                                       {{ $basketFormColor === $color
                                                           ? 'ring-2 ring-offset-2 ring-offset-white dark:ring-offset-[#12151f] scale-110'
                                                           : 'opacity-70 hover:opacity-100 hover:scale-105' }}"
                                                style="background-color: {{ $color }}">
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 mt-5">
                            <button wire:click="saveBasket"
                                    class="px-4 py-2 rounded-xl text-sm font-semibold transition-all
                                           bg-primary-500 hover:bg-primary-600 text-white shadow-sm">
                                {{ __('Create basket') }}
                            </button>
                            <button wire:click="cancelBasketForm"
                                    class="px-4 py-2 rounded-xl text-sm font-medium transition-all
                                           text-neutral-500 dark:text-neutral-400
                                           hover:text-neutral-700 dark:hover:text-neutral-200
                                           hover:bg-neutral-100 dark:hover:bg-white/[0.06]">
                                {{ __('Cancel') }}
                            </button>
                            <kbd class="ml-auto hidden sm:inline-flex items-center px-1.5 py-0.5 rounded
                                        text-[10px] font-mono border border-neutral-200 dark:border-white/[0.1]
                                        text-neutral-400 dark:text-neutral-500 bg-neutral-50 dark:bg-white/[0.03]">
                                Esc
                            </kbd>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Basket list --}}
            @if ($baskets->isEmpty())
                @if (!$showBasketForm)
                    <div class="relative rounded-2xl overflow-hidden shadow-card
                                border border-neutral-200/80 dark:border-white/[0.06]">
                        <div class="absolute inset-0 backdrop-blur-sm transition-colors duration-300
                                    bg-white dark:bg-[#12151f]"></div>
                        <div class="relative py-16 flex flex-col items-center text-center">
                            <div class="w-12 h-12 rounded-2xl flex items-center justify-center mb-4
                                        bg-neutral-100 dark:bg-white/[0.05]">
                                <svg class="h-6 w-6 text-neutral-300 dark:text-neutral-600"
                                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                          d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                </svg>
                            </div>
                            <p class="text-sm font-medium text-neutral-500 dark:text-neutral-400">
                                {{ __('No baskets yet') }}
                            </p>
                            <p class="text-xs mt-1 text-neutral-400 dark:text-neutral-500 max-w-xs">
                                {{ __('Create a basket to group products and compare their combined price across cities.') }}
                            </p>
                            <button wire:click="openCreateBasket"
                                    class="mt-4 inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-sm
                                           font-semibold transition-all
                                           bg-primary-500 hover:bg-primary-600 text-white shadow-sm">
                                {{ __('Create your first basket') }}
                            </button>
                        </div>
                    </div>
                @endif
            @else
                @foreach ($baskets as $basket)
                    @php
                        $isOpen    = $openBasketId === $basket->id;
                        $itemCount = $basket->items->count();
                        $catGroups = $basket->items
                            ->groupBy(fn($i) => $i->product->category_id ?? 0)
                            ->values()
                            ->map(fn($g) => [
                                'name'  => $g->first()->product->category?->name ?? '?',
                                'color' => $g->first()->product->category?->color ?? '#9ca3af',
                                'count' => $g->count(),
                            ])
                            ->sortByDesc('count');
                    @endphp

                    <div wire:key="basket-{{ $basket->id }}"
                         class="relative rounded-2xl shadow-card
                                border border-neutral-200/80 dark:border-white/[0.06]"
                         style="border-top: 3px solid {{ $basket->color }}"
                         x-data="{
                             editing: false,
                             editName: {{ Js::from($basket->name) }},
                             editColor: {{ Js::from($basket->color) }},
                             savedName: {{ Js::from($basket->name) }},
                             savedColor: {{ Js::from($basket->color) }},
                             colors: {{ Js::from($basketColors) }},
                             init() {
                                 this.$watch('editing', v => {
                                     if (v) this.$nextTick(() => this.$refs.editNameInput?.focus());
                                 });
                             },
                             async save() {
                                 const name = this.editName.trim();
                                 if (!name) return;
                                 await $wire.call('updateBasket', {{ $basket->id }}, name, this.editColor);
                                 this.savedName = name;
                                 this.savedColor = this.editColor;
                                 this.editing = false;
                             },
                             cancel() {
                                 this.editName = this.savedName;
                                 this.editColor = this.savedColor;
                                 this.editing = false;
                             }
                         }"
                         @keydown.escape.window="if (editing) { $event.preventDefault(); cancel(); }">

                        <div class="absolute inset-0 rounded-2xl overflow-hidden backdrop-blur-sm
                                    transition-colors duration-300 bg-white dark:bg-[#12151f]"></div>
                        <div class="relative">

                            {{-- Basket header --}}
                            <div class="px-5 py-3.5">

                                {{-- View mode --}}
                                <div x-show="!editing" class="flex items-center gap-3">
                                    <div class="w-3 h-3 rounded-full shrink-0"
                                         style="background-color: {{ $basket->color }}"></div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-semibold text-neutral-900 dark:text-white truncate">
                                            {{ $basket->name }}
                                        </p>
                                        <div class="mt-0.5">
                                            @if ($itemCount === 0)
                                                <p class="text-xs text-neutral-400 dark:text-neutral-500">{{ __('Empty') }}</p>
                                            @else
                                                <div class="flex flex-wrap items-center gap-x-2 gap-y-0.5">
                                                    <span class="text-xs text-neutral-400 dark:text-neutral-500">
                                                        {{ $itemCount }} {{ $itemCount === 1 ? __('item') : __('items') }}
                                                    </span>
                                                    @foreach ($catGroups as $cat)
                                                        <span class="inline-flex items-center gap-1
                                                                     text-[11px] text-neutral-400 dark:text-neutral-500">
                                                            <span class="w-1.5 h-1.5 rounded-full shrink-0"
                                                                  style="background-color: {{ $cat['color'] }}"></span>
                                                            {{ $cat['name'] }}@if ($cat['count'] > 1) ×{{ $cat['count'] }}@endif
                                                        </span>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-1 shrink-0">
                                        <button @click="editing = true"
                                                class="p-1.5 rounded-lg transition
                                                       text-neutral-400 dark:text-neutral-500
                                                       hover:text-neutral-700 dark:hover:text-neutral-200
                                                       hover:bg-neutral-100 dark:hover:bg-white/[0.06]"
                                                title="{{ __('Edit') }}">
                                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </button>
                                        <button wire:click="deleteBasket({{ $basket->id }})"
                                                wire:confirm="{{ __('Delete this basket and all its items?') }}"
                                                class="p-1.5 rounded-lg transition
                                                       text-neutral-400 dark:text-neutral-500
                                                       hover:text-error-500 dark:hover:text-error-400
                                                       hover:bg-error-50 dark:hover:bg-error-900/20">
                                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                        @if ($user->city)
                                            <button wire:click="selectBasketForPricing({{ $basket->id }})"
                                                    class="p-1.5 rounded-lg transition
                                                           {{ $selectedBasketId === $basket->id
                                                               ? 'text-primary-600 dark:text-primary-400 bg-primary-50 dark:bg-primary-900/20'
                                                               : 'text-neutral-400 dark:text-neutral-500 hover:text-primary-600 dark:hover:text-primary-400 hover:bg-primary-50 dark:hover:bg-primary-900/20' }}"
                                                    title="{{ __('View price in your city') }}">
                                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                          d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                                </svg>
                                            </button>
                                        @endif
                                        <button wire:click="toggleBasket({{ $basket->id }})"
                                                class="p-1.5 rounded-lg transition
                                                       text-neutral-400 dark:text-neutral-500
                                                       hover:text-neutral-700 dark:hover:text-neutral-200
                                                       hover:bg-neutral-100 dark:hover:bg-white/[0.06]">
                                            <svg class="h-3.5 w-3.5 transition-transform duration-200
                                                        {{ $isOpen ? 'rotate-180' : '' }}"
                                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M19 9l-7 7-7-7"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                {{-- Edit mode (inline — no separate form card) --}}
                                <div x-show="editing" x-cloak class="space-y-3">
                                    {{-- Color swatches --}}
                                    <div class="flex flex-wrap items-center gap-2">
                                        <template x-for="color in colors" :key="color">
                                            <button type="button"
                                                    @click="editColor = color; $el.closest('[style*=border-top]').style.borderTopColor = color"
                                                    :style="'background-color:' + color"
                                                    :class="editColor === color
                                                        ? 'ring-2 ring-offset-2 ring-offset-white dark:ring-offset-[#12151f] scale-110'
                                                        : 'opacity-60 hover:opacity-100 hover:scale-105'"
                                                    class="w-6 h-6 rounded-full transition-all focus:outline-none shrink-0">
                                            </button>
                                        </template>
                                    </div>
                                    {{-- Name + save/cancel --}}
                                    <div class="flex items-center gap-2">
                                        <input type="text"
                                               x-ref="editNameInput"
                                               x-model="editName"
                                               maxlength="80"
                                               @keydown.enter.prevent="save()"
                                               @keydown.escape.prevent="cancel()"
                                               class="flex-1 text-sm rounded-lg transition
                                                      border-neutral-300 dark:border-white/[0.1]
                                                      bg-neutral-50 dark:bg-white/[0.05]
                                                      text-neutral-800 dark:text-neutral-100
                                                      focus:ring-2 focus:ring-primary-500/30 focus:border-primary-500
                                                      focus:bg-white dark:focus:bg-white/[0.08]"/>
                                        <button @click="save()"
                                                :disabled="!editName.trim()"
                                                class="shrink-0 px-3 py-2 rounded-lg text-sm font-semibold transition-all
                                                       bg-primary-500 hover:bg-primary-600 text-white
                                                       disabled:opacity-40 disabled:cursor-not-allowed">
                                            {{ __('Save') }}
                                        </button>
                                        <button @click="cancel()"
                                                class="shrink-0 p-2 rounded-lg transition
                                                       text-neutral-400 dark:text-neutral-500
                                                       hover:text-neutral-700 dark:hover:text-neutral-200
                                                       hover:bg-neutral-100 dark:hover:bg-white/[0.06]">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                            </div>

                            {{-- Expanded content --}}
                            @if ($isOpen)
                                <div class="border-t border-black/[0.06] dark:border-white/[0.06]">

                                    {{-- Items list --}}
                                    @if ($basket->items->isEmpty())
                                        <div class="px-5 py-6 text-center">
                                            <p class="text-sm text-neutral-400 dark:text-neutral-500">
                                                {{ __('No items yet — add some below.') }}
                                            </p>
                                        </div>
                                    @else
                                        <ul class="divide-y divide-black/[0.04] dark:divide-white/[0.04]">
                                            @foreach ($basket->items as $item)
                                                <li class="group px-5 py-3 flex items-center gap-3">
                                                    <span class="w-2 h-2 rounded-full shrink-0"
                                                          style="background-color: {{ $item->product->category?->color ?? '#9ca3af' }}"></span>
                                                    <span class="flex-1 text-sm font-medium
                                                                 text-neutral-800 dark:text-neutral-100 truncate">
                                                        {{ $item->product->name }}
                                                    </span>
                                                    <span class="text-sm tabular-nums shrink-0
                                                                 text-neutral-500 dark:text-neutral-400 flex items-baseline gap-1">
                                                        <span>×&thinsp;{{ rtrim(rtrim(number_format((float)$item->quantity, 2), '0'), '.') }}</span>
                                                        @if ($item->product->unit)
                                                            <span class="text-sm font-bold text-neutral-700 dark:text-neutral-200">
                                                                {{ $item->product->unit->symbol }}
                                                            </span>
                                                        @endif
                                                    </span>
                                                    <button
                                                        wire:click="removeItemFromBasket({{ $basket->id }}, {{ $item->product_id }})"
                                                        class="shrink-0 p-1 rounded-md transition
                                                               text-neutral-300 dark:text-neutral-600
                                                               hover:text-error-500 dark:hover:text-error-400
                                                               opacity-0 group-hover:opacity-100
                                                               focus-visible:opacity-100 focus-visible:outline-none">
                                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                  d="M6 18L18 6M6 6l12 12"/>
                                                        </svg>
                                                    </button>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif

                                    {{-- Add item form --}}
                                    <div class="px-5 pt-4 pb-5 border-t border-black/[0.06] dark:border-white/[0.06]
                                                bg-neutral-50/60 dark:bg-white/[0.02] rounded-b-2xl"
                                         wire:key="add-item-{{ $basket->id }}-{{ $basketItemFormKey }}"
                                         x-data="{
                                             open: false,
                                             search: '',
                                             activeIndex: -1,
                                             cityOnly: {{ $user->city ? 'true' : 'false' }},
                                             qty: 1,
                                             selectedName: '',
                                             selectedUnit: '',
                                             selectedId: null,
                                             cityProductIds: {{ Js::from($cityProductIds) }},
                                             basketProductIds: {{ Js::from($basket->items->pluck('product_id')->all()) }},
                                             categories: {{ Js::from($categories) }},
                                             locale: document.documentElement.lang ?? 'en',
                                             getName(obj) {
                                                 if (!obj) return '';
                                                 if (typeof obj === 'string') return obj;
                                                 return obj[this.locale] ?? obj.en ?? Object.values(obj)[0] ?? '';
                                             },
                                             get filteredCategories() {
                                                 return this.categories.map(c => ({
                                                     ...c,
                                                     products: c.products
                                                         .filter(p => {
                                                             if (this.basketProductIds.includes(p.id)) return false;
                                                             if (this.cityOnly && !this.cityProductIds.includes(p.id)) return false;
                                                             return !this.search || this.getName(p.name).toLowerCase().includes(this.search.toLowerCase());
                                                         })
                                                         .sort((a, b) => this.getName(a.name).localeCompare(this.getName(b.name)))
                                                 })).filter(c => c.products.length > 0);
                                             },
                                             get flatProducts() {
                                                 return this.filteredCategories.flatMap(c => c.products);
                                             },
                                             selectProduct(product) {
                                                 this.selectedName = this.getName(product.name);
                                                 this.selectedUnit = product.unit ? product.unit.symbol : '';
                                                 this.selectedId = product.id;
                                                 this.search = '';
                                                 this.open = false;
                                                 this.activeIndex = -1;
                                             },
                                             clearProduct() {
                                                 this.selectedName = '';
                                                 this.selectedUnit = '';
                                                 this.selectedId = null;
                                                 this.qty = 1;
                                             },
                                             async addToBasket() {
                                                 if (!this.selectedId) return;
                                                 const qty = Math.max(0.01, parseFloat(this.qty) || 1);
                                                 await $wire.call('addItemToBasket', {{ $basket->id }}, this.selectedId, qty);
                                                 this.clearProduct();
                                             }
                                         }"
                                         @basket-focus-search.window="$refs.basketSearchInput?.focus(); open = true"
                                         @click.outside="open = false; activeIndex = -1">

                                        {{-- Header row: label + city filter toggle --}}
                                        <div class="flex items-center justify-between mb-3">
                                            <p class="text-xs font-semibold uppercase tracking-widest
                                                       text-neutral-400 dark:text-neutral-500">
                                                {{ __('Add item') }}
                                            </p>
                                            @if ($user->city)
                                                <div class="flex items-center p-0.5 gap-0.5 rounded-lg
                                                            bg-neutral-100 dark:bg-white/[0.06]">
                                                    <button type="button"
                                                            @click="cityOnly = false; if (search) open = true"
                                                            class="px-2.5 py-1 rounded-md text-xs font-medium transition-all"
                                                            :class="!cityOnly
                                                                ? 'bg-white dark:bg-white/[0.12] shadow-sm text-neutral-700 dark:text-white'
                                                                : 'text-neutral-400 dark:text-neutral-500 hover:text-neutral-600 dark:hover:text-neutral-300'">
                                                        {{ __('All products') }}
                                                    </button>
                                                    <button type="button"
                                                            @click="cityOnly = true; if (search) open = true"
                                                            class="px-2.5 py-1 rounded-md text-xs font-medium transition-all"
                                                            :class="cityOnly
                                                                ? 'bg-white dark:bg-white/[0.12] shadow-sm text-neutral-700 dark:text-white'
                                                                : 'text-neutral-400 dark:text-neutral-500 hover:text-neutral-600 dark:hover:text-neutral-300'"
                                                            title="{{ __('Only products with price data in :city', ['city' => $user->city->name]) }}">
                                                        {{ __('With local data') }}
                                                    </button>
                                                </div>
                                            @endif
                                        </div>

                                        {{-- Product search / selected chip --}}
                                        <div class="relative mb-3">
                                            <div x-show="selectedName"
                                                 class="flex items-center gap-2 w-full pl-3 pr-2 py-2.5 rounded-lg text-sm
                                                        border border-primary-300 dark:border-primary-700/60
                                                        bg-primary-50 dark:bg-primary-900/20">
                                                <span x-text="selectedName"
                                                      class="flex-1 truncate font-medium text-primary-700 dark:text-primary-300">
                                                </span>
                                                <span x-show="selectedUnit" x-text="selectedUnit"
                                                      class="text-xs shrink-0 text-primary-400 dark:text-primary-500
                                                             pl-2 border-l border-primary-200 dark:border-primary-700/40">
                                                </span>
                                                <button type="button" @click="clearProduct()"
                                                        class="shrink-0 ml-1 p-0.5 rounded text-primary-400 dark:text-primary-500
                                                               hover:text-primary-700 dark:hover:text-primary-300 transition">
                                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                              d="M6 18L18 6M6 6l12 12"/>
                                                    </svg>
                                                </button>
                                            </div>

                                            <div x-show="!selectedName" class="relative">
                                                <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-3.5 w-3.5
                                                            text-neutral-400 pointer-events-none"
                                                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                          d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                                </svg>
                                                <input type="text"
                                                       x-ref="basketSearchInput"
                                                       x-model="search"
                                                       @focus="open = true"
                                                       @input="open = true; activeIndex = -1"
                                                       @keydown.down.prevent="open = true; activeIndex = Math.min(activeIndex + 1, flatProducts.length - 1)"
                                                       @keydown.up.prevent="activeIndex = Math.max(0, activeIndex - 1)"
                                                       @keydown.enter.prevent="if (activeIndex >= 0 && flatProducts[activeIndex]) selectProduct(flatProducts[activeIndex])"
                                                       @keydown.escape.stop="open = false; activeIndex = -1"
                                                       placeholder="{{ __('Search products… (/)') }}"
                                                       class="w-full pl-9 py-2.5 text-sm rounded-lg transition
                                                              border-neutral-300 dark:border-white/[0.1]
                                                              bg-neutral-50 dark:bg-white/[0.05]
                                                              text-neutral-800 dark:text-neutral-100
                                                              placeholder-neutral-400 dark:placeholder-neutral-500
                                                              focus:ring-2 focus:ring-primary-500/30
                                                              focus:border-primary-500
                                                              focus:bg-white dark:focus:bg-white/[0.08]"/>
                                            </div>

                                            {{-- Dropdown --}}
                                            <div x-show="open && !selectedName" x-cloak
                                                 x-transition:enter="transition ease-out duration-100"
                                                 x-transition:enter-start="opacity-0 scale-95"
                                                 x-transition:enter-end="opacity-100 scale-100"
                                                 class="absolute left-0 z-50 mt-1 w-full rounded-xl shadow-card-md
                                                        max-h-56 overflow-y-auto
                                                        bg-white dark:bg-neutral-900
                                                        border border-neutral-200 dark:border-white/[0.08]">
                                                <template x-for="category in filteredCategories" :key="category.id">
                                                    <div>
                                                        <div class="px-3 pt-2.5 pb-1 text-[10px] font-bold
                                                                    uppercase tracking-widest"
                                                             :style="'color: ' + (category.color ?? '#9ca3af')">
                                                            <span x-text="getName(category.name)"></span>
                                                        </div>
                                                        <template x-for="product in category.products" :key="product.id">
                                                            <div @click="selectProduct(product)"
                                                                 :class="activeIndex >= 0 && flatProducts[activeIndex]?.id === product.id
                                                                     ? 'bg-primary-50 dark:bg-primary-900/30'
                                                                     : 'hover:bg-neutral-100 dark:hover:bg-white/[0.06]'"
                                                                 class="px-3 py-2 text-sm cursor-pointer transition
                                                                        flex items-center justify-between">
                                                                <span x-text="getName(product.name)"
                                                                      class="text-neutral-800 dark:text-neutral-200"></span>
                                                                <div class="flex items-center gap-2 shrink-0 ml-2">
                                                                    <template x-if="cityProductIds.includes(product.id)">
                                                                        <span class="w-1.5 h-1.5 rounded-full bg-primary-400"
                                                                              title="{{ __('Has price data in your city') }}"></span>
                                                                    </template>
                                                                    <template x-if="product.unit">
                                                                        <span class="text-xs text-neutral-400 dark:text-neutral-500"
                                                                              x-text="product.unit.symbol"></span>
                                                                    </template>
                                                                </div>
                                                            </div>
                                                        </template>
                                                    </div>
                                                </template>
                                                <div x-show="filteredCategories.length === 0"
                                                     class="px-3 py-4 text-sm text-center
                                                            text-neutral-400 dark:text-neutral-500">
                                                    {{ __('No products found') }}
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Quantity slider + input + Add button --}}
                                        <div class="flex items-center gap-3">
                                            <div class="flex-1 flex items-center gap-2 min-w-0">
                                                <input type="range"
                                                       x-model.number="qty"
                                                       min="0.1" max="10" step="0.1"
                                                       class="flex-1 min-w-0 h-1.5 rounded-full appearance-none cursor-pointer
                                                              accent-primary-500"
                                                       :disabled="!selectedId"/>
                                                <div class="flex items-center gap-1.5 shrink-0">
                                                    <input type="number"
                                                           x-model.number="qty"
                                                           min="0.01" step="0.01"
                                                           class="w-16 text-sm text-center rounded-lg transition
                                                                  border-neutral-300 dark:border-white/[0.1]
                                                                  bg-neutral-50 dark:bg-white/[0.05]
                                                                  text-neutral-800 dark:text-neutral-100
                                                                  focus:ring-2 focus:ring-primary-500/30
                                                                  focus:border-primary-500
                                                                  focus:bg-white dark:focus:bg-white/[0.08]"/>
                                                    <span x-show="selectedUnit" x-text="selectedUnit"
                                                          class="text-sm font-bold text-neutral-700 dark:text-neutral-200 min-w-[1.5rem] shrink-0">
                                                    </span>
                                                </div>
                                            </div>
                                            <button @click="addToBasket()"
                                                    :disabled="!selectedId"
                                                    class="shrink-0 px-4 py-2 rounded-lg text-sm font-semibold transition-all
                                                           bg-primary-500 hover:bg-primary-600 text-white
                                                           disabled:opacity-40 disabled:cursor-not-allowed">
                                                {{ __('Add') }}
                                            </button>
                                        </div>

                                    </div>
                                </div>
                            @endif

                        </div>
                    </div>
                @endforeach
            @endif

        @endif

    </div>
</div>

@if ($user->city)
@assets
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
@endassets

@script
<script>
    const TILE_DARK  = 'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png';
    const TILE_LIGHT = 'https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png';

    const isDark = () => document.documentElement.classList.contains('dark');

    const map = L.map('dashboard-map', {
        center:             [{{ $user->city->lat }}, {{ $user->city->lng }}],
        zoom:               12,
        zoomControl:        false,
        scrollWheelZoom:    false,
        dragging:           false,
        touchZoom:          false,
        doubleClickZoom:    false,
        boxZoom:            false,
        keyboard:           false,
        attributionControl: false,
    });

    let tileLayer = L.tileLayer(isDark() ? TILE_DARK : TILE_LIGHT, { maxZoom: 19 }).addTo(map);

    function swapTile(dark) {
        map.removeLayer(tileLayer);
        tileLayer = L.tileLayer(dark ? TILE_DARK : TILE_LIGHT, { maxZoom: 19 }).addTo(map);
    }

    // Watch for theme changes — fires whenever toggleTheme() changes the dark class
    new MutationObserver(() => swapTile(isDark()))
        .observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
</script>
@endscript
@endif
