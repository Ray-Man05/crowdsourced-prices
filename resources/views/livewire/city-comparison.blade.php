{{--
    City data (3989 cities + categories) is pushed to the @stack('scripts') in app.blade.php
    ONCE on the initial page load. Livewire AJAX responses do NOT include @push content,
    so subsequent renders stay small. Alpine reads from window.__cities et al.
--}}
@push('scripts')
<script>
    window.__cities          = @json($cities);
    window.__citiesWithData  = @json($citiesWithData);
    window.__categories      = @json($categories);
</script>
@endpush

@php $basketColors = ['#10b981','#06b6d4','#3b82f6','#8b5cf6','#ec4899','#f97316','#f59e0b','#ef4444']; @endphp

<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-5">

    {{-- ── Page header ────────────────────────────────────────────────── --}}
    <div class="relative rounded-2xl border border-neutral-200/80 dark:border-white/[0.06] shadow-card">
        <div class="absolute inset-0 backdrop-blur-sm bg-white dark:bg-[#12151f] rounded-2xl"></div>
        <div class="relative px-5 py-5 flex items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-neutral-900 dark:text-white">
                    {{ __('Compare Cities') }}
                </h1>
                <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                    {{ __('Side-by-side product prices and basket totals for any two cities') }}
                </p>
            </div>
            @if ($cityAId && $cityBId)
                <div class="shrink-0 flex p-0.5 gap-0.5 rounded-xl bg-neutral-100 dark:bg-white/[0.06]">
                    @foreach ([['30','30d'],['90','90d'],['365','1yr'],['0','All']] as [$val,$label])
                        <button wire:click="setDays('{{ $val }}')"
                                class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all
                                       {{ $days === $val
                                           ? 'bg-white dark:bg-white/[0.12] shadow-sm text-neutral-800 dark:text-white'
                                           : 'text-neutral-400 dark:text-neutral-500 hover:text-neutral-600 dark:hover:text-neutral-300' }}">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- ── City selectors ──────────────────────────────────────────────── --}}
    {{--
        ONE Alpine scope for the whole grid.
        - window.__cities / window.__citiesWithData: set once on page load (see @push above)
        - Country change with no city selected: pure Alpine, ZERO round-trips
        - Country change after city selected: one action call (clearCityA/B)
        - City selection: one action call (selectCityA/B)
        - NOTE: overflow-hidden is intentionally ABSENT — present on any parent would
          clip the absolute-positioned dropdown lists.
    --}}
    <div class="grid grid-cols-1 sm:grid-cols-[1fr_56px_1fr] gap-3 items-start"
         wire:key="city-grid-{{ $cityAId ?? 'n' }}-{{ $cityBId ?? 'n' }}-{{ $cityACountryId ?? 'n' }}-{{ $cityBCountryId ?? 'n' }}"
         x-data="{
             cities:         window.__cities         ?? [],
             citiesWithData: window.__citiesWithData ?? {},
             a: {
                 open: false, search: '', activeIdx: -1,
                 name: @js($cityAName),
                 countryId: {{ $cityACountryId ?? 'null' }},
             },
             b: {
                 open: false, search: '', activeIdx: -1,
                 name: @js($cityBName),
                 countryId: {{ $cityBCountryId ?? 'null' }},
             },

             filtered(slot) {
                 const s   = this[slot];
                 const cid = s.countryId;
                 const q   = s.search.toLowerCase();
                 return this.cities
                     .filter(c => (!cid || c.country_id === cid) && (!q || c.name.toLowerCase().includes(q)))
                     .sort((a, b) => {
                         const aH = !!this.citiesWithData[a.id];
                         const bH = !!this.citiesWithData[b.id];
                         if (aH !== bH) return aH ? -1 : 1;
                         return a.name.localeCompare(b.name);
                     });
             },

             changeCountry(slot, val) {
                 const s = this[slot];
                 const newCountryId = val ? parseInt(val) : null;
                 const hadCity = !!s.name;
                 s.countryId = newCountryId;
                 s.name = ''; s.search = ''; s.open = false; s.activeIdx = -1;
                 if (hadCity) {
                     // A city was selected — clear it server-side in one round-trip.
                     $wire.call(slot === 'a' ? 'clearCityA' : 'clearCityB', newCountryId);
                 }
             },

             selectCity(slot, city) {
                 this[slot].name = city.name;
                 this[slot].search = '';
                 this[slot].open = false;
                 this[slot].activeIdx = -1;
                 $wire.call(
                     slot === 'a' ? 'selectCityA' : 'selectCityB',
                     city.id,
                     city.country_id
                 );
             }
         }">

        {{-- City A --}}
        <div class="relative rounded-2xl border border-neutral-200/80 dark:border-white/[0.06] shadow-card"
             @click.outside="a.open = false; a.activeIdx = -1">
            <div class="absolute inset-0 backdrop-blur-sm bg-white dark:bg-[#12151f] rounded-2xl pointer-events-none"></div>
            <div class="relative p-5 space-y-3">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-primary-500 shrink-0"></span>
                    <span class="text-sm font-semibold text-neutral-900 dark:text-white">{{ __('City A') }}</span>
                    <template x-if="a.name">
                        <span class="ml-auto text-xs font-medium text-primary-600 dark:text-primary-400 truncate"
                              x-text="a.name"></span>
                    </template>
                </div>

                <div>
                    <label class="block text-xs font-medium mb-1.5 text-neutral-500 dark:text-neutral-400">{{ __('Country') }}</label>
                    <select x-init="$el.value = a.countryId ?? ''"
                            @change="changeCountry('a', $event.target.value)"
                            class="block w-full text-sm rounded-lg transition
                                   border-neutral-300 dark:border-white/[0.1]
                                   bg-neutral-50 dark:bg-white/[0.05]
                                   text-neutral-800 dark:text-neutral-100
                                   focus:ring-2 focus:ring-primary-500/30 focus:border-primary-500">
                        <option value="">{{ __('Select a country') }}</option>
                        @foreach ($countries as $country)
                            <option value="{{ $country->id }}">{{ $country->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="relative">
                    <label class="block text-xs font-medium mb-1.5 text-neutral-500 dark:text-neutral-400">{{ __('City') }}</label>
                    <div class="relative">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-neutral-400 pointer-events-none"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input type="text"
                               :value="a.name || a.search"
                               @focus="if (a.countryId) a.open = true"
                               @input="a.search = $event.target.value; a.name = ''; a.open = true; a.activeIdx = -1"
                               @click.outside="a.open = false; a.search = ''; if (!a.name) $el.value = ''"
                               :placeholder="a.countryId ? @js(__('Type to search...')) : @js(__('Select a country first'))"
                               :disabled="!a.countryId"
                               @keydown.down.prevent="a.activeIdx = Math.min(a.activeIdx + 1, filtered('a').length - 1); a.open = true"
                               @keydown.up.prevent="a.activeIdx = Math.max(a.activeIdx - 1, 0)"
                               @keydown.enter.prevent="if (filtered('a').length > 0) selectCity('a', filtered('a')[a.activeIdx >= 0 ? a.activeIdx : 0])"
                               @keydown.escape.prevent="a.open = false; a.search = ''"
                               autocomplete="off"
                               class="block w-full pl-9 text-sm rounded-lg transition
                                      border-neutral-300 dark:border-white/[0.1]
                                      bg-neutral-50 dark:bg-white/[0.05]
                                      text-neutral-800 dark:text-neutral-100
                                      placeholder-neutral-400 dark:placeholder-neutral-500
                                      disabled:opacity-50 disabled:cursor-not-allowed
                                      focus:ring-2 focus:ring-primary-500/30 focus:border-primary-500
                                      focus:bg-white dark:focus:bg-white/[0.08]"/>
                    </div>

                    <ul x-show="a.open && filtered('a').length > 0"
                        x-ref="listA"
                        x-effect="if (a.activeIdx >= 0 && $refs.listA) { const li = $refs.listA.querySelectorAll('li')[a.activeIdx]; if (li) li.scrollIntoView({ block: 'nearest' }); }"
                        style="display:none"
                        class="absolute z-30 mt-1 w-full rounded-xl shadow-card-md max-h-56 overflow-y-auto
                               bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-white/[0.08]">
                        <template x-for="(city, idx) in filtered('a')" :key="city.id">
                            <li @click="selectCity('a', city)"
                                :class="{
                                    'bg-primary-50 dark:bg-primary-900/30': {{ $cityAId ?? 'null' }} === city.id,
                                    'bg-neutral-100 dark:bg-neutral-700/60': {{ $cityAId ?? 'null' }} !== city.id && idx === a.activeIdx,
                                    'opacity-50': !citiesWithData[city.id]
                                }"
                                class="px-4 py-2 cursor-pointer text-sm transition-colors
                                       flex items-center justify-between gap-2
                                       hover:bg-neutral-50 dark:hover:bg-white/[0.04]">
                                <span x-text="city.name"
                                      :class="citiesWithData[city.id] ? 'text-neutral-900 dark:text-neutral-100' : 'text-neutral-500 dark:text-neutral-400'">
                                </span>
                                <span x-show="!citiesWithData[city.id]"
                                      class="text-[10px] italic text-neutral-400 dark:text-neutral-600 shrink-0">no data</span>
                            </li>
                        </template>
                    </ul>
                    <p x-show="a.open && a.countryId && a.search && filtered('a').length === 0"
                       class="mt-1 text-xs text-neutral-400 dark:text-neutral-500">
                        {{ __('No cities found.') }}
                    </p>
                </div>
            </div>
        </div>

        {{-- VS + swap --}}
        <div class="hidden sm:flex flex-col items-center justify-center pt-14 gap-3">
            <span class="text-sm font-bold text-neutral-300 dark:text-neutral-600 uppercase tracking-widest">vs</span>
            @if ($cityAId && $cityBId)
                <button wire:click="swapCities"
                        title="{{ __('Swap cities') }}"
                        class="p-2 rounded-xl transition text-neutral-400 dark:text-neutral-500
                               hover:text-primary-600 dark:hover:text-primary-400
                               hover:bg-primary-50 dark:hover:bg-primary-900/20">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                    </svg>
                </button>
            @endif
        </div>

        {{-- City B --}}
        <div class="relative rounded-2xl border border-neutral-200/80 dark:border-white/[0.06] shadow-card"
             @click.outside="b.open = false; b.activeIdx = -1">
            <div class="absolute inset-0 backdrop-blur-sm bg-white dark:bg-[#12151f] rounded-2xl pointer-events-none"></div>
            <div class="relative p-5 space-y-3">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-accent-500 shrink-0"></span>
                    <span class="text-sm font-semibold text-neutral-900 dark:text-white">{{ __('City B') }}</span>
                    <template x-if="b.name">
                        <span class="ml-auto text-xs font-medium text-accent-600 dark:text-accent-400 truncate"
                              x-text="b.name"></span>
                    </template>
                </div>

                <div>
                    <label class="block text-xs font-medium mb-1.5 text-neutral-500 dark:text-neutral-400">{{ __('Country') }}</label>
                    <select x-init="$el.value = b.countryId ?? ''"
                            @change="changeCountry('b', $event.target.value)"
                            class="block w-full text-sm rounded-lg transition
                                   border-neutral-300 dark:border-white/[0.1]
                                   bg-neutral-50 dark:bg-white/[0.05]
                                   text-neutral-800 dark:text-neutral-100
                                   focus:ring-2 focus:ring-accent-500/30 focus:border-accent-500">
                        <option value="">{{ __('Select a country') }}</option>
                        @foreach ($countries as $country)
                            <option value="{{ $country->id }}">{{ $country->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="relative">
                    <label class="block text-xs font-medium mb-1.5 text-neutral-500 dark:text-neutral-400">{{ __('City') }}</label>
                    <div class="relative">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-neutral-400 pointer-events-none"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input type="text"
                               :value="b.name || b.search"
                               @focus="if (b.countryId) b.open = true"
                               @input="b.search = $event.target.value; b.name = ''; b.open = true; b.activeIdx = -1"
                               @click.outside="b.open = false; b.search = ''; if (!b.name) $el.value = ''"
                               :placeholder="b.countryId ? @js(__('Type to search...')) : @js(__('Select a country first'))"
                               :disabled="!b.countryId"
                               @keydown.down.prevent="b.activeIdx = Math.min(b.activeIdx + 1, filtered('b').length - 1); b.open = true"
                               @keydown.up.prevent="b.activeIdx = Math.max(b.activeIdx - 1, 0)"
                               @keydown.enter.prevent="if (filtered('b').length > 0) selectCity('b', filtered('b')[b.activeIdx >= 0 ? b.activeIdx : 0])"
                               @keydown.escape.prevent="b.open = false; b.search = ''"
                               autocomplete="off"
                               class="block w-full pl-9 text-sm rounded-lg transition
                                      border-neutral-300 dark:border-white/[0.1]
                                      bg-neutral-50 dark:bg-white/[0.05]
                                      text-neutral-800 dark:text-neutral-100
                                      placeholder-neutral-400 dark:placeholder-neutral-500
                                      disabled:opacity-50 disabled:cursor-not-allowed
                                      focus:ring-2 focus:ring-accent-500/30 focus:border-accent-500
                                      focus:bg-white dark:focus:bg-white/[0.08]"/>
                    </div>

                    <ul x-show="b.open && filtered('b').length > 0"
                        x-ref="listB"
                        x-effect="if (b.activeIdx >= 0 && $refs.listB) { const li = $refs.listB.querySelectorAll('li')[b.activeIdx]; if (li) li.scrollIntoView({ block: 'nearest' }); }"
                        style="display:none"
                        class="absolute z-30 mt-1 w-full rounded-xl shadow-card-md max-h-56 overflow-y-auto
                               bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-white/[0.08]">
                        <template x-for="(city, idx) in filtered('b')" :key="city.id">
                            <li @click="selectCity('b', city)"
                                :class="{
                                    'bg-accent-50 dark:bg-accent-900/30': {{ $cityBId ?? 'null' }} === city.id,
                                    'bg-neutral-100 dark:bg-neutral-700/60': {{ $cityBId ?? 'null' }} !== city.id && idx === b.activeIdx,
                                    'opacity-50': !citiesWithData[city.id]
                                }"
                                class="px-4 py-2 cursor-pointer text-sm transition-colors
                                       flex items-center justify-between gap-2
                                       hover:bg-neutral-50 dark:hover:bg-white/[0.04]">
                                <span x-text="city.name"
                                      :class="citiesWithData[city.id] ? 'text-neutral-900 dark:text-neutral-100' : 'text-neutral-500 dark:text-neutral-400'">
                                </span>
                                <span x-show="!citiesWithData[city.id]"
                                      class="text-[10px] italic text-neutral-400 dark:text-neutral-600 shrink-0">no data</span>
                            </li>
                        </template>
                    </ul>
                    <p x-show="b.open && b.countryId && b.search && filtered('b').length === 0"
                       class="mt-1 text-xs text-neutral-400 dark:text-neutral-500">
                        {{ __('No cities found.') }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- Mobile swap --}}
    @if ($cityAId && $cityBId)
        <div class="flex sm:hidden justify-center">
            <button wire:click="swapCities"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium
                           transition border border-neutral-200 dark:border-white/[0.08]
                           text-neutral-500 dark:text-neutral-400
                           hover:text-primary-600 dark:hover:text-primary-400
                           bg-white dark:bg-[#12151f]">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                </svg>
                {{ __('Swap cities') }}
            </button>
        </div>
    @endif

    {{-- ── Empty state ─────────────────────────────────────────────────── --}}
    @if (!$cityAId || !$cityBId)
        <div class="relative rounded-2xl border border-neutral-200/80 dark:border-white/[0.06] shadow-card">
            <div class="absolute inset-0 backdrop-blur-sm bg-white dark:bg-[#12151f] rounded-2xl"></div>
            <div class="relative py-16 flex flex-col items-center text-center px-8">
                <div class="w-14 h-14 rounded-2xl flex items-center justify-center mb-4 bg-neutral-100 dark:bg-white/[0.05]">
                    <svg class="h-7 w-7 text-neutral-300 dark:text-neutral-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/>
                    </svg>
                </div>
                <p class="text-sm font-semibold text-neutral-600 dark:text-neutral-300">
                    {{ __('Select both cities to start comparing') }}
                </p>
                <p class="text-xs mt-1 text-neutral-400 dark:text-neutral-500 max-w-sm">
                    {{ __('Choose a country and city for each slot above to see side-by-side prices across all products and your baskets.') }}
                </p>
            </div>
        </div>
    @endif

    {{-- ── Product comparison + baskets (always present once both cities selected) ── --}}
    @if ($cityAId && $cityBId)

        {{--
            Skeleton is INSIDE this @if block (both cities selected) so it IS in the DOM
            before the user clicks "Compare". wire:loading then shows it immediately when
            the compute() request starts, giving instant visual feedback.
        --}}
        <div class="relative rounded-2xl overflow-hidden border border-neutral-200/80 dark:border-white/[0.06] shadow-card">
            <div class="absolute inset-0 backdrop-blur-sm bg-white dark:bg-[#12151f]"></div>
            <div class="relative">

                {{-- Card header --}}
                <div class="px-5 py-4 border-b border-black/[0.06] dark:border-white/[0.06]
                            flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="text-base font-semibold tracking-tight text-neutral-900 dark:text-white">
                            {{ __('Product Prices') }}
                        </h2>
                        @if ($comparison)
                            <p class="text-xs mt-0.5 text-neutral-400 dark:text-neutral-500">
                                {{ $comparison['products_a'] }} {{ __('products in :city', ['city' => $comparison['city_a']->name]) }}
                                &middot;
                                {{ $comparison['products_b'] }} {{ __('products in :city', ['city' => $comparison['city_b']->name]) }}
                            </p>
                        @endif
                    </div>
                    <div class="hidden sm:flex items-center gap-4 text-xs font-semibold">
                        <span class="flex items-center gap-1.5 text-primary-600 dark:text-primary-400">
                            <span class="w-2 h-2 rounded-full bg-primary-500"></span>{{ $cityAName }}
                        </span>
                        <span class="flex items-center gap-1.5 text-accent-600 dark:text-accent-400">
                            <span class="w-2 h-2 rounded-full bg-accent-500"></span>{{ $cityBName }}
                        </span>
                    </div>
                </div>

                {{-- Loading skeleton — in the DOM, shows during compute() round-trip --}}
                <div wire:loading wire:target="compute,setDays,swapCities" class="px-5 py-6 space-y-4">
                    <div class="h-2.5 w-28 rounded-full animate-pulse bg-neutral-200 dark:bg-white/[0.07]"></div>
                    @foreach ([62, 44, 70, 38, 55, 48, 75, 42] as $w)
                        <div class="flex items-center gap-4">
                            <div class="h-3 rounded-full animate-pulse bg-neutral-200 dark:bg-white/[0.07]"
                                 style="width: {{ $w }}%"></div>
                            <div class="ml-auto flex gap-5">
                                <div class="h-3 w-14 rounded-full animate-pulse bg-primary-100 dark:bg-primary-900/30"></div>
                                <div class="h-3 w-14 rounded-full animate-pulse bg-accent-100 dark:bg-accent-900/30"></div>
                                <div class="h-3 w-10 rounded-full animate-pulse bg-neutral-200 dark:bg-white/[0.07]"></div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Content: hidden while loading --}}
                <div wire:loading.remove wire:target="compute,setDays,swapCities">
                    @if (!$showComparison)
                        {{-- CTA: ready to compare --}}
                        <div class="px-5 py-10 flex flex-col sm:flex-row items-center justify-between gap-4">
                            <div class="text-center sm:text-left">
                                <p class="text-sm font-semibold text-neutral-800 dark:text-neutral-100">
                                    {{ __('Ready to compare') }}
                                    <span class="text-primary-600 dark:text-primary-400">{{ $cityAName }}</span>
                                    {{ __('and') }}
                                    <span class="text-accent-600 dark:text-accent-400">{{ $cityBName }}</span>
                                </p>
                                <p class="text-xs mt-0.5 text-neutral-400 dark:text-neutral-500">
                                    {{ __('Click to load prices for all products and your baskets.') }}
                                </p>
                            </div>
                            <button wire:click="compute"
                                    class="shrink-0 inline-flex items-center gap-2 px-5 py-2.5 rounded-xl
                                           text-sm font-bold transition-all shadow-sm
                                           bg-primary-600 hover:bg-primary-700 active:scale-95 text-white">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7"/>
                                </svg>
                                {{ __('Compare') }}
                            </button>
                        </div>
                    @elseif (!$comparison || empty($comparison['sections']))
                        <div class="py-12 flex flex-col items-center text-center px-8">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center mb-3 bg-neutral-100 dark:bg-white/[0.05]">
                                <svg class="h-5 w-5 text-neutral-300 dark:text-neutral-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                          d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                </svg>
                            </div>
                            <p class="text-sm font-medium text-neutral-500 dark:text-neutral-400">
                                {{ __('No price data found for this period') }}
                            </p>
                            <p class="text-xs mt-1 text-neutral-400 dark:text-neutral-500">
                                {{ __('Try a longer period or contribute prices for these cities.') }}
                            </p>
                        </div>
                    @else
                        {{-- Desktop column headers --}}
                        <div class="hidden sm:grid grid-cols-[1fr_120px_120px_96px] gap-x-4
                                    px-5 py-2.5 border-b border-black/[0.04] dark:border-white/[0.04]
                                    bg-neutral-50/80 dark:bg-white/[0.02]">
                            <span class="text-[10px] font-bold uppercase tracking-widest text-neutral-400 dark:text-neutral-600">{{ __('Product') }}</span>
                            <span class="text-[10px] font-bold uppercase tracking-widest text-right text-primary-500">{{ $cityAName }}</span>
                            <span class="text-[10px] font-bold uppercase tracking-widest text-right text-accent-500">{{ $cityBName }}</span>
                            <span class="text-[10px] font-bold uppercase tracking-widest text-right text-neutral-400 dark:text-neutral-600">{{ __('Diff') }}</span>
                        </div>

                        @foreach ($comparison['sections'] as $section)
                            <div class="flex items-center gap-2 px-5 py-2
                                        bg-neutral-50/60 dark:bg-white/[0.02]
                                        border-b border-black/[0.04] dark:border-white/[0.04]">
                                <span class="w-2 h-2 rounded-full shrink-0" style="background-color: {{ $section['category']->color ?? '#9ca3af' }}"></span>
                                <span class="text-[10px] font-bold uppercase tracking-widest" style="color: {{ $section['category']->color ?? '#9ca3af' }}">
                                    {{ $section['category']->name }}
                                </span>
                            </div>

                            @foreach ($section['rows'] as $row)
                                @php
                                    $d     = $row['delta'];
                                    $dCls  = match(true) {
                                        $d === null  => 'text-neutral-400 dark:text-neutral-600',
                                        abs($d) < 2  => 'text-neutral-500 dark:text-neutral-400',
                                        $d > 0       => 'text-error-600 dark:text-error-400',
                                        default      => 'text-success-600 dark:text-success-400',
                                    };
                                    $dLbl  = $d === null ? '—' : (($d > 0 ? '+' : '') . number_format($d, 1) . '%');
                                @endphp
                                <div class="hidden sm:grid grid-cols-[1fr_120px_120px_96px] gap-x-4 items-center
                                            px-5 py-3 border-b border-black/[0.04] dark:border-white/[0.04]
                                            hover:bg-black/[0.015] dark:hover:bg-white/[0.02] transition-colors">
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <a href="{{ route('products.show', $row['product']) }}"
                                           class="text-sm text-neutral-800 dark:text-neutral-100 truncate
                                                  hover:text-primary-600 dark:hover:text-primary-400 transition">
                                            {{ $row['product']->name }}
                                        </a>
                                        @if ($row['product']->unit)
                                            <span class="text-xs shrink-0 text-neutral-400 dark:text-neutral-500">/ {{ $row['product']->unit->symbol }}</span>
                                        @endif
                                    </div>
                                    <span class="text-sm tabular-nums text-right font-medium {{ $row['price_a'] !== null ? 'text-primary-700 dark:text-primary-300' : 'text-neutral-300 dark:text-neutral-700' }}">
                                        {{ $row['price_a'] !== null ? $comparison['symbol'] . number_format($row['price_a'], 2) : '—' }}
                                    </span>
                                    <span class="text-sm tabular-nums text-right font-medium {{ $row['price_b'] !== null ? 'text-accent-700 dark:text-accent-300' : 'text-neutral-300 dark:text-neutral-700' }}">
                                        {{ $row['price_b'] !== null ? $comparison['symbol'] . number_format($row['price_b'], 2) : '—' }}
                                    </span>
                                    <span class="text-xs tabular-nums text-right font-semibold {{ $dCls }}">{{ $dLbl }}</span>
                                </div>
                                <div class="flex sm:hidden flex-col px-5 py-3 gap-1 border-b border-black/[0.04] dark:border-white/[0.04]">
                                    <div class="flex items-center justify-between gap-2">
                                        <a href="{{ route('products.show', $row['product']) }}"
                                           class="text-sm font-medium text-neutral-800 dark:text-neutral-100 truncate
                                                  hover:text-primary-600 dark:hover:text-primary-400 transition">
                                            {{ $row['product']->name }}
                                        </a>
                                        <span class="text-xs font-semibold shrink-0 {{ $dCls }}">{{ $dLbl }}</span>
                                    </div>
                                    <div class="flex items-center gap-3 text-xs">
                                        <span class="flex items-center gap-1 text-primary-600 dark:text-primary-400">
                                            <span class="w-1.5 h-1.5 rounded-full bg-primary-500"></span>
                                            {{ $row['price_a'] !== null ? $comparison['symbol'] . number_format($row['price_a'], 2) : '—' }}
                                        </span>
                                        <span class="text-neutral-300 dark:text-neutral-700">·</span>
                                        <span class="flex items-center gap-1 text-accent-600 dark:text-accent-400">
                                            <span class="w-1.5 h-1.5 rounded-full bg-accent-500"></span>
                                            {{ $row['price_b'] !== null ? $comparison['symbol'] . number_format($row['price_b'], 2) : '—' }}
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        @endforeach
                    @endif
                </div>

            </div>
        </div>

        {{-- ── Baskets section ─────────────────────────────────────────── --}}

        <div class="relative rounded-2xl overflow-hidden border border-neutral-200/80 dark:border-white/[0.06] shadow-card">
            <div class="absolute inset-0 backdrop-blur-sm bg-white dark:bg-[#12151f]"></div>
            <div class="relative px-5 py-4 flex items-center justify-between gap-4">
                <div>
                    <h2 class="text-base font-semibold tracking-tight text-neutral-900 dark:text-white">{{ __('My Baskets') }}</h2>
                    <p class="text-xs mt-0.5 text-neutral-500 dark:text-neutral-400">
                        {{ __('Compare your basket totals between the two selected cities') }}
                    </p>
                </div>
                @if (!$showBasketForm)
                    <button wire:click="openCreateBasket"
                            class="shrink-0 inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-sm
                                   font-semibold transition-all bg-primary-500 hover:bg-primary-600 text-white shadow-sm">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                        </svg>
                        {{ __('New basket') }}
                    </button>
                @endif
            </div>
        </div>

        {{--
            New basket form — color is managed in Alpine (no Livewire round-trip per swatch).
            saveBasket($color) receives the color as a parameter at save time.
        --}}
        @if ($showBasketForm && !$editingBasketId)
            <div x-data="{ formColor: '#10b981', colors: {{ Js::from($basketColors) }} }"
                 class="relative rounded-2xl overflow-hidden border border-neutral-200/80 dark:border-white/[0.06] shadow-card"
                 :style="'border-top: 3px solid ' + formColor">
                <div class="absolute inset-0 backdrop-blur-sm bg-white dark:bg-[#12151f]"></div>
                <div class="relative px-5 py-5 space-y-4">
                    <h3 class="text-sm font-semibold text-neutral-900 dark:text-white">{{ __('New basket') }}</h3>
                    <div>
                        <label class="block text-xs font-medium text-neutral-500 dark:text-neutral-400 mb-1.5">{{ __('Name') }}</label>
                        <input wire:model="basketFormName" type="text" maxlength="80"
                               placeholder="{{ __('e.g. Weekly groceries') }}"
                               x-init="$el.focus()"
                               @keydown.enter.prevent="$wire.call('saveBasket', formColor)"
                               @keydown.escape.prevent="$wire.call('cancelBasketForm')"
                               class="w-full text-sm rounded-lg transition
                                      border-neutral-300 dark:border-white/[0.1]
                                      bg-neutral-50 dark:bg-white/[0.05]
                                      text-neutral-800 dark:text-neutral-100
                                      placeholder-neutral-400 dark:placeholder-neutral-500
                                      focus:ring-2 focus:ring-primary-500/30 focus:border-primary-500
                                      focus:bg-white dark:focus:bg-white/[0.08]"/>
                        @error('basketFormName')<p class="mt-1 text-xs text-error-500">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-neutral-500 dark:text-neutral-400 mb-2">{{ __('Color') }}</label>
                        <div class="flex flex-wrap gap-2">
                            <template x-for="c in colors" :key="c">
                                <button type="button" @click="formColor = c"
                                        :style="'background-color: ' + c"
                                        :class="formColor === c
                                            ? 'ring-2 ring-offset-2 ring-offset-white dark:ring-offset-[#12151f] scale-110'
                                            : 'opacity-70 hover:opacity-100 hover:scale-105'"
                                        class="w-7 h-7 rounded-full transition-all focus:outline-none">
                                </button>
                            </template>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 pt-1">
                        <button @click="$wire.call('saveBasket', formColor)"
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
                    </div>
                </div>
            </div>
        @endif

        {{-- Basket list --}}
        @if ($baskets->isEmpty())
            @if (!$showBasketForm)
                <div class="relative rounded-2xl overflow-hidden border border-neutral-200/80 dark:border-white/[0.06] shadow-card">
                    <div class="absolute inset-0 backdrop-blur-sm bg-white dark:bg-[#12151f]"></div>
                    <div class="relative py-14 flex flex-col items-center text-center px-8">
                        <div class="w-12 h-12 rounded-2xl flex items-center justify-center mb-4 bg-neutral-100 dark:bg-white/[0.05]">
                            <svg class="h-6 w-6 text-neutral-300 dark:text-neutral-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                            </svg>
                        </div>
                        <p class="text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ __('No baskets yet') }}</p>
                        <p class="text-xs mt-1 text-neutral-400 dark:text-neutral-500 max-w-xs">
                            {{ __('Create a basket to group products and compare their combined price across cities.') }}
                        </p>
                        <button wire:click="openCreateBasket"
                                class="mt-4 inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-sm
                                       font-semibold transition-all bg-primary-500 hover:bg-primary-600 text-white shadow-sm">
                            {{ __('Create your first basket') }}
                        </button>
                    </div>
                </div>
            @endif
        @else
            @foreach ($baskets as $basket)
                @php
                    $isOpen = $openBasketId === $basket->id;
                    $iCnt   = $basket->items->count();
                    $bComp  = $basketComparison
                        ? collect($basketComparison['baskets'])->firstWhere('basket.id', $basket->id)
                        : null;
                @endphp

                {{--
                    NOTE: overflow-hidden is intentionally absent here too — would clip
                    the product-search dropdown inside the "add item" form.
                --}}
                <div wire:key="basket-{{ $basket->id }}"
                     class="relative rounded-2xl shadow-card border border-neutral-200/80 dark:border-white/[0.06]"
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
                                 if (v) this.$nextTick(() => this.$refs.editInput?.focus());
                             });
                         },
                         async save() {
                             const name = this.editName.trim();
                             if (!name) return;
                             await $wire.call('updateBasket', {{ $basket->id }}, name, this.editColor);
                             this.savedName = name; this.savedColor = this.editColor; this.editing = false;
                         },
                         cancel() {
                             this.editName = this.savedName; this.editColor = this.savedColor; this.editing = false;
                         }
                     }"
                     @keydown.escape.window="if (editing) { $event.preventDefault(); cancel(); }">
                    <div class="absolute inset-0 backdrop-blur-sm bg-white dark:bg-[#12151f] rounded-2xl"></div>
                    <div class="relative">

                        {{-- Basket header --}}
                        <div class="px-5 py-3.5">
                            <div x-show="!editing" class="flex items-center gap-3">
                                <div class="w-3 h-3 rounded-full shrink-0" style="background-color: {{ $basket->color }}"></div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-semibold text-neutral-900 dark:text-white truncate">{{ $basket->name }}</p>
                                    <p class="text-xs mt-0.5 text-neutral-400 dark:text-neutral-500">
                                        {{ $iCnt }} {{ $iCnt === 1 ? __('item') : __('items') }}
                                    </p>
                                </div>

                                {{-- Price totals in header --}}
                                @if ($bComp && ($bComp['total_a'] > 0 || $bComp['total_b'] > 0))
                                    <div class="hidden sm:flex items-center gap-4 shrink-0">
                                        <div class="text-right">
                                            <p class="text-[10px] font-medium text-primary-500">{{ $cityAName }}</p>
                                            <p class="text-sm font-bold tabular-nums text-primary-700 dark:text-primary-300">
                                                {{ $bComp['total_a'] > 0 ? $basketComparison['symbol'] . number_format($bComp['total_a'], 2) : '—' }}
                                            </p>
                                        </div>
                                        <div class="text-xs font-bold text-neutral-200 dark:text-neutral-700">vs</div>
                                        <div class="text-right">
                                            <p class="text-[10px] font-medium text-accent-500">{{ $cityBName }}</p>
                                            <p class="text-sm font-bold tabular-nums text-accent-700 dark:text-accent-300">
                                                {{ $bComp['total_b'] > 0 ? $basketComparison['symbol'] . number_format($bComp['total_b'], 2) : '—' }}
                                            </p>
                                        </div>
                                        @if ($bComp['delta'] !== null)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold
                                                         {{ $bComp['delta'] > 0
                                                             ? 'bg-error-50 dark:bg-error-900/20 text-error-700 dark:text-error-400'
                                                             : 'bg-success-50 dark:bg-success-900/20 text-success-700 dark:text-success-400' }}">
                                                {{ ($bComp['delta'] > 0 ? '+' : '') . number_format($bComp['delta'], 1) }}%
                                            </span>
                                        @endif
                                    </div>
                                @else
                                    <div wire:loading wire:target="compute"
                                         class="hidden sm:flex items-center gap-4 shrink-0">
                                        <div class="space-y-1">
                                            <div class="h-2 w-10 rounded-full animate-pulse bg-primary-100 dark:bg-primary-900/30"></div>
                                            <div class="h-3 w-14 rounded-full animate-pulse bg-primary-100 dark:bg-primary-900/30"></div>
                                        </div>
                                        <div class="space-y-1">
                                            <div class="h-2 w-10 rounded-full animate-pulse bg-accent-100 dark:bg-accent-900/30"></div>
                                            <div class="h-3 w-14 rounded-full animate-pulse bg-accent-100 dark:bg-accent-900/30"></div>
                                        </div>
                                    </div>
                                @endif

                                <div class="flex items-center gap-1 shrink-0">
                                    <button @click="editing = true"
                                            class="p-1.5 rounded-lg transition text-neutral-400 dark:text-neutral-500
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
                                            class="p-1.5 rounded-lg transition text-neutral-400 dark:text-neutral-500
                                                   hover:text-error-500 dark:hover:text-error-400
                                                   hover:bg-error-50 dark:hover:bg-error-900/20">
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                    <button wire:click="toggleBasket({{ $basket->id }})"
                                            class="p-1.5 rounded-lg transition text-neutral-400 dark:text-neutral-500
                                                   hover:text-neutral-700 dark:hover:text-neutral-200
                                                   hover:bg-neutral-100 dark:hover:bg-white/[0.06]">
                                        <svg class="h-3.5 w-3.5 transition-transform duration-200 {{ $isOpen ? 'rotate-180' : '' }}"
                                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            {{-- Inline edit mode --}}
                            <div x-show="editing" x-cloak class="space-y-3">
                                <div class="flex flex-wrap items-center gap-2">
                                    <template x-for="c in colors" :key="c">
                                        <button type="button"
                                                @click="editColor = c; $el.closest('[style*=border-top]').style.borderTopColor = c"
                                                :style="'background-color:' + c"
                                                :class="editColor === c ? 'ring-2 ring-offset-2 ring-offset-white dark:ring-offset-[#12151f] scale-110' : 'opacity-60 hover:opacity-100 hover:scale-105'"
                                                class="w-6 h-6 rounded-full transition-all focus:outline-none shrink-0">
                                        </button>
                                    </template>
                                </div>
                                <div class="flex items-center gap-2">
                                    <input type="text" x-ref="editInput" x-model="editName" maxlength="80"
                                           @keydown.enter.prevent="save()" @keydown.escape.prevent="cancel()"
                                           class="flex-1 text-sm rounded-lg transition
                                                  border-neutral-300 dark:border-white/[0.1]
                                                  bg-neutral-50 dark:bg-white/[0.05]
                                                  text-neutral-800 dark:text-neutral-100
                                                  focus:ring-2 focus:ring-primary-500/30 focus:border-primary-500
                                                  focus:bg-white dark:focus:bg-white/[0.08]"/>
                                    <button @click="save()" :disabled="!editName.trim()"
                                            class="shrink-0 px-3 py-2 rounded-lg text-sm font-semibold transition-all
                                                   bg-primary-500 hover:bg-primary-600 text-white
                                                   disabled:opacity-40 disabled:cursor-not-allowed">
                                        {{ __('Save') }}
                                    </button>
                                    <button @click="cancel()"
                                            class="shrink-0 p-2 rounded-lg transition text-neutral-400 dark:text-neutral-500
                                                   hover:text-neutral-700 dark:hover:text-neutral-200
                                                   hover:bg-neutral-100 dark:hover:bg-white/[0.06]">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        {{-- Expanded content --}}
                        @if ($isOpen)
                            <div class="border-t border-black/[0.06] dark:border-white/[0.06]">

                                {{-- Mobile price totals --}}
                                @if ($bComp && ($bComp['total_a'] > 0 || $bComp['total_b'] > 0))
                                    <div class="sm:hidden flex items-center justify-around px-5 py-3
                                                border-b border-black/[0.04] dark:border-white/[0.04]
                                                bg-neutral-50/60 dark:bg-white/[0.02]">
                                        <div class="text-center">
                                            <p class="text-[10px] font-semibold text-primary-500 mb-0.5">{{ $cityAName }}</p>
                                            <p class="text-sm font-bold text-primary-700 dark:text-primary-300 tabular-nums">
                                                {{ $bComp['total_a'] > 0 ? $basketComparison['symbol'] . number_format($bComp['total_a'], 2) : '—' }}
                                            </p>
                                        </div>
                                        @if ($bComp['delta'] !== null)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold
                                                         {{ $bComp['delta'] > 0 ? 'bg-error-50 dark:bg-error-900/20 text-error-700 dark:text-error-400' : 'bg-success-50 dark:bg-success-900/20 text-success-700 dark:text-success-400' }}">
                                                {{ ($bComp['delta'] > 0 ? '+' : '') . number_format($bComp['delta'], 1) }}%
                                            </span>
                                        @endif
                                        <div class="text-center">
                                            <p class="text-[10px] font-semibold text-accent-500 mb-0.5">{{ $cityBName }}</p>
                                            <p class="text-sm font-bold text-accent-700 dark:text-accent-300 tabular-nums">
                                                {{ $bComp['total_b'] > 0 ? $basketComparison['symbol'] . number_format($bComp['total_b'], 2) : '—' }}
                                            </p>
                                        </div>
                                    </div>
                                @endif

                                {{-- Breakdown loading skeleton --}}
                                <div wire:loading wire:target="compute" class="px-5 py-4 space-y-2.5">
                                    @foreach ([70, 55, 80, 45] as $w)
                                        <div class="flex items-center gap-3">
                                            <div class="h-2.5 w-2.5 rounded-full bg-neutral-200 dark:bg-white/[0.07] shrink-0"></div>
                                            <div class="h-3 rounded-full animate-pulse bg-neutral-200 dark:bg-white/[0.07]" style="width:{{ $w }}%"></div>
                                            <div class="ml-auto flex gap-3">
                                                <div class="h-3 w-12 rounded-full animate-pulse bg-primary-100 dark:bg-primary-900/30"></div>
                                                <div class="h-3 w-12 rounded-full animate-pulse bg-accent-100 dark:bg-accent-900/30"></div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                {{-- Breakdown content --}}
                                <div wire:loading.remove wire:target="compute">
                                @if ($bComp && !empty($bComp['breakdown']))
                                    <div class="hidden sm:grid grid-cols-[1fr_90px_90px_90px_90px_32px] gap-x-3
                                                px-5 py-2 border-b border-black/[0.04] dark:border-white/[0.04]
                                                bg-neutral-50/60 dark:bg-white/[0.02]">
                                        <span class="text-[10px] font-bold uppercase tracking-widest text-neutral-400 dark:text-neutral-600">{{ __('Product') }}</span>
                                        <span class="text-[10px] font-bold uppercase tracking-widest text-right text-primary-400">{{ $cityAName }}</span>
                                        <span class="text-[10px] font-bold uppercase tracking-widest text-right text-accent-400">{{ $cityBName }}</span>
                                        <span class="text-[10px] font-bold uppercase tracking-widest text-right text-primary-400">{{ __('Sub A') }}</span>
                                        <span class="text-[10px] font-bold uppercase tracking-widest text-right text-accent-400">{{ __('Sub B') }}</span>
                                        <span></span>
                                    </div>
                                    <ul class="divide-y divide-black/[0.04] dark:divide-white/[0.04]">
                                        @foreach ($bComp['breakdown'] as $row)
                                            <li class="hidden sm:grid grid-cols-[1fr_90px_90px_90px_90px_32px] gap-x-3
                                                       items-center px-5 py-2.5 group">
                                                <div class="flex items-center gap-2.5 min-w-0">
                                                    <span class="w-2 h-2 rounded-full shrink-0" style="background-color: {{ $row['category_color'] }}"></span>
                                                    <span class="text-sm text-neutral-800 dark:text-neutral-100 truncate">{{ $row['product']->name }}</span>
                                                    <span class="text-xs text-neutral-400 shrink-0">
                                                        ×{{ rtrim(rtrim(number_format($row['quantity'], 2), '0'), '.') }}@if ($row['unit']){{ $row['unit'] }}@endif
                                                    </span>
                                                </div>
                                                <span class="text-xs tabular-nums text-right {{ $row['price_a'] !== null ? 'text-primary-600 dark:text-primary-400' : 'text-neutral-300 dark:text-neutral-700' }}">
                                                    {{ $row['price_a'] !== null ? $basketComparison['symbol'] . number_format($row['price_a'], 2) : '—' }}
                                                </span>
                                                <span class="text-xs tabular-nums text-right {{ $row['price_b'] !== null ? 'text-accent-600 dark:text-accent-400' : 'text-neutral-300 dark:text-neutral-700' }}">
                                                    {{ $row['price_b'] !== null ? $basketComparison['symbol'] . number_format($row['price_b'], 2) : '—' }}
                                                </span>
                                                <span class="text-xs font-semibold tabular-nums text-right {{ $row['subtotal_a'] !== null ? 'text-primary-700 dark:text-primary-300' : 'text-neutral-300 dark:text-neutral-700' }}">
                                                    {{ $row['subtotal_a'] !== null ? $basketComparison['symbol'] . number_format($row['subtotal_a'], 2) : '—' }}
                                                </span>
                                                <span class="text-xs font-semibold tabular-nums text-right {{ $row['subtotal_b'] !== null ? 'text-accent-700 dark:text-accent-300' : 'text-neutral-300 dark:text-neutral-700' }}">
                                                    {{ $row['subtotal_b'] !== null ? $basketComparison['symbol'] . number_format($row['subtotal_b'], 2) : '—' }}
                                                </span>
                                                <button wire:click="removeItemFromBasket({{ $basket->id }}, {{ $row['product']->id }})"
                                                        class="p-1 rounded-md transition text-neutral-300 dark:text-neutral-600
                                                               hover:text-error-500 dark:hover:text-error-400
                                                               opacity-0 group-hover:opacity-100 focus-visible:opacity-100 focus-visible:outline-none">
                                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                    </svg>
                                                </button>
                                            </li>
                                            <li class="flex sm:hidden flex-col px-5 py-3 gap-1.5 group">
                                                <div class="flex items-center gap-2 min-w-0">
                                                    <span class="w-2 h-2 rounded-full shrink-0" style="background-color: {{ $row['category_color'] }}"></span>
                                                    <span class="flex-1 text-sm font-medium text-neutral-800 dark:text-neutral-100 truncate">{{ $row['product']->name }}</span>
                                                    <span class="text-xs text-neutral-400 shrink-0">×{{ rtrim(rtrim(number_format($row['quantity'], 2), '0'), '.') }}@if ($row['unit']){{ $row['unit'] }}@endif</span>
                                                    <button wire:click="removeItemFromBasket({{ $basket->id }}, {{ $row['product']->id }})"
                                                            class="shrink-0 p-1 rounded-md transition text-neutral-300 dark:text-neutral-600
                                                                   hover:text-error-500 dark:hover:text-error-400
                                                                   opacity-0 group-hover:opacity-100 focus-visible:opacity-100 focus-visible:outline-none">
                                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                        </svg>
                                                    </button>
                                                </div>
                                                <div class="flex items-center gap-3 text-xs pl-4">
                                                    <span class="flex items-center gap-1 text-primary-600 dark:text-primary-400">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-primary-500"></span>
                                                        {{ $row['subtotal_a'] !== null ? $basketComparison['symbol'] . number_format($row['subtotal_a'], 2) : '—' }}
                                                    </span>
                                                    <span class="text-neutral-300 dark:text-neutral-700">·</span>
                                                    <span class="flex items-center gap-1 text-accent-600 dark:text-accent-400">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-accent-500"></span>
                                                        {{ $row['subtotal_b'] !== null ? $basketComparison['symbol'] . number_format($row['subtotal_b'], 2) : '—' }}
                                                    </span>
                                                </div>
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    <div class="px-5 py-6 text-center">
                                        <p class="text-sm text-neutral-400 dark:text-neutral-500">{{ __('No items yet, add some below.') }}</p>
                                    </div>
                                @endif
                                </div>{{-- /wire:loading.remove --}}

                                {{-- Add item form --}}
                                {{--
                                    categories is read from window.__categories (set once on page load).
                                    On AJAX re-renders the @push script is not re-sent, so the global
                                    persists and is re-used here.
                                --}}
                                <div class="px-5 pt-4 pb-5 border-t border-black/[0.06] dark:border-white/[0.06]
                                            bg-neutral-50/60 dark:bg-white/[0.02] rounded-b-2xl"
                                     wire:key="add-item-{{ $basket->id }}-{{ $basketItemFormKey }}"
                                     x-data="{
                                         open: false, search: '', activeIndex: -1,
                                         qty: 1, selectedName: '', selectedUnit: '', selectedId: null,
                                         basketProductIds: {{ Js::from($basket->items->pluck('product_id')->all()) }},
                                         cityProductIds: {{ Js::from($cityProductIds) }},
                                         cityOnly: false,
                                         categories: window.__categories ?? [],
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
                                         get flatProducts() { return this.filteredCategories.flatMap(c => c.products); },
                                         selectProduct(p) {
                                             this.selectedName = this.getName(p.name);
                                             this.selectedUnit = p.unit ? p.unit.symbol : '';
                                             this.selectedId = p.id;
                                             this.search = ''; this.open = false; this.activeIndex = -1;
                                         },
                                         clearProduct() { this.selectedName = ''; this.selectedUnit = ''; this.selectedId = null; this.qty = 1; },
                                         async addToBasket() {
                                             if (!this.selectedId) return;
                                             await $wire.call('addItemToBasket', {{ $basket->id }}, this.selectedId, Math.max(0.01, parseFloat(this.qty) || 1));
                                             this.clearProduct();
                                         }
                                     }"
                                     @click.outside="open = false; activeIndex = -1">

                                    <div class="flex items-center justify-between mb-3">
                                        <p class="text-xs font-semibold uppercase tracking-widest text-neutral-400 dark:text-neutral-500">{{ __('Add item') }}</p>
                                        @if (auth()->user()->city)
                                            <div class="flex items-center p-0.5 gap-0.5 rounded-lg bg-neutral-100 dark:bg-white/[0.06]">
                                                <button type="button" @click="cityOnly = false"
                                                        :class="!cityOnly ? 'bg-white dark:bg-white/[0.12] shadow-sm text-neutral-700 dark:text-white' : 'text-neutral-400 dark:text-neutral-500'"
                                                        class="px-2.5 py-1 rounded-md text-xs font-medium transition-all">
                                                    {{ __('All products') }}
                                                </button>
                                                <button type="button" @click="cityOnly = true"
                                                        :class="cityOnly ? 'bg-white dark:bg-white/[0.12] shadow-sm text-neutral-700 dark:text-white' : 'text-neutral-400 dark:text-neutral-500'"
                                                        class="px-2.5 py-1 rounded-md text-xs font-medium transition-all">
                                                    {{ __('With local data') }}
                                                </button>
                                            </div>
                                        @endif
                                    </div>

                                    <div class="relative mb-3">
                                        <div x-show="selectedName"
                                             class="flex items-center gap-2 w-full pl-3 pr-2 py-2.5 rounded-lg text-sm
                                                    border border-primary-300 dark:border-primary-700/60
                                                    bg-primary-50 dark:bg-primary-900/20">
                                            <span x-text="selectedName" class="flex-1 truncate font-medium text-primary-700 dark:text-primary-300"></span>
                                            <span x-show="selectedUnit" x-text="selectedUnit"
                                                  class="text-xs shrink-0 text-primary-400 dark:text-primary-500 pl-2 border-l border-primary-200 dark:border-primary-700/40"></span>
                                            <button type="button" @click="clearProduct()"
                                                    class="shrink-0 ml-1 p-0.5 rounded text-primary-400 hover:text-primary-700 transition">
                                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                </svg>
                                            </button>
                                        </div>

                                        <div x-show="!selectedName" class="relative">
                                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-neutral-400 pointer-events-none"
                                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                            </svg>
                                            <input type="text"
                                                   x-model="search"
                                                   @focus="open = true"
                                                   @input="open = true; activeIndex = -1"
                                                   @keydown.down.prevent="open = true; activeIndex = Math.min(activeIndex + 1, flatProducts.length - 1)"
                                                   @keydown.up.prevent="activeIndex = Math.max(0, activeIndex - 1)"
                                                   @keydown.enter.prevent="if (activeIndex >= 0 && flatProducts[activeIndex]) selectProduct(flatProducts[activeIndex])"
                                                   @keydown.escape.stop="open = false; activeIndex = -1"
                                                   placeholder="{{ __('Search products…') }}"
                                                   class="w-full pl-9 py-2.5 text-sm rounded-lg transition
                                                          border-neutral-300 dark:border-white/[0.1]
                                                          bg-neutral-50 dark:bg-white/[0.05]
                                                          text-neutral-800 dark:text-neutral-100
                                                          placeholder-neutral-400 dark:placeholder-neutral-500
                                                          focus:ring-2 focus:ring-primary-500/30
                                                          focus:border-primary-500
                                                          focus:bg-white dark:focus:bg-white/[0.08]"/>
                                        </div>

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
                                                    <div class="px-3 pt-2.5 pb-1 text-[10px] font-bold uppercase tracking-widest"
                                                         :style="'color: ' + (category.color ?? '#9ca3af')">
                                                        <span x-text="getName(category.name)"></span>
                                                    </div>
                                                    <template x-for="p in category.products" :key="p.id">
                                                        <div @click="selectProduct(p)"
                                                             :class="activeIndex >= 0 && flatProducts[activeIndex]?.id === p.id
                                                                 ? 'bg-primary-50 dark:bg-primary-900/30'
                                                                 : 'hover:bg-neutral-100 dark:hover:bg-white/[0.06]'"
                                                             class="px-3 py-2 text-sm cursor-pointer transition flex items-center justify-between">
                                                            <span x-text="getName(p.name)" class="text-neutral-800 dark:text-neutral-200"></span>
                                                            <div class="flex items-center gap-2 shrink-0 ml-2">
                                                                <template x-if="cityProductIds.includes(p.id)">
                                                                    <span class="w-1.5 h-1.5 rounded-full bg-primary-400"
                                                                          title="{{ __('Has price data in your city') }}"></span>
                                                                </template>
                                                                <template x-if="p.unit">
                                                                    <span class="text-xs text-neutral-400 dark:text-neutral-500" x-text="p.unit.symbol"></span>
                                                                </template>
                                                            </div>
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

                                    <div class="flex items-center gap-3">
                                        <div class="flex-1 flex items-center gap-2 min-w-0">
                                            <input type="range" x-model.number="qty" min="0.1" max="10" step="0.1"
                                                   class="flex-1 min-w-0 h-1.5 rounded-full appearance-none cursor-pointer accent-primary-500"
                                                   :disabled="!selectedId"/>
                                            <input type="number" x-model.number="qty" min="0.01" step="0.01"
                                                   class="w-16 text-sm text-center rounded-lg transition
                                                          border-neutral-300 dark:border-white/[0.1]
                                                          bg-neutral-50 dark:bg-white/[0.05]
                                                          text-neutral-800 dark:text-neutral-100
                                                          focus:ring-2 focus:ring-primary-500/30 focus:border-primary-500
                                                          disabled:opacity-40 disabled:cursor-not-allowed"
                                                   :disabled="!selectedId"/>
                                            <span x-show="selectedUnit" x-text="selectedUnit"
                                                  class="text-sm font-bold shrink-0 text-neutral-600 dark:text-neutral-300 min-w-[1.5rem]"></span>
                                        </div>
                                        <button type="button"
                                                @click="addToBasket()"
                                                :disabled="!selectedId"
                                                class="shrink-0 inline-flex items-center gap-1.5 px-3 py-2.5 rounded-lg
                                                       text-sm font-semibold transition-all
                                                       bg-primary-500 hover:bg-primary-600 text-white shadow-sm
                                                       disabled:opacity-40 disabled:cursor-not-allowed">
                                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                                            </svg>
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

    @endif{{-- /cityAId && cityBId --}}

</div>
