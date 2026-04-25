<div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200
            dark:border-neutral-700 p-5 shadow-sm">

    {{-- Controls --}}
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <h2 class="text-base font-semibold text-neutral-800 dark:text-neutral-100">
            {{ __('Price over time') }} / {{ $product->unit->symbol }}
        </h2>
        <div class="flex flex-wrap items-center gap-4">
            <div class="flex items-center gap-2 text-sm text-neutral-700 dark:text-neutral-300">
                <label>{{ __('Period') }}</label>
                <select wire:model.live="days"
                        class="rounded-lg border-neutral-300 dark:border-neutral-600
                               bg-white dark:bg-neutral-800 text-neutral-800 dark:text-neutral-100
                               text-sm focus:ring focus:ring-accent-300">
                    <option value="30">30 {{ __('days') }}</option>
                    <option value="90">90 {{ __('days') }}</option>
                    <option value="180">180 {{ __('days') }}</option>
                    <option value="365">{{ __('1 year') }}</option>
                    <option value="0">{{ __('All time') }}</option>
                </select>
            </div>
            <label class="flex items-center gap-2 text-sm text-neutral-700 dark:text-neutral-300 cursor-pointer">
                <input type="checkbox" wire:model.live="allCities"
                       class="rounded border-neutral-300 dark:border-neutral-600 text-accent-600"/>
                {{ __('All cities') }}
            </label>
        </div>
    </div>

    @if ($rows->isEmpty())
        <p class="text-center text-sm text-neutral-400 dark:text-neutral-500 py-12">
            {{ __('No data for this period') }}
        </p>
    @else
        <div class="relative h-64">
            <canvas id="price-chart"></canvas>
        </div>
    @endif

</div>

{{-- @assets
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
@endassets --}}

@script
<script>

    function getPrimary() {
        const val = getComputedStyle(document.documentElement)
            .getPropertyValue('--color-theme-accent')
            .trim();
        return val && val !== 'auto' ? val : '#00d3f2';
    }

    function drawChart(labels, values, symbol, categoryColor) {
        const existing = Chart.getChart('price-chart');
        if (existing) existing.destroy();

        // const primary = categoryColor
        //     || getComputedStyle(document.documentElement)
        //         .getPropertyValue('--color-theme-primary').trim()
        //     || '#6366f1';

        const primary = getPrimary() || categoryColor;
        
        console.log(primary);
        console.log('efe')

        const canvas = document.getElementById('price-chart');
        if (!canvas) return;

        const isDark = document.documentElement.classList.contains('dark');

        new Chart(canvas, {
            type: 'line',
            data: {
                labels,
                datasets: [{
                    data:             values,
                    borderColor:      primary,
                    // backgroundColor:  'rgba(99, 102, 241, 0.08)',
                    backgroundColor:  primary + '14',
                    borderWidth:      2,
                    pointRadius:      3,
                    pointHoverRadius: 5,
                    fill:             true,
                    tension:          0.3,
                }],
            },
            options: {
                responsive:          true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: ctx => symbol + ctx.parsed.y.toFixed(2),
                        },
                    },
                },
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Date',  
                            color: isDark ? '#9ca3af' : '#374151',
                            font: {
                                size: 12,
                                weight: 'bold'
                            }
                        },
                        ticks: { color: isDark ? '#9ca3af' : '#6b7280', maxTicksLimit: 10, maxRotation: 0 },
                        grid:  { color: isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.05)' },
                    },
                    y: {        
                        title: {
                            display: true,
                            text: 'Price',
                            color: isDark ? '#9ca3af' : '#374151',
                            font: {
                                size: 12,
                                weight: 'bold'
                            }
                        },
                        beginAtZero: true,
                        ticks: {
                            color:    isDark ? '#9ca3af' : '#6b7280',
                            callback: v => symbol + v.toFixed(2),
                        },
                        grid: { color: isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.05)' },
                    },
                },
            },
        });
    }

    // Initial draw from server-rendered data
    drawChart(
        @json($rows->pluck('date')),
        @json($rows->pluck('average')),
        @json($rows->first()['symbol'] ?? ''),
        @json($product->category->color)
    );

    // Redraw when PHP dispatches updated data
    $wire.$on('chart-data-updated', ({ rows, categoryColor }) => {
        const labels = rows.map(r => r.date);
        const values = rows.map(r => r.average);
        const symbol = rows.length ? rows[0].symbol : '';
        drawChart(labels, values, symbol, categoryColor);
    });
</script>
@endscript