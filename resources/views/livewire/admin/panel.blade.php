<div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200
            dark:border-neutral-700 shadow-sm overflow-hidden">

    {{-- Panel header --}}
    <div class="px-6 py-4 border-b border-neutral-200 dark:border-neutral-700
                flex items-center justify-between gap-4">
        <div>
            <h1 class="text-base font-semibold text-neutral-900 dark:text-neutral-100">
                {{ $title }}
            </h1>
            @isset($subtitle)
                <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-0.5">{{ $subtitle }}</p>
            @endisset
        </div>
        @isset($action)
            {{ $action }}
        @endisset
    </div>

    {{ $slot }}
</div>