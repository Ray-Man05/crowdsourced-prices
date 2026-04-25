<div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-5 shadow-sm">

    <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
        <h2 class="text-base font-semibold text-neutral-800 dark:text-neutral-100">
            {{ __('Prices by city') }}
        </h2>
        <input
            type="text"
            wire:model.live.debounce.200ms="citySearch"
            placeholder="{{ __('Search cities...') }}"
            class="text-sm rounded-lg border-neutral-300 dark:border-neutral-600
                   bg-white dark:bg-neutral-800 text-neutral-800 dark:text-neutral-100
                   focus:ring focus:ring-primary-300 w-48"
        />
    </div>

    @if ($cityStats->isEmpty())
        <p class="text-center text-sm text-neutral-400 dark:text-neutral-500 py-6">
            {{ __('No data found') }}
        </p>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-neutral-200 dark:border-neutral-700">
                        @foreach ([
                            ['key' => 'name',        'label' => __('City'),        'align' => 'left'],
                            ['key' => 'price',       'label' => __('Avg. price'),  'align' => 'right'],
                            ['key' => 'submissions', 'label' => __('Submissions'), 'align' => 'right'],
                        ] as $col)
                            <th class="pb-2 {{ $col['align'] === 'right' ? 'text-right' : 'text-left' }} pr-4">
                                <button
                                    wire:click="toggleSort('{{ $col['key'] }}')"
                                    class="inline-flex items-center gap-1 text-xs font-semibold uppercase
                                           tracking-wide text-neutral-500 dark:text-neutral-400
                                           hover:text-neutral-800 dark:hover:text-neutral-200
                                           {{ $col['align'] === 'right' ? 'ml-auto' : '' }}"
                                >
                                    {{ $col['label'] }}
                                    @if ($sortBy === $col['key'])
                                        {{ $sortDir === 'asc' ? '↑' : '↓' }}
                                    @else
                                        <span class="opacity-30">↕</span>
                                    @endif
                                </button>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100 dark:divide-neutral-700">
                    @foreach ($cityStats as $row)
                        <tr class="hover:bg-neutral-50 dark:hover:bg-neutral-700/40 transition">
                            <td class="py-2.5 pr-4 font-medium text-neutral-800 dark:text-neutral-100">
                                {{ $row['city']->name }}
                                <span class="text-xs text-neutral-400 dark:text-neutral-500 font-normal ml-1">
                                    {{ $row['city']->country->name ?? '' }}
                                </span>
                            </td>
                            <td class="py-2.5 pr-4 text-right font-semibold text-primary-600 dark:text-primary-400">
                                {{ $row['symbol'] }}{{ number_format($row['average'], 2) }}
                                @if ($unit)
                                    <span class="text-xs font-normal text-neutral-400 dark:text-neutral-500">
                                        / {{ $unit->symbol }}
                                    </span>
                                @endif
                            </td>
                            <td class="py-2.5 text-right text-neutral-500 dark:text-neutral-400">
                                {{ $row['submissions'] }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

</div>