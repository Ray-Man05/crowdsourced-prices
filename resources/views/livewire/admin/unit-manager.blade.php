<div>
    @if ($showModal)
        <x-admin.modal :title="$editingId ? __('Edit unit') : __('New unit')" width="max-w-lg">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
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
                    <x-input-label :value="__('Symbol')"/>
                    <x-text-input wire:model="form.symbol"
                                  placeholder="kg, L, pc…"
                                  class="mt-1 w-full text-sm font-mono"/>
                    <x-input-error :messages="$errors->get('form.symbol')" class="mt-1"/>
                </div>
            </div>
        </x-admin.modal>
    @endif

    <x-admin.panel :title="__('Units of measure')"
                   :subtitle="__('Manage units used across products')">

        <x-slot name="action">
            <div class="flex items-center gap-3">
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
                                  focus:bg-white dark:focus:bg-white/[0.07] transition w-44"/>
                </div>
                <button wire:click="openCreate"
                        class="inline-flex items-center gap-1.5 px-4 py-2 bg-primary-600 hover:bg-primary-700
                               active:bg-primary-800 text-white text-sm font-semibold rounded-xl transition
                               focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                    </svg>
                    {{ __('New unit') }}
                </button>
            </div>
        </x-slot>

        <div class="overflow-x-auto transition-opacity duration-200" wire:loading.class="opacity-40">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-neutral-100 dark:border-white/[0.05]">
                        @foreach ([__('Name (EN)'), __('Name (FR)'), __('Symbol'), __('Products'), __('Actions')] as $h)
                            <th class="text-left px-6 py-3 text-[10px] font-bold uppercase tracking-widest
                                       text-neutral-500 dark:text-neutral-400">{{ $h }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-50 dark:divide-white/[0.03]">
                    @forelse ($units as $unit)
                        <tr class="hover:bg-neutral-50 dark:hover:bg-white/[0.03] transition-colors group">
                            <td class="px-6 py-3.5 font-medium text-neutral-800 dark:text-neutral-100">
                                {{ $unit->translate('name', 'en') }}
                            </td>
                            <td class="px-6 py-3.5 text-neutral-500 dark:text-neutral-400">
                                {{ $unit->translate('name', 'fr') }}
                            </td>
                            <td class="px-6 py-3.5">
                                <span class="font-mono text-xs px-2 py-0.5 rounded-md
                                             bg-neutral-100 dark:bg-white/[0.06]
                                             text-neutral-600 dark:text-neutral-300">
                                    {{ $unit->symbol }}
                                </span>
                            </td>
                            <td class="px-6 py-3.5">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs
                                             bg-neutral-100 dark:bg-white/[0.06]
                                             text-neutral-600 dark:text-neutral-300">
                                    {{ $unit->products_count }}
                                </span>
                            </td>
                            <td class="px-6 py-3.5">
                                <div class="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition">
                                    <button wire:click="openEdit({{ $unit->id }})"
                                            class="text-xs font-medium text-primary-600 dark:text-primary-400
                                                   px-2 py-1 rounded-md hover:bg-primary-50 dark:hover:bg-primary-900/20
                                                   transition focus-visible:outline-none">
                                        {{ __('Edit') }}
                                    </button>
                                    <button wire:click="delete({{ $unit->id }})"
                                            wire:confirm="{{ __('Delete this unit?') }}"
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
                                {{ __('No units found') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-6 py-4 border-t border-neutral-100 dark:border-white/[0.05]">
            {{ $units->links() }}
        </div>

    </x-admin.panel>
</div>
