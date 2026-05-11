<div>
    @if ($showModal)
        <x-admin.modal :title="$editingId ? __('Edit category') : __('New category')"
                       width="max-w-lg">
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
                <div class="sm:col-span-2">
                    <x-input-label :value="__('Color')"/>
                    <div class="flex items-center gap-2 mt-1">
                        <input type="color" wire:model="form.color"
                               class="h-9 w-16 rounded cursor-pointer
                                      border border-neutral-300 dark:border-neutral-600"/>
                        <x-text-input wire:model="form.color"
                                      class="flex-1 text-sm font-mono"/>
                    </div>
                    <x-input-error :messages="$errors->get('form.color')" class="mt-1"/>
                </div>
            </div>
        </x-admin.modal>
    @endif

    <x-admin.panel :title="__('Categories')"
                   :subtitle="__('Manage product categories and their colors')">

        <x-slot name="action">
            <div class="flex items-center gap-3">
                <input wire:model.live.debounce.200ms="search" type="text"
                       placeholder="{{ __('Search...') }}"
                       class="text-sm rounded-lg border-neutral-300 dark:border-neutral-600
                              bg-white dark:bg-neutral-800 text-neutral-800 dark:text-neutral-100
                              focus:ring focus:ring-primary-300 w-48"/>
                <button wire:click="openCreate"
                        class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white
                               text-sm font-medium rounded-lg transition">
                    + {{ __('New category') }}
                </button>
            </div>
        </x-slot>

        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-neutral-200 dark:border-neutral-700">
                    <th class="text-left px-6 py-3 text-xs font-semibold uppercase tracking-wide
                               text-neutral-500 dark:text-neutral-400">{{ __('Category') }}</th>
                    <th class="text-left px-6 py-3 text-xs font-semibold uppercase tracking-wide
                               text-neutral-500 dark:text-neutral-400">{{ __('FR') }}</th>
                    <th class="text-left px-6 py-3 text-xs font-semibold uppercase tracking-wide
                               text-neutral-500 dark:text-neutral-400">{{ __('Color') }}</th>
                    <th class="text-left px-6 py-3 text-xs font-semibold uppercase tracking-wide
                               text-neutral-500 dark:text-neutral-400">{{ __('Products') }}</th>
                    <th class="text-left px-6 py-3 text-xs font-semibold uppercase
                        tracking-wide text-neutral-500 dark:text-neutral-400
                        hover:text-neutral-800 dark:hover:text-neutral-200">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100 dark:divide-neutral-700">
                @forelse ($categories as $category)
                    <tr class="hover:bg-neutral-50 dark:hover:bg-neutral-700/30 transition group">
                        <td class="px-6 py-3 font-medium text-neutral-800 dark:text-neutral-100">
                            <div class="flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full flex-shrink-0"
                                      style="background-color: {{ $category->color }}"></span>
                                {{ $category->translate('name', 'en') }}
                            </div>
                        </td>
                        <td class="px-6 py-3 text-neutral-600 dark:text-neutral-300">
                            {{ $category->translate('name', 'fr') }}
                        </td>
                        <td class="px-6 py-3 font-mono text-xs text-neutral-500 dark:text-neutral-400">
                            {{ $category->color }}
                        </td>
                        <td class="px-6 py-3 text-neutral-500 dark:text-neutral-400">
                            {{ $category->products_count }}
                        </td>
                        <td class="px-6 py-3 text-right">
                            <div class="flex items-center justify-start gap-3 opacity-0
                                        group-hover:opacity-100 transition">
                                <button wire:click="openEdit({{ $category->id }})"
                                        class="text-xs text-primary-600 dark:text-primary-400
                                               hover:underline">
                                    {{ __('Edit') }}
                                </button>
                                <button wire:click="delete({{ $category->id }})"
                                        wire:confirm="{{ __('Delete this category?') }}"
                                        class="text-xs text-error-500 dark:text-error-400
                                               hover:underline">
                                    {{ __('Delete') }}
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5"
                            class="px-6 py-12 text-center text-sm text-neutral-400 dark:text-neutral-500">
                            {{ __('No categories found') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="px-6 py-4 border-t border-neutral-100 dark:border-neutral-700">
            {{ $categories->links() }}
        </div>

    </x-admin.panel>
</div>