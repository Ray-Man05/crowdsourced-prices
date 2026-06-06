<nav x-data="{ open: false }"
     class="fixed top-0 inset-x-0 z-50
            bg-white/90 dark:bg-black/35 backdrop-blur-md
            border-b border-neutral-200/70 dark:border-white/[0.05]">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-14">

            {{-- ── Left: logo + nav links ─────────────────────────────── --}}
            <div class="flex items-center gap-6">
                <a href="{{ route('landing') }}"
                   class="text-lg font-bold tracking-tight text-neutral-900 dark:text-white hover:opacity-70 transition">
                    {{ config('app.name') }}
                </a>

                <div class="hidden sm:flex items-center gap-0.5">
                    @php
                        $links = [
                            ['route' => 'catalog',   'label' => __('Products'),  'path' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'],
                            ['route' => 'map',       'label' => __('Map'),       'path' => 'M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.4471-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7'],
                            ['route' => 'compare',   'label' => __('Compare'),   'path' => 'M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7'],
                            ['route' => 'dashboard', 'label' => __('Dashboard'), 'path' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
                        ];
                    @endphp
                    @foreach ($links as $link)
                        @php $active = request()->routeIs($link['route']); @endphp
                        <a href="{{ route($link['route']) }}"
                           class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[15px] transition-all
                                  {{ $active
                                      ? 'font-semibold text-primary-600 dark:text-primary-400 bg-primary-50 dark:bg-primary-900/25'
                                      : 'font-medium text-neutral-800 dark:text-neutral-200 hover:text-neutral-900 dark:hover:text-white hover:bg-neutral-50 dark:hover:bg-white/[0.06]' }}">
                            <svg class="h-3.5 w-3.5 {{ $active ? 'opacity-100' : 'opacity-60' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $link['path'] }}"/>
                            </svg>
                            {{ $link['label'] }}
                        </a>
                    @endforeach
                    @if (auth()->user()?->isAdmin())
                        @php $adminActive = request()->routeIs('admin.*'); @endphp
                        <a href="{{ route('admin.categories') }}"
                           class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-md transition-all
                                  {{ $adminActive
                                      ? 'font-semibold text-primary-700 dark:text-primary-300 bg-primary-50 dark:bg-primary-900/30'
                                      : 'font-medium text-neutral-800 dark:text-neutral-200 hover:text-primary-600 dark:hover:text-primary-400 hover:bg-primary-50 dark:hover:bg-primary-900/20' }}">
                            <svg class="h-3.5 w-3.5 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            {{ __('Admin') }}
                        </a>
                    @endif
                </div>
            </div>

            {{-- ── Right: preferences + user dropdown ────────────────── --}}
            <div class="hidden sm:flex items-center gap-2">
                @include('partials.preference-switches')

                <div class="group relative">
                    <button class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[15px] font-medium
                                   text-neutral-700 dark:text-neutral-200
                                   hover:text-neutral-900 dark:hover:text-white
                                   hover:bg-neutral-50 dark:hover:bg-white/[0.06] transition-all">
                        {{ Auth::user()->name }}
                        <svg class="h-3 w-3 opacity-40 transition-transform group-hover:rotate-180 duration-200"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>

                    <div class="absolute right-0 top-full pt-3.5 w-52 z-50
                                opacity-0 -translate-y-1 pointer-events-none
                                group-hover:opacity-100 group-hover:translate-y-0 group-hover:pointer-events-auto
                                transition-all duration-150">
                        <div class="rounded-xl overflow-hidden
                                    border border-neutral-200/70 dark:border-white/[0.08]
                                    bg-white/[0.97] dark:bg-[#0a0c12]/95 backdrop-blur-xl
                                    shadow-lg shadow-black/[0.08] dark:shadow-black/40">
                            {{-- Identity header --}}
                            <div class="px-4 py-3 border-b border-neutral-100 dark:border-white/[0.06]">
                                <p class="text-xs font-semibold text-neutral-800 dark:text-neutral-100 truncate">{{ Auth::user()->name }}</p>
                                <p class="text-[11px] text-neutral-500 dark:text-neutral-400 truncate mt-0.5">{{ Auth::user()->email }}</p>
                            </div>
                            <div class="py-1">
                                <a href="{{ route('profile.edit') }}"
                                   class="flex items-center gap-2.5 px-4 py-2 text-[15px]
                                          text-neutral-700 dark:text-neutral-100
                                          hover:text-neutral-900 dark:hover:text-white
                                          hover:bg-neutral-100 dark:hover:bg-white/[0.07] transition-colors">
                                    <svg class="h-3.5 w-3.5 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                    {{ __('Profile') }}
                                </a>
                            </div>
                            <div class="h-px bg-neutral-100 dark:bg-white/[0.05]"></div>
                            <div class="py-1">
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit"
                                            class="w-full flex items-center gap-2.5 px-4 py-2 text-[15px] text-left
                                                   text-neutral-700 dark:text-neutral-100
                                                   hover:text-neutral-900 dark:hover:text-white
                                                   hover:bg-neutral-100 dark:hover:bg-white/[0.07] transition-colors">
                                        <svg class="h-3.5 w-3.5 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                        </svg>
                                        {{ __('Log out') }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── Hamburger (mobile) ──────────────────────────────────── --}}
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = !open"
                        class="p-2 rounded-lg text-neutral-500 dark:text-neutral-400
                               hover:bg-neutral-100 dark:hover:bg-white/[0.06]
                               focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 transition">
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

    {{-- ── Mobile menu ─────────────────────────────────────────────────── --}}
    <div :class="{'block': open, 'hidden': !open}" class="hidden sm:hidden
                 border-t border-neutral-200/70 dark:border-white/[0.05]
                 bg-white/95 dark:bg-black/60 backdrop-blur-md">
        <div class="px-4 py-3 space-y-0.5">
            @foreach ($links as $link)
                @php $active = request()->routeIs($link['route']); @endphp
                <a href="{{ route($link['route']) }}"
                   class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg text-sm transition-all
                          {{ $active
                              ? 'font-semibold text-neutral-900 dark:text-white bg-neutral-100 dark:bg-white/[0.10]'
                              : 'font-medium text-neutral-500 dark:text-neutral-400 hover:text-neutral-800 dark:hover:text-neutral-100 hover:bg-neutral-50 dark:hover:bg-white/[0.06]' }}">
                    <svg class="h-4 w-4 {{ $active ? 'opacity-75' : 'opacity-40' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $link['path'] }}"/>
                    </svg>
                    {{ $link['label'] }}
                </a>
            @endforeach
            @if (auth()->user()?->isAdmin())
                <a href="{{ route('admin.categories') }}"
                   class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg text-sm font-medium transition-all
                          text-neutral-500 dark:text-neutral-400 hover:text-primary-600 dark:hover:text-primary-400 hover:bg-primary-50 dark:hover:bg-primary-900/20">
                    <svg class="h-4 w-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    {{ __('Admin') }}
                </a>
            @endif
        </div>
        <div class="px-4 py-3 border-t border-neutral-100 dark:border-white/[0.05] space-y-0.5">
            <div class="px-3 py-2 mb-1">
                <p class="text-xs font-semibold text-neutral-800 dark:text-neutral-100">{{ Auth::user()->name }}</p>
                <p class="text-[11px] text-neutral-400 dark:text-neutral-500 mt-0.5">{{ Auth::user()->email }}</p>
            </div>
            <a href="{{ route('profile.edit') }}"
               class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg text-sm font-medium
                      text-neutral-500 dark:text-neutral-400 hover:text-neutral-800 dark:hover:text-neutral-100
                      hover:bg-neutral-50 dark:hover:bg-white/[0.06] transition-all">
                <svg class="h-4 w-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                {{ __('Profile') }}
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                        class="w-full flex items-center gap-2.5 px-3 py-2.5 rounded-lg text-sm font-medium text-left
                               text-neutral-500 dark:text-neutral-400 hover:text-neutral-800 dark:hover:text-neutral-100
                               hover:bg-neutral-50 dark:hover:bg-white/[0.06] transition-all">
                    <svg class="h-4 w-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    {{ __('Log out') }}
                </button>
            </form>
        </div>
    </div>
</nav>
