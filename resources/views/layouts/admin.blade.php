<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      class="{{ ($userTheme ?? 'light') === 'dark' ? 'dark' : '' }}">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <meta name="csrf-token" content="{{ csrf_token() }}"/>
    <title>{{ config('app.name') }} — Admin</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet"/>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-surface-page min-h-screen font-sans antialiased text-neutral-900 dark:text-neutral-100">

    {{-- Top bar --}}
    <header class="sticky top-0 z-40 bg-white/95 dark:bg-[#0a0c12]/90 backdrop-blur-md
                   border-b border-neutral-200/80 dark:border-white/[0.05] shadow-card">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-14 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <a href="{{ route('catalog') }}"
                   class="flex items-center gap-1.5 text-sm text-neutral-500 dark:text-neutral-400
                          hover:text-neutral-800 dark:hover:text-neutral-200 transition">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    {{ __('Back to app') }}
                </a>
                <span class="text-neutral-200 dark:text-neutral-700">|</span>
                <span class="text-sm font-semibold tracking-tight text-neutral-800 dark:text-neutral-100">
                    {{ __('Admin') }}
                </span>
            </div>
            <div class="flex items-center gap-4">
                @include('partials.preference-switches')
                <span class="text-xs text-neutral-400 dark:text-neutral-500">
                    {{ auth()->user()->name }}
                </span>
            </div>
        </div>
    </header>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        {{-- Admin nav --}}
        <nav class="flex flex-wrap gap-1 mb-7">
            @foreach ([
                ['route' => 'admin.categories', 'label' => __('Categories')],
                ['route' => 'admin.units',      'label' => __('Units')],
                ['route' => 'admin.products',   'label' => __('Products')],
            ] as $item)
                <a href="{{ route($item['route']) }}"
                   class="px-4 py-2 rounded-xl text-sm font-medium transition
                          focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500
                          {{ request()->routeIs($item['route'])
                              ? 'bg-primary-600 text-white shadow-sm'
                              : 'text-neutral-600 dark:text-neutral-300 hover:bg-neutral-100 dark:hover:bg-white/[0.06]' }}">
                    {{ $item['label'] }}
                </a>
            @endforeach
        </nav>

        {{ $slot }}
    </div>

    @livewireScripts
    @stack('scripts')
</body>
</html>
