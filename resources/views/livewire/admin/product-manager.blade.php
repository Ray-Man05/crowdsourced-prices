<div>
    {{-- Modal overlay --}}
    @if ($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4"
             x-data
             @keydown.escape.window="$wire.set('showModal', false)">

            {{-- Backdrop --}}
            <div class="absolute inset-0 bg-black/50 dark:bg-black/70"
                 wire:click="$set('showModal', false)"></div>

            {{-- Modal panel --}}
            <div class="relative z-10 w-full max-w-2xl bg-white dark:bg-neutral-800
                        rounded-xl shadow-2xl border border-neutral-200 dark:border-neutral-700
                        max-h-[90vh] overflow-y-auto">

                <div class="px-6 py-4 border-b border-neutral-200 dark:border-neutral-700
                            flex items-center justify-between sticky top-0
                            bg-white dark:bg-neutral-800 z-10">
                    <h2 class="text-sm font-semibold text-neutral-800 dark:text-neutral-100">
                        {{ $editingId ? __('Edit product') : __('New product') }}
                    </h2>
                    <button wire:click="$set('showModal', false)"
                            class="text-neutral-400 hover:text-neutral-600 dark:hover:text-neutral-200
                                   transition p-1 rounded">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="px-6 py-5 space-y-4">
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
                                      class="mt-1 w-full text-sm rounded-lg border-neutral-300
                                             dark:border-neutral-600 bg-white dark:bg-neutral-800
                                             text-neutral-800 dark:text-neutral-100
                                             focus:ring focus:ring-primary-300"></textarea>
                        </div>
                        <div>
                            <x-input-label :value="__('Description (FR)')"/>
                            <textarea wire:model="form.desc_fr" rows="3"
                                      class="mt-1 w-full text-sm rounded-lg border-neutral-300
                                             dark:border-neutral-600 bg-white dark:bg-neutral-800
                                             text-neutral-800 dark:text-neutral-100
                                             focus:ring focus:ring-primary-300"></textarea>
                        </div>
                        <div>
                            <x-input-label :value="__('Category')"/>
                            <select wire:model="form.category_id"
                                    class="mt-1 w-full text-sm rounded-lg border-neutral-300
                                           dark:border-neutral-600 bg-white dark:bg-neutral-800
                                           text-neutral-800 dark:text-neutral-100
                                           focus:ring focus:ring-primary-300">
                                <option value="">{{ __('Select a category') }}</option>
                                @foreach ($categories as $cat)
                                    <option value="{{ $cat->id }}">
                                        {{ $cat->translate('name', 'en') }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('form.category_id')" class="mt-1"/>
                        </div>
                        <div>
                            <x-input-label :value="__('Unit (optional)')"/>
                            <select wire:model="form.unit_id"
                                    class="mt-1 w-full text-sm rounded-lg border-neutral-300
                                           dark:border-neutral-600 bg-white dark:bg-neutral-800
                                           text-neutral-800 dark:text-neutral-100
                                           focus:ring focus:ring-primary-300">
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

                <div class="px-6 py-4 border-t border-neutral-200 dark:border-neutral-700
                            flex items-center justify-end gap-3
                            sticky bottom-0 bg-white dark:bg-neutral-800">
                    <button wire:click="$set('showModal', false)"
                            class="px-4 py-2 text-sm text-neutral-600 dark:text-neutral-400
                                   hover:text-neutral-800 dark:hover:text-neutral-200 transition">
                        {{ __('Cancel') }}
                    </button>
                    <button wire:click="save"
                            class="px-5 py-2 bg-primary-600 hover:bg-primary-700 text-white
                                   text-sm font-semibold rounded-lg transition">
                        {{ __('Save') }}
                    </button>
                </div>
            </div>
        </div>
    @endif

    <x-admin.panel :title="__('Products')" :subtitle="__('Manage the product catalogue')">

        <x-slot name="action">
            <div class="flex flex-wrap items-center gap-3">
                <input wire:model.live.debounce.200ms="search" type="text"
                       placeholder="{{ __('Search...') }}"
                       class="text-sm rounded-lg border-neutral-300 dark:border-neutral-600
                              bg-white dark:bg-neutral-800 text-neutral-800 dark:text-neutral-100
                              focus:ring focus:ring-primary-300 w-40"/>

                <select wire:model.live="filterCategory"
                        class="text-sm rounded-lg border-neutral-300 dark:border-neutral-600
                               bg-white dark:bg-neutral-800 text-neutral-800 dark:text-neutral-100
                               focus:ring focus:ring-primary-300">
                    <option value="">{{ __('All categories') }}</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat->id }}">
                            {{ $cat->translate('name', 'en') }}
                        </option>
                    @endforeach
                </select>

                <select wire:model.live="filterUnit"
                        class="text-sm rounded-lg border-neutral-300 dark:border-neutral-600
                               bg-white dark:bg-neutral-800 text-neutral-800 dark:text-neutral-100
                               focus:ring focus:ring-primary-300">
                    <option value="">{{ __('All units') }}</option>
                    <option value="none">{{ __('No unit') }}</option>
                    @foreach ($units as $unit)
                        <option value="{{ $unit->id }}">
                            {{ $unit->translate('name', 'en') }} ({{ $unit->symbol }})
                        </option>
                    @endforeach
                </select>

                <button wire:click="openCreate"
                        class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white
                               text-sm font-medium rounded-lg transition">
                    + {{ __('New product') }}
                </button>
            </div>
        </x-slot>

        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-neutral-200 dark:border-neutral-700">
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
                                    class="flex items-center gap-1 text-xs font-semibold uppercase
                                        tracking-wide text-neutral-500 dark:text-neutral-400
                                        hover:text-neutral-800 dark:hover:text-neutral-200">
                                {{ $col['label'] }}
                                @if ($sortBy === $col['key'])
                                    <span>{{ $sortDir === 'asc' ? '↑' : '↓' }}</span>
                                @else
                                    <span class="opacity-30">↕</span>
                                @endif
                            </button>
                        </th>
                    @endforeach
                    <th class="text-left px-6 py-3 text-xs font-semibold uppercase
                                        tracking-wide text-neutral-500 dark:text-neutral-400
                                        hover:text-neutral-800 dark:hover:text-neutral-200">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100 dark:divide-neutral-700">
                @forelse ($products as $product)
                    <tr class="hover:bg-neutral-50 dark:hover:bg-neutral-700/30 transition group">
                        <td class="px-6 py-3">
                            <p class="font-medium text-neutral-800 dark:text-neutral-100">
                                {{ $product->translate('name', 'en') }}
                            </p>
                            <p class="text-xs text-neutral-400 dark:text-neutral-500 mt-0.5">
                                {{ $product->translate('name', 'fr') }}
                            </p>
                        </td>
                        <td class="px-6 py-3">
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full flex-shrink-0"
                                      style="background-color: {{ $product->category->color }}"></span>
                                <span class="text-neutral-600 dark:text-neutral-300">
                                    {{ $product->category->translate('name', 'en') }}
                                </span>
                            </div>
                        </td>
                        <td class="px-6 py-3 font-mono text-xs text-neutral-500 dark:text-neutral-400">
                            {{ $product->unit?->symbol ?? '-' }}
                        </td>
                        <td class="px-6 py-3 text-neutral-500 dark:text-neutral-400">
                            {{ $product->price_estimates_count }}
                        </td>
                        <td class="px-6 py-3 text-right">
                            <div class="flex items-center justify-start gap-3 opacity-0
                                        group-hover:opacity-100 transition">
                                <a href="{{ route('products.show', $product) }}"
                                   class="text-xs text-neutral-500 dark:text-neutral-400 hover:underline">
                                    {{ __('View') }}
                                </a>
                                <button wire:click="openEdit({{ $product->id }})"
                                        class="text-xs text-primary-600 dark:text-primary-400
                                               hover:underline">
                                    {{ __('Edit') }}
                                </button>
                                <button wire:click="delete({{ $product->id }})"
                                        wire:confirm="{{ __('Delete this product and all its estimates?') }}"
                                        class="text-xs text-error-500 dark:text-error-400 hover:underline">
                                    {{ __('Delete') }}
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5"
                            class="px-6 py-12 text-center text-sm text-neutral-400 dark:text-neutral-500">
                            {{ __('No products found') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="px-6 py-4 border-t border-neutral-100 dark:border-neutral-700">
            {{ $products->links() }}
        </div>

    </x-admin.panel>
</div>