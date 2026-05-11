{{-- Theme toggle --}}
<button onclick="toggleTheme()"
        class="p-2 rounded-md text-neutral-500 dark:text-neutral-400
               hover:bg-neutral-100 dark:hover:bg-neutral-700 transition"
        title="{{ __('Toggle theme') }}">
    <svg class="hidden dark:block h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707M17.657 17.657l-.707-.707M6.343 6.343l-.707-.707M12 8a4 4 0 100 8 4 4 0 000-8z"/>
    </svg>
    <svg class="block dark:hidden h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/>
    </svg>
</button>

{{-- Locale switcher --}}
<div class="flex items-center space-x-1 text-sm">
    @foreach(config('app.available_locales') as $code => $label)
        <a href="{{ route('preferences.locale', $code) }}"
           class="{{ app()->getLocale() === $code
               ? 'font-semibold text-primary-600 dark:text-primary-400'
               : 'text-neutral-500 dark:text-neutral-400 hover:text-neutral-800 dark:hover:text-neutral-200' }} transition">
            {{ $label }}
        </a>
        @if(!$loop->last)
            <span class="text-neutral-300 dark:text-neutral-600">|</span>
        @endif
    @endforeach
</div>