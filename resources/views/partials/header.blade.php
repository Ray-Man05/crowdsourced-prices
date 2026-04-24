<header class="w-full bg-primary text-white px-6 py-3 flex items-center justify-between shadow">

    <a href="{{ route('dashboard') }}" class="font-semibold text-lg tracking-tight">
        {{ config('app.name') }}
    </a>

    <div class="flex items-center gap-4">

        {{-- Locale switcher --}}
        <div class="flex items-center gap-1 text-sm">
            <form method="POST" action="{{ route('preferences.locale', 'en') }}">
                @csrf
                <button type="submit"
                    class="px-2 py-1 rounded {{ app()->getLocale() === 'en' ? 'bg-white/20 font-bold' : 'hover:bg-white/10' }}">
                    EN
                </button>
            </form>
            <form method="POST" action="{{ route('preferences.locale', 'fr') }}">
                @csrf
                <button type="submit"
                    class="px-2 py-1 rounded {{ app()->getLocale() === 'fr' ? 'bg-white/20 font-bold' : 'hover:bg-white/10' }}">
                    FR
                </button>
            </form>
        </div>

        {{-- Theme switcher --}}
        <form method="POST" action="{{ route('preferences.theme', $userTheme === 'light' ? 'dark' : 'light') }}">
            @csrf
            <button type="submit" class="px-2 py-1 rounded hover:bg-white/10 text-sm">
                {{ $userTheme === 'light' ? '🌙' : '☀️' }}
            </button>
        </form>

        {{-- User menu --}}
        @auth
            <span class="text-sm opacity-75">{{ auth()->user()->name }}</span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-sm hover:underline">{{ __('Log out') }}</button>
            </form>
        @endauth

    </div>
</header>