@props(['title'])

<div class="fixed inset-0 z-50 flex items-center justify-center p-4">

    {{-- Backdrop --}}
    <div class="absolute inset-0 bg-black/50 dark:bg-black/70"
         wire:click="$set('showModal', false)"></div>

    {{-- Panel --}}
    <div class="relative z-10 w-full {{ $attributes->get('width', 'max-w-2xl') }}
                bg-white dark:bg-neutral-800 rounded-xl shadow-2xl
                border border-neutral-200 dark:border-neutral-700
                max-h-[90vh] flex flex-col">

        {{-- Sticky header --}}
        <div class="px-6 py-4 border-b border-neutral-200 dark:border-neutral-700
                    flex items-center justify-between flex-shrink-0">
            <h2 class="text-sm font-semibold text-neutral-800 dark:text-neutral-100">
                {{ $title }}
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

        {{-- Scrollable body --}}
        <div class="px-6 py-5 overflow-y-auto flex-1">
            {{ $slot }}
        </div>

        {{-- Sticky footer --}}
        <div class="px-6 py-4 border-t border-neutral-200 dark:border-neutral-700
                    flex items-center justify-end gap-3 flex-shrink-0">
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