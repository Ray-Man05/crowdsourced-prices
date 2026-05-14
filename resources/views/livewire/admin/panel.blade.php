<div class="bg-surface-card rounded-2xl border border-neutral-200 dark:border-white/[0.06]
            shadow-card overflow-hidden">

    {{-- Panel header --}}
    <div class="px-6 py-4 border-b border-neutral-100 dark:border-white/[0.05]
                flex items-center justify-between gap-4">
        <div>
            <h1 class="text-sm font-semibold tracking-tight text-neutral-900 dark:text-neutral-100">
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
