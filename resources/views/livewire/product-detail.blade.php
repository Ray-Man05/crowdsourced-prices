<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

    {{-- Header --}}
    <div class="flex items-start justify-between gap-6">
        <div>
            <a href="{{ route('home') }}"
            class="text-sm text-neutral-500 dark:text-neutral-400 hover:underline">
                ← {{ __('Products') }}
            </a>
            <h1 class="mt-2 text-2xl font-bold text-neutral-900 dark:text-neutral-100">
                {{ $product->name }}
            </h1>
            <div class="flex items-center gap-2 mt-1">
                <span class="inline-block w-2.5 h-2.5 rounded-full"
                    style="background-color: {{ $product->category->color }}"></span>
                <span class="text-sm text-neutral-500 dark:text-neutral-400">
                    {{ $product->category->name }}
                    @if ($product->unit) · {{ $product->unit->name }} @endif
                </span>
            </div>
            <div class="flex items-center gap-1.5 mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                {{ auth()->user()->city->name ?? __('No city set') }}
            </div>
            @if ($product->description)
                <p class="mt-2 text-sm text-neutral-600 dark:text-neutral-300 max-w-prose">
                    {{ $product->description }}
                </p>
            @endif
        </div>

        {{-- Estimate widget sits top-right, self-contained --}}
        <div class="flex-shrink-0 w-72">
            <livewire:estimate-submission :product="$product" />
        </div>
    </div>

    <livewire:product-prices-over-time :product="$product" />
    <livewire:product-prices-by-city   :product="$product" />

</div>