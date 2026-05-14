<div>
    @if ($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4"
             x-data @keydown.escape.window="$wire.set('showModal', false)">

            <div class="absolute inset-0 bg-black/60 backdrop-blur-[2px]"
                 wire:click="$set('showModal', false)"></div>

            <div class="relative z-10 w-full max-w-2xl bg-surface-raised rounded-2xl shadow-card-md
                        border border-neutral-200 dark:border-white/[0.08] max-h-[90vh] flex flex-col">

                <div class="px-6 py-4 border-b border-neutral-100 dark:border-white/[0.06]
                            flex items-center justify-between flex-shrink-0">
                    <h2 class="text-sm font-semibold tracking-tight text-neutral-900 dark:text-neutral-100">
                        {{ $editingId ? __('Edit product') : __('New product') }}
                    </h2>
                    <button wire:click="$set('showModal', false)"
                            class="p-1.5 rounded-lg text-neutral-400 hover:text-neutral-600 dark:hover:text-neutral-200
                                   hover:bg-neutral-100 dark:hover:bg-white/[0.06] transition
                                   focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="px-6 py-5 overflow-y-auto flex-1 space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label :value="__('Name (EN)')"/>
                            <x-text-input wire:model="form.name_en" class="mt-1 w-full text-sm"/>
                            <x-input-error :messages="$errors->get('form.name_en')" class="mt-1"/>
                        </div>
                        <div>
                            <x-input-label :value="__('Name (FR)')"/>
                            <x-text-input wire:model="form.name_fr" class="mt-1 w-full text-sm"/>
                            <x-input-error :messages="$errors->get('form.name_fr')" class="mt-1"/>
                        </div>
                        <div>
                            <x-input-label :value="__('Description (EN)')"/>
                            <textarea wire:model="form.desc_en" rows="3"
                                      class="mt-1 w-full text-sm rounded-xl border-neutral-300
                                             dark:border-white/[0.1] bg-neutral-50 dark:bg-white/[0.04]
                                             text-neutral-800 dark:text-neutral-100
                                             focus:ring-2 focus:ring-primary-500/30 focus:border-primary-500
                                             focus:bg-white dark:focus:bg-white/[0.07] transition resize-none"></textarea>
                        </div>
                        <div>
                            <x-input-label :value="__('Description (FR)')"/>
                            <textarea wire:model="form.desc_fr" rows="3"
                                      class="mt-1 w-full text-sm rounded-xl border-neutral-300
                                             dark:border-white/[0.1] bg-neutral-50 dark:bg-white/[0.04]
                                             text-neutral-800 dark:text-neutral-100
                                             focus:ring-2 focus:ring-primary-500/30 focus:border-primary-500
                                             focus:bg-white dark:focus:bg-white/[0.07] transition resize-none"></textarea>
                        </div>
                        <div>
                            <x-input-label :value="__('Category')"/>
                            <select wire:model="form.category_id"
                                    class="mt-1 w-full text-sm rounded-xl border-neutral-300
                                           dark:border-white/[0.1] bg-neutral-50 dark:bg-white/[0.04]
                                           text-neutral-800 dark:text-neutral-100
                                           focus:ring-2 focus:ring-primary-500/30 focus:border-primary-500 transition">
                                <option value="">{{ __('Select a category') }}</option>
                                @foreach ($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->translate('name', 'en') }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('form.category_id')" class="mt-1"/>
                        </div>
                        <div>
                            <x-input-label :value="__('Unit (optional)')"/>
                            <select wire:model="form.unit_id"
                                    class="mt-1 w-full text-sm rounded-xl border-neutral-300
                                           dark:border-white/[0.1] bg-neutral-50 dark:bg-white/[0.04]
                                           text-neutral-800 dark:text-neutral-100
                                           focus:ring-2 focus:ring-primary-500/30 focus:border-primary-500 transition">
                                <option value="">{{ __('No unit') }}</option>
                                @foreach ($units as $unit)
                                    <option value="{{ $unit->id }}">
                                        {{ $unit->translate('name', 'en') }} ({{ $unit->symbol }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-4 border-t border-neutral-100 dark:border-white/[0.06]
                            flex items-center justify-end gap-3 flex-shrink-0">
                    <button wire:click="$set('showModal', false)"
                            class="px-4 py-2 text-sm font-medium text-neutral-600 dark:text-neutral-400
                                   hover:text-neutral-900 dark:hover:text-neutral-200
                                   hover:bg-neutral-100 dark:hover:bg-white/[0.05] rounded-lg transition
                                   focus-visible:outline-none">
                        {{ __('Cancel') }}
                    </button>
                    <button wire:click="save"
                            wire:loading.attr="disabled"
                            wire:loading.class="opacity-60 cursor-not-allowed"
                            class="px-5 py-2 bg-primary-600 hover:bg-primary-700 active:bg-primary-800
                                   text-white text-sm font-semibold rounded-xl transition
                                   focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500
                                   disabled:opacity-60 disabled:cursor-not-allowed">
                        {{ __('Save') }}
                    </button>
                </div>
            </div>
        </div>
    @endif

    <x-admin.panel :title="__('Products')" :subtitle="__('Manage the product catalogue')">

        <x-slot name="action">
            <div class="flex flex-wrap items-center gap-2">
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-neutral-400 pointer-events-none"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input wire:model.live.debounce.200ms="search" type="text"
                           placeholder="{{ __('Search…') }}"
                           class="pl-8 text-sm rounded-xl border-neutral-300 dark:border-white/[0.1]
                                  bg-neutral-50 dark:bg-white/[0.04] text-neutral-800 dark:text-neutral-100
                                  focus:ring-2 focus:ring-primary-500/30 focus:border-primary-500
                                  focus:bg-white dark:focus:bg-white/[0.07] transition w-36"/>
                </div>

                <select wire:model.live="filterCategory"
                        class="text-sm rounded-xl border-neutral-300 dark:border-white/[0.1]
                               bg-neutral-50 dark:bg-white/[0.04] text-neutral-800 dark:text-neutral-100
                               focus:ring-2 focus:ring-primary-500/30 focus:border-primary-500 transition py-2">
                    <option value="">{{ __('All categories') }}</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->translate('name', 'en') }}</option>
                    @endforeach
                </select>

                <select wire:model.live="filterUnit"
                        class="text-sm rounded-xl border-neutral-300 dark:border-white/[0.1]
                               bg-neutral-50 dark:bg-white/[0.04] text-neutral-800 dark:text-neutral-100
                               focus:ring-2 focus:ring-primary-500/30 focus:border-primary-500 transition py-2">
                    <option value="">{{ __('All units') }}</option>
                    <option value="none">{{ __('No unit') }}</option>
                    @foreach ($units as $unit)
                        <option value="{{ $unit->id }}">{{ $unit->translate('name', 'en') }} ({{ $unit->symbol }})</option>
                    @endforeach
                </select>

                <button wire:click="openCreate"
                        class="inline-flex items-center gap-1.5 px-4 py-2 bg-primary-600 hover:bg-primary-700
                               active:bg-primary-800 text-white text-sm font-semibold rounded-xl transition
                               focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                    </svg>
                    {{ __('New product') }}
                </button>
            </div>
        </x-slot>

        <div class="overflow-x-auto transition-opacity duration-200" wire:loading.class="opacity-40">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-neutral-100 dark:border-white/[0.05]">
                        @php
                            $cols = [
                                ['key' => 'name',      'label' => __('Product')],
                                ['key' => 'category',  'label' => __('Category')],
                                ['key' => 'unit',      'label' => __('Unit')],
                                ['key' => 'estimates', 'label' => __('Estimates')],
                            ];
                        @endphp
                        @foreach ($cols as $col)
                            <th class="text-left px-6 py-3">
                                <button wire:click="changeSort('{{ $col['key'] }}')"
                                        class="flex items-center gap-1 text-[10px] font-bold uppercase
                                               tracking-widest text-neutral-500 dark:text-neutral-400
                                               hover:text-neutral-800 dark:hover:text-neutral-200
                                               focus-visible:outline-none transition">
                                    {{ $col['label'] }}
                                    @if ($sortBy === $col['key'])
                                        <span class="text-primary-500 text-xs">{{ $sortDir === 'asc' ? '↑' : '↓' }}</span>
                                    @else
                                        <span class="opacity-20 text-xs">↕</span>
                                    @endif
                                </button>
                            </th>
                        @endforeach
                        <th class="text-left px-6 py-3 text-[10px] font-bold uppercase tracking-widest
                                   text-neutral-500 dark:text-neutral-400">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-50 dark:divide-white/[0.03]">
                    @forelse ($products as $product)
                        <tr class="hover:bg-neutral-50 dark:hover:bg-white/[0.03] transition-colors group">
                            <td class="px-6 py-3.5">
                                <p class="font-medium text-neutral-800 dark:text-neutral-100">
                                    {{ $product->translate('name', 'en') }}
                                </p>
                                <p class="text-xs text-neutral-400 dark:text-neutral-500 mt-0.5">
                                    {{ $product->translate('name', 'fr') }}
                                </p>
                            </td>
                            <td class="px-6 py-3.5">
                                <div class="flex items-center gap-2">
                                    <span class="w-2.5 h-2.5 rounded-full flex-shrink-0"
                                          style="background-color: {{ $product->category->color }}"></span>
                                    <span class="text-neutral-600 dark:text-neutral-300">
                                        {{ $product->category->translate('name', 'en') }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-3.5">
                                @if ($product->unit)
                                    <span class="font-mono text-xs px-2 py-0.5 rounded-md
                                                 bg-neutral-100 dark:bg-white/[0.06]
                                                 text-neutral-600 dark:text-neutral-300">
                                        {{ $product->unit->symbol }}
                                    </span>
                                @else
                                    <span class="text-neutral-300 dark:text-neutral-600">—</span>
                                @endif
                            </td>
                            <td class="px-6 py-3.5">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs
                                             bg-neutral-100 dark:bg-white/[0.06]
                                             text-neutral-600 dark:text-neutral-300 tabular-nums">
                                    {{ $product->price_estimates_count }}
                                </span>
                            </td>
                            <td class="px-6 py-3.5">
                                <div class="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition">
                                    <a href="{{ route('products.show', $product) }}"
                                       class="text-xs font-medium text-neutral-500 dark:text-neutral-400
                                              px-2 py-1 rounded-md hover:bg-neutral-100 dark:hover:bg-white/[0.06]
                                              transition focus-visible:outline-none">
                                        {{ __('View') }}
                                    </a>
                                    <button wire:click="openEdit({{ $product->id }})"
                                            class="text-xs font-medium text-primary-600 dark:text-primary-400
                                                   px-2 py-1 rounded-md hover:bg-primary-50 dark:hover:bg-primary-900/20
                                                   transition focus-visible:outline-none">
                                        {{ __('Edit') }}
                                    </button>
                                    <button wire:click="delete({{ $product->id }})"
                                            wire:confirm="{{ __('Delete this product and all its estimates?') }}"
                                            class="text-xs font-medium text-error-600 dark:text-error-400
                                                   px-2 py-1 rounded-md hover:bg-error-50 dark:hover:bg-error-900/20
                                                   transition focus-visible:outline-none">
                                        {{ __('Delete') }}
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5"
                                class="px-6 py-14 text-center text-sm text-neutral-400 dark:text-neutral-500">
                                {{ __('No products found') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-6 py-4 border-t border-neutral-100 dark:border-white/[0.05]">
            {{ $products->links() }}
        </div>

    </x-admin.panel>
</div>
