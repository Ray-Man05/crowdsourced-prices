@props(['title'])

<div class="fixed inset-0 z-50 flex items-center justify-center p-4"
     x-data @keydown.escape.window="$wire.set('showModal', false)">

    {{-- Backdrop --}}
    <div class="absolute inset-0 bg-black/60 backdrop-blur-[2px]"
         wire:click="$set('showModal', false)"></div>

    {{-- Panel --}}
    <div class="relative z-10 w-full {{ $attributes->get('width', 'max-w-2xl') }}
                bg-surface-raised rounded-2xl shadow-card-md
                border border-neutral-200 dark:border-white/[0.08]
                max-h-[90vh] flex flex-col">

        {{-- Header --}}
        <div class="px-6 py-4 border-b border-neutral-100 dark:border-white/[0.06]
                    flex items-center justify-between flex-shrink-0">
            <h2 class="text-sm font-semibold tracking-tight text-neutral-900 dark:text-neutral-100">
                {{ $title }}
            </h2>
            <button wire:click="$set('showModal', false)"
                    class="p-1.5 rounded-lg text-neutral-400 hover:text-neutral-600 dark:hover:text-neutral-200
                           hover:bg-neutral-100 dark:hover:bg-white/[0.06] transition
                           focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Scrollable body --}}
        <div class="px-6 py-5 overflow-y-auto flex-1">
            {{ $slot }}
        </div>

        {{-- Footer --}}
        <div class="px-6 py-4 border-t border-neutral-100 dark:border-white/[0.06]
                    flex items-center justify-end gap-3 flex-shrink-0">
            <button wire:click="$set('showModal', false)"
                    class="px-4 py-2 text-sm font-medium text-neutral-600 dark:text-neutral-400
                           hover:text-neutral-900 dark:hover:text-neutral-200
                           hover:bg-neutral-100 dark:hover:bg-white/[0.05] rounded-lg transition
                           focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-400">
                {{ __('Cancel') }}
            </button>
            <button wire:click="save"
                    wire:loading.attr="disabled"
                    wire:loading.class="opacity-60 cursor-not-allowed"
                    class="px-5 py-2 bg-primary-600 hover:bg-primary-700 active:bg-primary-800
                           text-white text-sm font-semibold rounded-lg transition
                           focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500
                           disabled:opacity-60 disabled:cursor-not-allowed">
                {{ __('Save') }}
            </button>
        </div>

    </div>
</div>
