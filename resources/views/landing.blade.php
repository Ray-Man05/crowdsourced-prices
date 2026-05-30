<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      class="{{ ($userTheme ?? 'dark') === 'dark' ? 'dark' : '' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }}: {{ __('Know the real cost of living') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet"/>
    

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>

    <style>
        /* ── Zoom controls: above stat bar, minimal style ── */
        #hero-map .leaflet-control-zoom {
            border: none;
            box-shadow: none;
            margin-bottom: 96px;
            margin-right: 14px;
        }
        #hero-map .leaflet-control-zoom a {
            width: 30px;
            height: 30px;
            line-height: 28px;
            background: rgba(255,255,255,0.88);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(0,0,0,0.08) !important;
            color: #374151;
            font-size: 16px;
            transition: background 0.15s;
        }
        #hero-map .leaflet-control-zoom a:hover { background: rgba(255,255,255,1); }
        .dark #hero-map .leaflet-control-zoom a {
            background: rgba(15,20,30,0.82);
            border: 1px solid rgba(255,255,255,0.10) !important;
            color: #e2e8f0;
        }
        .dark #hero-map .leaflet-control-zoom a:hover { background: rgba(30,40,55,0.95); }
        #hero-map .leaflet-control-zoom-in  { border-radius: 8px 8px 0 0 !important; }
        #hero-map .leaflet-control-zoom-out { border-radius: 0 0 8px 8px !important; }

        /* ── Attribution: faint, above stat bar ── */
        #hero-map .leaflet-control-attribution {
            font-size: 9px;
            opacity: 0.4;
            background: transparent;
            margin-bottom: 80px;
            color: inherit;
        }
        .dark #hero-map .leaflet-control-attribution,
        .dark #hero-map .leaflet-control-attribution a { color: #94a3b8; }

        /* ── Popup: glassmorphism ── */
        #hero-map .leaflet-popup-content-wrapper {
            background: rgba(255,255,255,0.90);
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
            border: 1px solid rgba(0,0,0,0.07);
            border-radius: 12px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.13), 0 1px 4px rgba(0,0,0,0.06);
            font-family: inherit;
            color: #111827;
        }
        #hero-map .leaflet-popup-content {
            margin: 10px 14px 10px;
            font-size: 13px;
            line-height: 1.55;
        }
        #hero-map .leaflet-popup-tip-container { display: none; }
        #hero-map .leaflet-popup-close-button {
            color: #9ca3af !important;
            padding: 5px 8px !important;
            font-size: 17px !important;
        }
        .dark #hero-map .leaflet-popup-content-wrapper {
            background: rgba(12,18,30,0.88);
            border: 1px solid rgba(255,255,255,0.10);
            color: #f1f5f9;
        }

        /* ── Gradient overlay ── */
        .hero-overlay {
            pointer-events: none;
            background:
                linear-gradient(105deg,
                    rgba(243,244,246,1.00)  0%,
                    rgba(243,244,246,0.93) 22%,
                    rgba(243,244,246,0.55) 42%,
                    rgba(243,244,246,0.10) 62%,
                    transparent            78%
                ),
                linear-gradient(to bottom,
                    transparent 45%,
                    rgba(243,244,246,0.45) 100%
                );
        }
        .dark .hero-overlay {
            background:
                linear-gradient(105deg,
                    rgba(10,12,18,1.00)  0%,
                    rgba(10,12,18,0.93) 22%,
                    rgba(10,12,18,0.55) 42%,
                    rgba(10,12,18,0.08) 62%,
                    transparent         78%
                ),
                linear-gradient(to bottom,
                    transparent 45%,
                    rgba(10,12,18,0.45) 100%
                );
        }

        /* ── Marker pulse ring (for cities with ≥50 entries) ── */
        @keyframes pulse-ring {
            0%   { transform: scale(1);   opacity: 0.4; }
            100% { transform: scale(2.2); opacity: 0;   }
        }
        .marker-pulse::after {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: 50%;
            background: currentColor;
            animation: pulse-ring 2s ease-out infinite;
        }
    </style>
