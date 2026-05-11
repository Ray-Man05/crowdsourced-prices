<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      class="{{ ($userTheme ?? 'light') === 'dark' ? 'dark' : '' }}">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <meta name="csrf-token" content="{{ csrf_token() }}"/>
    <title>{{ config('app.name') }} — Admin</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-neutral-100 dark:bg-neutral-900 min-h-screen font-sans antialiased">

    {{-- Top bar --}}
    <header class="bg-white dark:bg-neutral-800 border-b border-neutral-200 dark:border-neutral-700 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-14 flex items-center justify-between">
            <div class="flex items-center gap-6">
                <a href="{{ route('home') }}"
                class="text-sm text-neutral-500 dark:text-neutral-400 hover:text-neutral-800
                        dark:hover:text-neutral-200 transition">
                    ← {{ __('Back to app') }}
                </a>
                <span class="text-neutral-300 dark:text-neutral-600">|</span>
                <span class="text-sm font-semibold text-neutral-800 dark:text-neutral-100">
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
        <nav class="flex gap-1 mb-8">
            @foreach ([
                ['route' => 'admin.categories', 'label' => __('Categories')],
                ['route' => 'admin.units',      'label' => __('Units')],
                ['route' => 'admin.products',   'label' => __('Products')],
            ] as $item)
                <a href="{{ route($item['route']) }}"
                   class="px-4 py-2 rounded-lg text-sm font-medium transition
                          {{ request()->routeIs($item['route'])
                              ? 'bg-primary-600 text-white'
                              : 'text-neutral-600 dark:text-neutral-300 hover:bg-neutral-200 dark:hover:bg-neutral-700' }}">
                    {{ $item['label'] }}
                </a>
            @endforeach
        </nav>

        {{ $slot }}
    </div>

    @livewireScripts
</body>
</html>