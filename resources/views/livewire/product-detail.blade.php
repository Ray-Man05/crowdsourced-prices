<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

    {{-- Header --}}
    <div class="flex items-start justify-between gap-4">
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
            @if ($product->description)
                <p class="mt-2 text-sm text-neutral-600 dark:text-neutral-300 max-w-prose">
                    {{ $product->description }}
                </p>
            @endif
        </div>

        <a href="{{ route('estimates.create', $product) }}"
        class="flex-shrink-0 px-4 py-2 bg-primary-600 hover:bg-primary-700
                text-white text-sm font-medium rounded-lg transition">
            + {{ __('Add estimate') }}
        </a>
    </div>

    <livewire:product-prices-over-time :product="$product" />
    <livewire:product-prices-by-city   :product="$product" />

</div>