<div class="bg-surface-card rounded-2xl border border-neutral-200 dark:border-white/[0.06] shadow-card overflow-hidden">

    {{-- Header --}}
    <div class="px-5 py-4 border-b border-neutral-100 dark:border-white/[0.05]
                flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-sm font-semibold tracking-tight text-neutral-900 dark:text-neutral-100">
            {{ __('Price over time') }}
            @if ($product->unit)
                <span class="text-neutral-400 dark:text-neutral-500 font-normal ml-1">
                    / {{ $product->unit->symbol }}
                </span>
            @endif
        </h2>

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
    function drawChart(labels, values, symbol, categoryColor) {
        const existing = Chart.getChart('price-chart');
        if (existing) existing.destroy();

        const canvas = document.getElementById('price-chart');
        if (!canvas) return;

        const isDark  = document.documentElement.classList.contains('dark');
        const primary = '#10b981'; // emerald-500, matches the primary token

        const gridColor  = isDark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.05)';
        const tickColor  = isDark ? '#6b7280' : '#9ca3af';
        const labelColor = isDark ? '#9ca3af' : '#6b7280';

        new Chart(canvas, {
            type: 'line',
            data: {
                labels,
                datasets: [{
                    data:             values,
                    borderColor:      primary,
                    backgroundColor:  primary + '18',
                    borderWidth:      2,
                    pointRadius:      2,
                    pointHoverRadius: 5,
                    pointBackgroundColor: primary,
                    fill:             true,
                    tension:          0.4,
                }],
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
                        callbacks: {
                            label: ctx => ' ' + symbol + ctx.parsed.y.toFixed(2),
                        },
                    },
                },
                scales: {
                    x: {
                        ticks: {
                            color:          tickColor,
                            maxTicksLimit:  8,
                            maxRotation:    0,
                            font:           { size: 11 },
                        },
                        grid:  { color: gridColor, drawBorder: false },
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color:    tickColor,
                            font:     { size: 11 },
                            callback: v => symbol + v.toFixed(2),
                        },
                        grid: { color: gridColor, drawBorder: false },
                    },
                },
            },
        });
    }

    drawChart(
        @json($rows->pluck('date')),
        @json($rows->pluck('average')),
        @json($rows->first()['symbol'] ?? ''),
        @json($product->category->color)
    );

    $wire.$on('chart-data-updated', ({ rows, categoryColor }) => {
        drawChart(
            rows.map(r => r.date),
            rows.map(r => r.average),
            rows.length ? rows[0].symbol : '',
            categoryColor
        );
    });
</script>
@endscript