</head>
<body class="bg-neutral-100 dark:bg-[#0a0c12] text-neutral-900 dark:text-white font-sans antialiased overflow-hidden h-screen">

    {{-- ─── Nav ─── --}}
    <nav class="fixed top-0 inset-x-0 z-50 flex items-center justify-between px-6 sm:px-10 h-14
                bg-white/90 dark:bg-black/35 backdrop-blur-md
                border-b border-neutral-200/70 dark:border-white/[0.05]">
        <a href="{{ route('landing') }}"
           class="text-sm font-bold tracking-tight text-neutral-900 dark:text-white hover:opacity-70 transition">
            {{ config('app.name') }}
        </a>

        <div class="flex items-center gap-3">
            @include('partials.preference-switches')
            @auth
                <span class="hidden sm:block text-sm text-neutral-500 dark:text-neutral-400">
                    {{ auth()->user()->name }}
                </span>
                <a href="{{ route('catalog') }}"
                   class="inline-flex items-center gap-1.5 px-4 py-1.5 text-sm font-semibold rounded-lg
                          bg-primary-600 hover:bg-primary-700 active:bg-primary-800 text-white
                          transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500">
                    {{ __('Open Catalog') }}
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            @else
                <a href="{{ route('login') }}"
                   class="text-sm font-medium text-neutral-600 dark:text-neutral-300 hover:text-neutral-900 dark:hover:text-white transition">
                    {{ __('Log in') }}
                </a>
                <a href="{{ route('register') }}"
                   class="inline-flex items-center px-4 py-1.5 text-sm font-semibold rounded-lg
                          bg-primary-600 hover:bg-primary-700 active:bg-primary-800 text-white
                          transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500">
                    {{ __('Get started') }}
                </a>
            @endauth
        </div>
    </nav>

    {{-- ─── Hero ─── --}}
    <section class="relative h-screen">

        {{-- Interactive map --}}
        <div id="hero-map" class="absolute inset-0 z-0"></div>

        {{-- Gradient overlay (pointer-events: none so map remains clickable) --}}
        <div class="hero-overlay absolute inset-0 z-10"></div>

        {{-- Hero copy --}}
        <div class="relative z-20 h-full flex flex-col justify-center px-8 sm:px-16 lg:px-24 pb-24 pointer-events-none">
            <div class="max-w-md pointer-events-auto">

                <p class="text-[11px] font-semibold tracking-[0.22em] uppercase
                           text-primary-600 dark:text-primary-400 mb-5 select-none">
                    {{ __('Crowdsourced price tracking') }}
                </p>

                <h1 class="text-[2.85rem] sm:text-[3.4rem] font-bold leading-[1.06] tracking-tight
                            text-neutral-900 dark:text-white mb-5">
                    {{ __('Know the real') }}<br>
                    <span class="text-primary-600 dark:text-primary-400">{{ __('cost of living.') }}</span>
                </h1>

                <p class="text-base text-neutral-500 dark:text-neutral-400 leading-relaxed mb-8 max-w-xs">
                    {{ __('Community-powered price data from cities around the world. Compare, contribute, and plan smarter.') }}
                </p>

                {{-- CTAs --}}
                <div class="flex flex-wrap items-center gap-3">
                    <a href="{{ route('map') }}"
                       class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold rounded-xl
                              bg-neutral-900 hover:bg-neutral-700 active:bg-neutral-800 text-white
                              dark:bg-white dark:hover:bg-neutral-100 dark:active:bg-neutral-200 dark:text-neutral-900
                              transition-all duration-150 active:scale-[0.98]
                              focus-visible:outline-none focus-visible:ring-2
                              focus-visible:ring-neutral-700 dark:focus-visible:ring-white/60">
                        <svg class="h-3.5 w-3.5 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                        </svg>
                        {{ __('Explore the full map') }}
                    </a>

                    @auth
                        <a href="{{ route('catalog') }}"
                           class="text-sm font-medium text-neutral-500 dark:text-neutral-400
                                  hover:text-neutral-800 dark:hover:text-neutral-200 transition">
                            {{ __('Browse Catalog') }} →
                        </a>
                    @else
                        <a href="{{ route('register') }}"
                           class="text-sm font-medium text-neutral-500 dark:text-neutral-400
                                  hover:text-neutral-800 dark:hover:text-neutral-200 transition">
                            {{ __('Start for free') }} →
                        </a>
                    @endauth
                </div>

            </div>
        </div>

        {{-- ─── Stat bar ─── --}}
        <div class="absolute bottom-0 inset-x-0 z-20
                    border-t border-neutral-200/60 dark:border-white/[0.06]
                    bg-white/85 dark:bg-black/50 backdrop-blur-md">
            <div class="max-w-2xl mx-auto px-8 py-4 grid grid-cols-3">

                <div class="text-center">
                    <p class="text-xl sm:text-2xl font-bold text-neutral-900 dark:text-white tabular-nums tracking-tight">
                        {{ number_format($stats['cities']) }}
                    </p>
                    <p class="text-[10px] font-medium text-neutral-400 dark:text-neutral-500 mt-0.5 tracking-widest uppercase">
                        {{ __('Cities') }}
                    </p>
                </div>

                <div class="text-center border-x border-neutral-200/60 dark:border-white/[0.06]">
                    <p class="text-xl sm:text-2xl font-bold text-neutral-900 dark:text-white tabular-nums tracking-tight">
                        {{ number_format($stats['estimates']) }}
                    </p>
                    <p class="text-[10px] font-medium text-neutral-400 dark:text-neutral-500 mt-0.5 tracking-widest uppercase">
                        {{ __('Estimates') }}
                    </p>
                </div>

                <div class="text-center">
                    <p class="text-xl sm:text-2xl font-bold text-neutral-900 dark:text-white tabular-nums tracking-tight">
                        {{ number_format($stats['countries']) }}
                    </p>
                    <p class="text-[10px] font-medium text-neutral-400 dark:text-neutral-500 mt-0.5 tracking-widest uppercase">
                        {{ __('Countries') }}
                    </p>
                </div>

            </div>
        </div>

    </section>

    {{-- Color probes — read by JS via getComputedStyle, same pattern as the chart --}}
    <div class="hidden bg-primary-500" id="landing-primary-probe"></div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
    (function () {
        const isDark = () => document.documentElement.classList.contains('dark');

        function randomView() {
            const views = [
                // [[48, 15],   4],    // Europe
                // [[-10, -20], 4.2],  // Africa
                [[37, -45],  4.2],  // North Atlantic
                [[35, -115], 4.2],  // North America
                // [[-10, -95], 4.0],  // South America
                [[15, 100],  4],    // East Asia
                [[25, 55],   4.5],  // Middle East
            ];
            return views[Math.floor(Math.random() * views.length)];
        }

    const [center, zoom] = randomView();

        // preferCanvas: true → all circle markers draw on a single <canvas>
        // instead of individual SVG elements — much faster with many markers
        const map = L.map('hero-map', {
            zoomControl:     false,
            scrollWheelZoom: true,
            doubleClickZoom: true,
            dragging:        true,
            touchZoom:       true,
            boxZoom:         true,
            keyboard:        true,
            zoomSnap:        0.5,
            preferCanvas:    true,
        }).setView(center, zoom);

        L.control.zoom({ position: 'bottomright' }).addTo(map);

        let tileLayer = null;
        function applyTile(dark) {
            if (tileLayer) map.removeLayer(tileLayer);
            tileLayer = L.tileLayer(
                dark
                    ? 'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png'
                    : 'https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png',
                { maxZoom: 18, attribution: '© <a href="https://carto.com">CARTO</a> © <a href="https://www.openstreetmap.org/copyright">OSM</a>' }
            ).addTo(map);
        }

        applyTile(isDark());
        new MutationObserver(() => applyTile(isDark()))
            .observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });

        const cities = @json($cities->values());
        if (!cities.length) return;

        // Read primary-500 from the Tailwind probe element — no hardcoded hex values
        const probeRgb  = getComputedStyle(document.getElementById('landing-primary-probe')).backgroundColor;
        const [pr, pg, pb] = probeRgb.match(/\d+/g).map(Number);
        const primaryColor  = `rgb(${pr},${pg},${pb})`;

        // Compute scale once from the full dataset
        const counts  = cities.map(c => c.count);
        const minC    = Math.min(...counts);
        const maxC    = Math.max(...counts);
        const logRange = Math.log1p(maxC - minC);

        function markerT(count) {
            return logRange > 0 ? Math.log1p(count - minC) / logRange : 1;
        }

        // Sort ascending so higher-count cities render on top
        const sorted = cities.slice().sort((a, b) => a.count - b.count);

        // Single shared popup
        const sharedPopup = L.popup({ maxWidth: 160, minWidth: 130, offset: [0, -2] });

        sorted.forEach(city => {
            const t = markerT(city.count);
            L.circleMarker([city.lat, city.lng], {
                radius:      1.8 + t * 4.2,        // 1.8 → 6 px
                fillColor:   primaryColor,
                fillOpacity: 0.10 + t * 0.75,       // 0.10 (ghost) → 0.85 (solid)
                color:       'transparent',
                weight:      0,
            })
            .on('click', function () {
                const label = city.count === 1 ? '1 submission' : city.count + ' submissions';
                sharedPopup
                    .setLatLng(this.getLatLng())
                    .setContent(
                        '<div style="font-weight:600;font-size:13px;margin-bottom:1px">' + city.city_name + '</div>' +
                        '<div style="font-size:11px;opacity:0.5;margin-bottom:5px">' + city.country + '</div>' +
                        '<div style="font-size:12px;font-weight:600;color:' + primaryColor + '">' + label + '</div>'
                    )
                    .openOn(map);
            })
            .addTo(map);
        });
    })();
    </script>
</body>
</html>
