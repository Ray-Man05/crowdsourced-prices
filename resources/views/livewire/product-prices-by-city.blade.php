<div class="bg-surface-card rounded-2xl border border-neutral-200 dark:border-white/[0.06] shadow-card overflow-hidden">

    {{-- Header --}}
    <div class="px-5 py-4 border-b border-neutral-100 dark:border-white/[0.05] flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-sm font-semibold tracking-tight text-neutral-900 dark:text-neutral-100">
                {{ __('Prices by city') }}
            </h2>
            @if ($cityStats->isNotEmpty())
                <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-0.5">
                    {{ $cityStats->count() }} {{ __('cities') }}
                </p>
            @endif
        </div>

        {{-- Filters --}}
        <div class="flex flex-wrap items-center gap-2">
            <div class="relative">
                <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-neutral-400 pointer-events-none"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input
                    type="text"
                    wire:model.live.debounce.200ms="citySearch"
                    placeholder="{{ __('City…') }}"
                    class="pl-8 text-sm rounded-lg border-neutral-300 dark:border-white/[0.1]
                           bg-neutral-50 dark:bg-white/[0.04] text-neutral-800 dark:text-neutral-100
                           placeholder-neutral-400 dark:placeholder-neutral-500
                           focus:ring-2 focus:ring-primary-500/30 focus:border-primary-500
                           focus:bg-white dark:focus:bg-white/[0.07] transition w-32"
                />
            </div>
            <div class="relative">
                <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-neutral-400 pointer-events-none"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          {{-- d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064" --}}
                          d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 0 1 3 12c0-1.605.42-3.113 1.157-4.418"
                          />
                </svg>
                <input
                    type="text"
                    wire:model.live.debounce.200ms="countrySearch"
                    placeholder="{{ __('Country…') }}"
                    class="pl-8 text-sm rounded-lg border-neutral-300 dark:border-white/[0.1]
                           bg-neutral-50 dark:bg-white/[0.04] text-neutral-800 dark:text-neutral-100
                           placeholder-neutral-400 dark:placeholder-neutral-500
                           focus:ring-2 focus:ring-primary-500/30 focus:border-primary-500
                           focus:bg-white dark:focus:bg-white/[0.07] transition w-32"
                />
            </div>
            @if ($citySearch || $countrySearch)
                <button
                    wire:click="$set('citySearch', ''); $set('countrySearch', '')"
                    class="text-xs text-neutral-500 dark:text-neutral-400 hover:text-error-600 dark:hover:text-error-400
                           transition focus-visible:outline-none"
                >
                    {{ __('Clear') }}
                </button>
            @endif
        </div>
    </div>

    @if ($cityStats->isEmpty())
        <div class="py-12 text-center">
            <svg class="mx-auto h-8 w-8 text-neutral-300 dark:text-neutral-600 mb-2"
                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p class="text-sm text-neutral-400 dark:text-neutral-500">{{ __('No cities match your filters') }}</p>
        </div>
    @else
        <div class="overflow-x-auto transition-opacity duration-200" wire:loading.class="opacity-40">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-neutral-100 dark:border-white/[0.05]">
                        @foreach ([
                            ['key' => 'name',        'label' => __('City'),        'align' => 'left'],
                            ['key' => 'price',       'label' => __('Avg. price'),  'align' => 'right'],
                            ['key' => 'submissions', 'label' => __('Submissions'), 'align' => 'right'],
                        ] as $col)
                            <th class="px-5 py-3 {{ $col['align'] === 'right' ? 'text-right' : 'text-left' }}">
                                <button
                                    wire:click="toggleSort('{{ $col['key'] }}')"
                                    class="inline-flex items-center gap-1 text-[10px] font-bold uppercase
                                           tracking-widest text-neutral-500 dark:text-neutral-400
                                           hover:text-neutral-800 dark:hover:text-neutral-200
                                           focus-visible:outline-none transition
                                           {{ $col['align'] === 'right' ? 'ml-auto' : '' }}"
                                >
                                    {{ $col['label'] }}
                                    @if ($sortBy === $col['key'])
                                        <span class="text-primary-500 text-xs">{{ $sortDir === 'asc' ? '↑' : '↓' }}</span>
                                    @else
                                        <span class="opacity-20 text-xs">↕</span>
                                    @endif
                                </button>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-50 dark:divide-white/[0.03]">
                    @foreach ($cityStats as $row)
                        <tr class="group hover:bg-neutral-50 dark:hover:bg-white/[0.03] transition-colors">
                            <td class="px-5 py-3">
                                <span class="font-medium text-neutral-800 dark:text-neutral-100">
                                    {{ $row['city']->name }}
                                </span>
                                <span class="text-xs text-neutral-400 dark:text-neutral-500 ml-1.5 font-normal">
                                    {{ $row['city']->country->name ?? '' }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-right font-semibold tabular-nums text-primary-600 dark:text-primary-400">
                                {{ $row['symbol'] }}{{ number_format($row['average'], 2) }}
                                @if ($unit)
                                    <span class="text-xs font-normal text-neutral-400 dark:text-neutral-500">
                                        / {{ $unit->symbol }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-right tabular-nums text-neutral-500 dark:text-neutral-400">
                                {{ $row['submissions'] }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

</div>
