<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

    {{-- Header --}}
    <div>
        <a href="{{ route('catalog') }}"
           class="inline-flex items-center gap-1 text-sm text-neutral-500 dark:text-neutral-400
                  hover:text-neutral-800 dark:hover:text-neutral-200 transition mb-3">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            {{ __('Products') }}
        </a>

        <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-5">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-neutral-900 dark:text-neutral-100">
                    {{ $product->name }}
                </h1>
                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 mt-1.5">
                    <span class="inline-flex items-center gap-1.5 text-sm text-neutral-500 dark:text-neutral-400">
                        <span class="inline-block w-2 h-2 rounded-full flex-shrink-0"
                              style="background-color: {{ $product->category->color }}"></span>
                        {{ $product->category->name }}
                        @if ($product->unit) · {{ $product->unit->name }} @endif
                    </span>
                    <span class="inline-flex items-center gap-1 text-sm text-neutral-500 dark:text-neutral-400">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        {{ auth()->user()->city->name ?? __('No city set') }}
                    </span>
                </div>
                @if ($product->description)
                    <p class="mt-2 text-sm text-neutral-600 dark:text-neutral-400 max-w-prose leading-relaxed">
                        {{ $product->description }}
                    </p>
                @endif
            </div>

            <div class="lg:flex-shrink-0 lg:w-72">
                <livewire:estimate-submission :product="$product" />
            </div>
        </div>
    </div>

    <livewire:product-prices-over-time :product="$product" />
    <livewire:product-prices-by-city   :product="$product" />

</div>
