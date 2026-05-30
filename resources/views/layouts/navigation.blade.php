<nav x-data="{ open: false }"
     class="sticky top-0 z-40 bg-white/95 dark:bg-[#0a0c12]/90 backdrop-blur-md
            border-b border-neutral-200/80 dark:border-white/[0.05] shadow-card">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-14">

            <!-- Logo + Links -->
            <div class="flex items-center gap-8">
                <a href="{{ route('landing') }}"
                   class="text-sm font-bold tracking-tight text-neutral-900 dark:text-white hover:opacity-75 transition">
                    {{ config('app.name') }}
                </a>

                <div class="hidden sm:flex items-center gap-0.5">
                    <x-nav-link :href="route('catalog')" :active="request()->routeIs('catalog')">
                        {{ __('Products') }}
                    </x-nav-link>
                    <x-nav-link :href="route('map')" :active="request()->routeIs('map')">
                        {{ __('Map') }}
                    </x-nav-link>
                    <x-nav-link :href="route('compare')" :active="request()->routeIs('compare')">
                        {{ __('Compare') }}
                    </x-nav-link>
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('Dashboard') }}
                    </x-nav-link>
                    @if (auth()->user()?->isAdmin())
                        <x-nav-link :href="route('admin.categories')" :active="request()->routeIs('admin.*')">
                            {{ __('Admin') }}
                        </x-nav-link>
                    @endif
                </div>
            </div>

            <!-- Right side -->
            <div class="hidden sm:flex items-center gap-2">
                @include('partials.preference-switches')

                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-medium
                                       text-neutral-600 dark:text-neutral-300
                                       hover:bg-neutral-100 dark:hover:bg-white/[0.06]
                                       focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500
                                       transition">
                            {{ Auth::user()->name }}
                            <svg class="h-3.5 w-3.5 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault(); this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = !open"
                        class="p-2 rounded-lg text-neutral-500 dark:text-neutral-400
                               hover:bg-neutral-100 dark:hover:bg-white/[0.06]
                               focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500
                               transition">
                    <svg class="h-5 w-5" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': !open}" class="inline-flex"
                              stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M4 6h16M4 12h16M4 18h16"/>
                        <path :class="{'hidden': !open, 'inline-flex': open}" class="hidden"
                              stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive menu -->
    <div :class="{'block': open, 'hidden': !open}" class="hidden sm:hidden border-t border-neutral-200 dark:border-white/[0.06]">
        <div class="py-2 space-y-0.5 px-3">
            <x-responsive-nav-link :href="route('catalog')" :active="request()->routeIs('catalog')">
                {{ __('Products') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('map')" :active="request()->routeIs('map')">
                {{ __('Map') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('compare')" :active="request()->routeIs('compare')">
                {{ __('Compare') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>
        </div>
        <div class="pt-3 pb-2 border-t border-neutral-200 dark:border-white/[0.06]">
            <div class="px-4 mb-2">
                <p class="text-sm font-semibold text-neutral-800 dark:text-neutral-200">{{ Auth::user()->name }}</p>
                <p class="text-xs text-neutral-500">{{ Auth::user()->email }}</p>
            </div>
            <div class="space-y-0.5 px-3">
                <x-responsive-nav-link :href="route('profile.edit')">{{ __('Profile') }}</x-responsive-nav-link>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault(); this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
