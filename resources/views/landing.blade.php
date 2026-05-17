<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }} — Know the real cost of living</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet"/>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Leaflet + MarkerCluster CSS --}}
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css"/>
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css"/>

    <style>
        /* Force Leaflet controls off on the hero map */
        #hero-map .leaflet-control-container { display: none; }
        #hero-map .leaflet-attribution-flag  { display: none; }

        .hero-overlay {
            background:
                linear-gradient(to right,
                    rgba(0,0,0,0.92) 0%,
                    rgba(0,0,0,0.65) 40%,
                    rgba(0,0,0,0.15) 70%,
                    rgba(0,0,0,0.35) 100%
                ),
                linear-gradient(to bottom,
                    transparent 50%,
                    rgba(0,0,0,0.55) 100%
                );
        }
    </style>
</head>
<body class="bg-[#0a0c12] text-white font-sans antialiased overflow-hidden h-screen">

    {{-- ─── Fixed nav ─── --}}
    <nav class="fixed top-0 inset-x-0 z-50 flex items-center justify-between px-6 sm:px-10 h-14
                bg-black/40 backdrop-blur-md border-b border-white/[0.06]">
        <a href="{{ route('landing') }}"
           class="text-sm font-bold tracking-tight text-white hover:opacity-80 transition">
            {{ config('app.name') }}
        </a>

        <div class="flex items-center gap-3">
            @auth
                <span class="hidden sm:block text-sm text-neutral-400">
                    {{ auth()->user()->name }}
                </span>
                <a href="{{ route('catalog') }}"
                   class="inline-flex items-center gap-1.5 px-4 py-1.5 text-sm font-semibold rounded-lg
                          bg-primary-600 hover:bg-primary-700 active:bg-primary-800 text-white
                          transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500">
                    Open Catalog
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            @else
                <a href="{{ route('login') }}"
                   class="text-sm font-medium text-neutral-300 hover:text-white transition">
                    Log in
                </a>
                <a href="{{ route('register') }}"
                   class="inline-flex items-center px-4 py-1.5 text-sm font-semibold rounded-lg
                          bg-primary-600 hover:bg-primary-700 active:bg-primary-800 text-white
                          transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500">
                    Get started
                </a>
            @endauth
        </div>
    </nav>

    {{-- ─── Hero ─── --}}
    <section class="relative h-screen">

        {{-- Map background (non-interactive) --}}
        <div id="hero-map" class="absolute inset-0 z-0"></div>

        {{-- Gradient overlay --}}
        <div class="hero-overlay absolute inset-0 z-10"></div>

        {{-- Hero copy --}}
        <div class="relative z-20 h-full flex flex-col justify-center px-8 sm:px-16 lg:px-24 pb-28">
            <div class="max-w-lg">
                <p class="text-xs font-semibold tracking-[0.18em] uppercase text-primary-400 mb-4 select-none">
                    Crowdsourced price tracking
                </p>
                <h1 class="text-5xl sm:text-6xl font-bold leading-[1.08] tracking-tight text-white mb-5">
                    Know the real<br>cost of living.
                </h1>
                <p class="text-lg text-neutral-300 leading-relaxed mb-8 max-w-sm">
                    Community-powered price data from cities around the world.
                    Compare, contribute, and plan smarter.
                </p>

                <div class="flex flex-wrap items-center gap-3">
                    @auth
                        <a href="{{ route('catalog') }}"
                           class="inline-flex items-center gap-2 px-6 py-3 text-sm font-semibold rounded-xl
                                  bg-primary-600 hover:bg-primary-700 active:bg-primary-800 text-white
                                  transition-all duration-150 active:scale-[0.98]
                                  focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500
                                  focus-visible:ring-offset-2 focus-visible:ring-offset-black">
                            Browse Catalog
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                            </svg>
                        </a>
                        <a href="{{ route('map') }}"
                           class="inline-flex items-center gap-2 px-6 py-3 text-sm font-semibold rounded-xl
                                  border border-white/20 text-white hover:bg-white/10 hover:border-white/30
                                  transition-all duration-150 active:scale-[0.98]
                                  focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/40
                                  focus-visible:ring-offset-2 focus-visible:ring-offset-black">
                            Explore Map
                        </a>
                    @else
                        <a href="{{ route('register') }}"
                           class="inline-flex items-center gap-2 px-6 py-3 text-sm font-semibold rounded-xl
                                  bg-primary-600 hover:bg-primary-700 active:bg-primary-800 text-white
                                  transition-all duration-150 active:scale-[0.98]
                                  focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500
                                  focus-visible:ring-offset-2 focus-visible:ring-offset-black">
                            Start for free
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                            </svg>
                        </a>
                        <a href="{{ route('login') }}"
                           class="inline-flex items-center gap-2 px-6 py-3 text-sm font-semibold rounded-xl
                                  border border-white/20 text-white hover:bg-white/10 hover:border-white/30
                                  transition-all duration-150 active:scale-[0.98]
                                  focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/40
                                  focus-visible:ring-offset-2 focus-visible:ring-offset-black">
                            Log in
                        </a>
                    @endauth
                </div>
            </div>
        </div>

        {{-- ─── Stat bar ─── --}}
        <div class="absolute bottom-0 inset-x-0 z-20 border-t border-white/[0.07]
                    bg-black/55 backdrop-blur-md">
            <div class="max-w-3xl mx-auto px-8 py-5 grid grid-cols-3">

                <div class="text-center">
                    <p class="text-2xl sm:text-3xl font-bold text-white tabular-nums">
                        {{ number_format($stats['cities']) }}
                    </p>
                    <p class="text-xs text-neutral-500 mt-0.5 tracking-widest uppercase">
                        Cities
                    </p>
                </div>

                <div class="text-center border-x border-white/[0.07]">
                    <p class="text-2xl sm:text-3xl font-bold text-white tabular-nums">
                        {{ number_format($stats['estimates']) }}
                    </p>
                    <p class="text-xs text-neutral-500 mt-0.5 tracking-widest uppercase">
                        Estimates
                    </p>
                </div>

                <div class="text-center">
                    <p class="text-2xl sm:text-3xl font-bold text-white tabular-nums">
                        {{ number_format($stats['countries']) }}
                    </p>
                    <p class="text-xs text-neutral-500 mt-0.5 tracking-widest uppercase">
                        Countries
                    </p>
                </div>

            </div>
        </div>

    </section>

    {{-- Leaflet + MarkerCluster JS --}}
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
    <script>
    (function () {
        const map = L.map('hero-map', {
            zoomControl:        false,
            dragging:           false,
            touchZoom:          false,
            scrollWheelZoom:    false,
            doubleClickZoom:    false,
            boxZoom:            false,
            keyboard:           false,
            attributionControl: false,
        }).setView([20, 10], 3);

        L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager_nolabels/{z}/{x}/{y}{r}.png', {
            maxZoom: 19,
        }).addTo(map);

        const cities = @json($cities);

        const clusterGroup = L.markerClusterGroup({
            showCoverageOnHover: false,
            maxClusterRadius:    70,
            spiderfyOnMaxZoom:   false,
            iconCreateFunction(cluster) {
                const n = cluster.getChildCount();
                const label = n >= 100 ? '100+' : n >= 50 ? '50+' : n >= 20 ? '20+' : n;
                const s = n >= 100 ? 52 : n >= 50 ? 44 : 36;
                return L.divIcon({
                    html: `<div style="
                        width:${s}px;height:${s}px;
                        background:rgba(16,185,129,0.80);
                        border:2px solid rgba(52,211,153,0.55);
                        border-radius:50%;
                        box-shadow:0 0 0 6px rgba(16,185,129,0.12);
                        display:flex;align-items:center;justify-content:center;
                        color:#fff;font-size:${n>=100?11:12}px;font-weight:700;
                        letter-spacing:-0.03em;font-family:ui-sans-serif,system-ui,sans-serif;
                    ">${label}</div>`,
                    className: '',
                    iconSize:   [s, s],
                    iconAnchor: [s / 2, s / 2],
                });
            },
        });

        cities.forEach(city => {
            clusterGroup.addLayer(
                L.circleMarker([city.lat, city.lng], {
                    radius:      5,
                    fillColor:   '#10b981',
                    color:       '#10b981',
                    weight:      0,
                    fillOpacity: 0.8,
                })
            );
        });

        map.addLayer(clusterGroup);
    })();
    </script>
</body>
</html>
