<div class="bg-surface-card rounded-2xl border border-neutral-200 dark:border-white/[0.06] shadow-card overflow-hidden">

    {{-- Header --}}
    <div class="px-5 py-4 border-b border-neutral-100 dark:border-white/[0.05]
                flex flex-wrap items-center justify-between gap-3">
        <div class="min-w-0">
            <div class="flex items-center gap-2">
                <h2 class="text-sm font-semibold tracking-tight text-neutral-900 dark:text-neutral-100">
                    {{ __('Price over time') }}
                    @if ($product->unit)
                        <span class="text-neutral-400 dark:text-neutral-500 font-normal ml-1">
                            / {{ $product->unit->symbol }}
                        </span>
                    @endif
                </h2>
                <div x-data="{ tip: false }" class="relative flex items-center shrink-0">
                    <button @mouseenter="tip = true" @mouseleave="tip = false"
                            @focus="tip = true" @blur="tip = false"
                            class="w-4 h-4 rounded-full flex items-center justify-center
                                   text-neutral-400 dark:text-neutral-500
                                   hover:text-neutral-600 dark:hover:text-neutral-300 transition"
                            aria-label="{{ __('Chart info') }}">
                        <svg fill="currentColor" viewBox="0 0 20 20" class="w-3.5 h-3.5">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clip-rule="evenodd"/>
                        </svg>
                    </button>
                    <div x-show="tip" x-cloak
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         class="absolute left-5 top-0 z-50 w-56 p-3 text-xs leading-relaxed
                                bg-white dark:bg-[#1a1e2d] rounded-xl shadow-card-md
                                border border-neutral-200 dark:border-white/[0.1]
                                text-neutral-600 dark:text-neutral-300 pointer-events-none">
                        {{ __('Daily average price from community submissions. Hover a point for the exact value. Change the period to consider more submissions or use the toggle to see data for all cities. Your own submissions appear in a different hue.') }}
                    </div>
                </div>
            </div>
            <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-0.5">
                {{ __('Community-submitted prices over time') }}
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            {{-- Period selector --}}
            <div class="flex items-center gap-2">
                <label class="text-xs text-neutral-500 dark:text-neutral-400">{{ __('Period') }}</label>
                <select wire:model.live="days"
                        class="text-xs rounded-lg border-neutral-300 dark:border-white/[0.1]
                               bg-neutral-50 dark:bg-[#1e2231] text-neutral-800 dark:text-neutral-100
                               focus:ring-2 focus:ring-primary-500/30 focus:border-primary-500 transition
                               py-1.5">
                    <option value="30">30 {{ __('days') }}</option>
                    <option value="90">90 {{ __('days') }}</option>
                    <option value="180">180 {{ __('days') }}</option>
                    <option value="365">{{ __('1 year') }}</option>
                    <option value="0">{{ __('All time') }}</option>
                </select>
            </div>

            {{-- All cities toggle --}}
            <label class="flex items-center gap-2 cursor-pointer group">
                <div class="relative">
                    <input type="checkbox" wire:model.live="allCities" class="sr-only peer"/>
                    <div class="w-8 h-4 rounded-full bg-neutral-300 dark:bg-neutral-600
                                peer-checked:bg-primary-500 transition-colors"></div>
                    <div class="absolute top-0.5 left-0.5 w-3 h-3 rounded-full bg-white
                                shadow-sm transition-transform peer-checked:translate-x-4"></div>
                </div>
                <span class="text-xs text-neutral-600 dark:text-neutral-400 select-none">
                    {{ __('All cities') }}
                </span>
            </label>
        </div>
    </div>

    {{-- Chart --}}
    <div class="p-5">
        <div class="hidden bg-primary-500" id="primary-color-probe"></div>
        <div class="hidden bg-accent-500" id="accent-color-probe"></div>
        @if ($rows->isEmpty())
            <div class="h-52 flex flex-col items-center justify-center">
                <svg class="h-8 w-8 text-neutral-300 dark:text-neutral-600 mb-2"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                <p class="text-sm text-neutral-400 dark:text-neutral-500">
                    {{ __('No data for this period') }}
                </p>
            </div>
        @else
            <div class="relative h-64 transition-opacity duration-200" wire:loading.class="opacity-40">
                <canvas id="price-chart"></canvas>
            </div>
        @endif
    </div>

</div>

@script
<script>
    function drawChart(labels, values, symbol, categoryColor, mySubmissions) {
        const existing = Chart.getChart('price-chart');
        if (existing) existing.destroy();

        const canvas = document.getElementById('price-chart');
        if (!canvas) return;

        const probe       = document.getElementById('primary-color-probe');
        const primary     = getComputedStyle(probe).backgroundColor;
        const primaryFill = primary.replace('rgb(', 'rgba(').replace(')', ', 0.09)');

        const accent = getComputedStyle(document.getElementById('accent-color-probe')).backgroundColor;

        const isDark = document.documentElement.classList.contains('dark');

        const gridColor = isDark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.05)';
        const tickColor = isDark ? '#6b7280' : '#9ca3af';

        const subs     = mySubmissions ?? {};
        const myValues = labels.map(label => subs[label]?.price ?? null);

        new Chart(canvas, {
            type: 'line',
            data: {
                labels,
                datasets: [
                    {
                        data:                 values,
                        borderColor:          primary,
                        backgroundColor:      primaryFill,
                        borderWidth:          2,
                        pointRadius:          2,
                        pointHoverRadius:     5,
                        pointBackgroundColor: primary,
                        fill:                 true,
                        tension:              0.4,
                    },
                    {
                        data:                 myValues,
                        showLine:             false,
                        pointRadius:          myValues.map(v => v !== null ? 5 : 0),
                        pointHoverRadius:     myValues.map(v => v !== null ? 7 : 0),
                        pointHitRadius:       myValues.map(v => v !== null ? 10 : 0),
                        pointBackgroundColor: accent,
                        pointBorderColor:     accent,
                        pointBorderWidth:     2,
                    },
                ],
            },
            options: {
                responsive:          true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: isDark ? '#1a1e2d' : '#ffffff',
                        titleColor:      isDark ? '#e5e7eb' : '#111827',
                        bodyColor:       isDark ? '#9ca3af' : '#6b7280',
                        borderColor:     isDark ? 'rgba(255,255,255,0.08)' : 'rgba(0,0,0,0.1)',
                        borderWidth:     1,
                        padding:         10,
                        filter:          item => item.parsed.y !== null,
                        callbacks: {
                            label: ctx => ctx.datasetIndex === 1
                                ? ' {{ __("Your submission") }}: ' + symbol + ctx.parsed.y.toFixed(2)
                                : ' ' + symbol + ctx.parsed.y.toFixed(2),
                        },
                    },
                },
                scales: {
                    x: {
                        ticks: { color: tickColor, maxTicksLimit: 8, maxRotation: 0, font: { size: 11 } },
                        grid:  { color: gridColor, drawBorder: false },
                    },
                    y: {
                        beginAtZero: true,
                        ticks: { color: tickColor, font: { size: 11 }, callback: v => symbol + v.toFixed(2) },
                        grid:  { color: gridColor, drawBorder: false },
                    },
                },
            },
        });
    }

    drawChart(
        @json($rows->pluck('date')),
        @json($rows->pluck('average')),
        @json($rows->first()['symbol'] ?? ''),
        @json($product->category->color),
        @json($mySubmissions)
    );

    $wire.$on('chart-data-updated', ({ rows, categoryColor, mySubmissions }) => {
        drawChart(
            rows.map(r => r.date),
            rows.map(r => r.average),
            rows.length ? rows[0].symbol : '',
            categoryColor,
            mySubmissions ?? {}
        );
    });
</script>
@endscript
