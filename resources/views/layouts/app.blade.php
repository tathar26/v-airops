<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'V-Ops') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <!-- Styles -->
        @livewireStyles
        @php
            /* ── Tenant colour computation ────────────────────────── */
            $tenantAccent   = auth()->check() && auth()->user()->tenant ? auth()->user()->tenant->accent_color  : '#f97316';
            $tenantBg       = auth()->check() && auth()->user()->tenant && auth()->user()->tenant->bg_color
                                ? auth()->user()->tenant->bg_color : '#0f1117';

            /* Luminance helper */
            $hexLuminance = function(string $hex): float {
                $hex = ltrim($hex, '#');
                if (strlen($hex) === 3) {
                    $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
                }
                $r = hexdec(substr($hex, 0, 2));
                $g = hexdec(substr($hex, 2, 2));
                $b = hexdec(substr($hex, 4, 2));
                return (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
            };

            $bgLuminance     = preg_match('/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/', $tenantBg)
                                 ? $hexLuminance($tenantBg) : 0;
            $accentLuminance = preg_match('/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/', $tenantAccent)
                                 ? $hexLuminance($tenantAccent) : 0;

            $isLight              = $bgLuminance > 0.6;
            $isAccentLight        = $accentLuminance > 0.55;
            $accentTextColor      = $isAccentLight ? '#0f172a' : '#ffffff';
            $accentMutedColor     = $isAccentLight ? 'rgba(15, 23, 42, 0.75)' : 'rgba(255, 255, 255, 0.8)';
            $accentHoverBg        = $isAccentLight ? 'rgba(0, 0, 0, 0.08)' : 'rgba(255, 255, 255, 0.15)';
            $accentActiveBg       = $isAccentLight ? 'rgba(0, 0, 0, 0.18)' : 'rgba(0, 0, 0, 0.28)';
            $accentBorderColor    = $isAccentLight ? 'rgba(0, 0, 0, 0.15)' : 'rgba(0, 0, 0, 0.25)';

            /* Convert accent to RGB triplet for rgba() usage in CSS */
            $accentHex = ltrim($tenantAccent, '#');
            if (strlen($accentHex) === 3) {
                $accentHex = $accentHex[0].$accentHex[0].$accentHex[1].$accentHex[1].$accentHex[2].$accentHex[2];
            }
            $accentRgb = hexdec(substr($accentHex,0,2)).', '.hexdec(substr($accentHex,2,2)).', '.hexdec(substr($accentHex,4,2));
        @endphp
        <style>
            /* ── Design-system CSS Variables ──────────────────────── */
            :root {
                /* Tenant branding */
                --tenant-accent:             {{ $tenantAccent }};
                --tenant-accent-rgb:         {{ $accentRgb }};
                --tenant-accent-text:        {{ $accentTextColor }};
                --tenant-accent-muted:       {{ $accentMutedColor }};
                --tenant-accent-hover-bg:    {{ $accentHoverBg }};
                --tenant-accent-active-bg:   {{ $accentActiveBg }};
                --tenant-accent-border:      {{ $accentBorderColor }};
                --tenant-bg:                 {{ $tenantBg }};

                /* Sidebar / Topbar shell — follows VA accent color */
                --sidebar-bg:                var(--tenant-accent);
                --sidebar-border:            var(--tenant-accent-border);
                --sidebar-text:              var(--tenant-accent-muted);
                --sidebar-text-hover:        var(--tenant-accent-text);
                --sidebar-active-bg:         var(--tenant-accent-active-bg);
                --sidebar-active-text:       var(--tenant-accent-text);

                /* Content cards */
                --card-bg:                   #161c2c;
                --card-header-bg:            #111827;
                --card-body-bg:              #161c2c;
                --card-border:               #1e2d45;
                --card-row-hover:            #1a2235;
            }

            /* ── Base body background ─────────────────────────────── */
            body {
                background-color: var(--tenant-bg) !important;
            }

            /* ── Tenant utility classes ───────────────────────────── */
            .text-tenant-accent   { color: var(--tenant-accent) !important; }
            .bg-tenant-accent     { background-color: var(--tenant-accent) !important; color: var(--tenant-accent-text) !important; }
            .border-tenant-accent { border-color: var(--tenant-accent) !important; }
            .ring-tenant-accent   { --tw-ring-color: var(--tenant-accent) !important; }

            /* ── Sidebar & Topbar follow Accent Color ─────────────── */
            .sidebar-shell {
                background-color: var(--sidebar-bg) !important;
                border-right: 1px solid var(--sidebar-border) !important;
                color: var(--tenant-accent-text) !important;
            }
            .topbar-shell {
                background-color: var(--sidebar-bg) !important;
                border-bottom: 1px solid var(--sidebar-border) !important;
                color: var(--tenant-accent-text) !important;
            }

            /* Nav links */
            .nav-link {
                display: flex;
                align-items: center;
                gap: 0.75rem;
                padding: 0.625rem 0.875rem;
                border-radius: 0.5rem;
                font-size: 0.875rem;
                font-weight: 500;
                color: var(--sidebar-text) !important;
                transition: background-color 150ms ease, color 150ms ease;
                border-left: 3px solid transparent;
            }
            .nav-link:hover {
                background-color: var(--tenant-accent-hover-bg) !important;
                color: var(--sidebar-text-hover) !important;
            }
            .nav-link.active {
                background-color: var(--sidebar-active-bg) !important;
                color: var(--sidebar-active-text) !important;
                border-left-color: var(--tenant-accent-text) !important;
                font-weight: 700;
                box-shadow: 0 1px 3px rgba(0,0,0,0.15);
            }
            .nav-section-label {
                padding: 1rem 0.875rem 0.25rem;
                font-size: 0.625rem; /* 10px */
                font-weight: 800;
                letter-spacing: 0.1em;
                text-transform: uppercase;
                color: var(--tenant-accent-muted) !important;
            }

            /* ── Primary Buttons follow Accent Color ──────────────── */
            .btn-primary,
            button.bg-tenant-accent,
            a.bg-tenant-accent {
                background-color: var(--tenant-accent) !important;
                color: var(--tenant-accent-text) !important;
            }
            .btn-primary:hover,
            button.bg-tenant-accent:hover,
            a.bg-tenant-accent:hover {
                filter: brightness(0.92);
            }

            /* ── Light Theme Adaptations (Content Panels) ─────────── */
            body.theme-light .va-card {
                background-color: rgba(255,255,255,0.92) !important;
                border-color: rgba(0,0,0,0.08) !important;
                box-shadow: 0 1px 4px rgba(0,0,0,0.07) !important;
            }
            body.theme-light .va-card-header {
                background-color: rgba(0,0,0,0.03) !important;
                border-color: rgba(0,0,0,0.08) !important;
                color: #475569 !important;
            }
            body.theme-light .va-card-body {
                background-color: rgba(255,255,255,0.95) !important;
                color: #0f172a !important;
            }
            body.theme-light .va-table thead tr {
                background-color: #f1f5f9 !important;
                border-color: rgba(0,0,0,0.08) !important;
            }
            body.theme-light .va-table thead th { color: #475569 !important; }
            body.theme-light .va-table tbody tr {
                background-color: #ffffff !important;
                border-color: rgba(0,0,0,0.06) !important;
            }
            body.theme-light .va-table tbody tr:hover { background-color: #f8fafc !important; }
            body.theme-light .va-table tbody td { color: #1e293b !important; }

            /* Glass panels light theme */
            body.theme-light .glass-panel {
                background-color: rgba(255,255,255,0.95) !important;
                border-color: rgba(0,0,0,0.08) !important;
                color: #1e293b !important;
                box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05) !important;
            }
            body.theme-light .glass-panel h1,
            body.theme-light .glass-panel h2,
            body.theme-light .glass-panel h3,
            body.theme-light .glass-panel h4 { color: #0f172a !important; }
            body.theme-light .glass-panel .text-white:not(.bg-tenant-accent):not(.bg-vops-primary):not(.bg-emerald-600):not(.bg-red-600) { color: #0f172a !important; }
            body.theme-light .glass-panel .text-gray-200,
            body.theme-light .glass-panel .text-gray-300 { color: #334155 !important; }
            body.theme-light .glass-panel .text-gray-400 { color: #475569 !important; }
            body.theme-light .glass-panel .text-gray-500 { color: #64748b !important; }
            body.theme-light .glass-panel .border-white\/5,
            body.theme-light .glass-panel .border-white\/10 { border-color: rgba(0,0,0,0.08) !important; }
            body.theme-light .glass-panel .bg-white\/5 { background-color: rgba(0,0,0,0.03) !important; }
            body.theme-light .glass-panel input[type="text"],
            body.theme-light .glass-panel input[type="number"],
            body.theme-light .glass-panel input[type="email"],
            body.theme-light .glass-panel input[type="password"],
            body.theme-light .glass-panel select,
            body.theme-light .glass-panel textarea {
                background-color: #ffffff !important;
                border-color: #cbd5e1 !important;
                color: #0f172a !important;
            }

            /* ── Dispatch Console & Dark Cards (avionics — always dark) */
            .dispatch-console,
            .dark-card { color: #ffffff !important; }
            .dispatch-console .text-white, .dark-card .text-white { color: #ffffff !important; }
            .dispatch-console .text-gray-200, .dark-card .text-gray-200 { color: #e2e8f0 !important; }
            .dispatch-console .text-gray-300, .dark-card .text-gray-300 { color: #cbd5e1 !important; }
            .dispatch-console .text-gray-400, .dark-card .text-gray-400 { color: #94a3b8 !important; }
            .dispatch-console .text-gray-500, .dark-card .text-gray-500 { color: #64748b !important; }
            .dispatch-console input[type="text"],
            .dispatch-console input[type="number"],
            .dispatch-console input[type="time"],
            .dispatch-console input[type="date"],
            .dispatch-console select,
            .dispatch-console textarea,
            .dark-card input[type="text"],
            .dark-card input[type="number"],
            .dark-card input[type="time"],
            .dark-card input[type="date"],
            .dark-card select,
            .dark-card textarea {
                background-color: #1e293b !important;
                border-color: #334155 !important;
                color: #ffffff !important;
            }
            .dispatch-console select option, .dark-card select option { background-color: #1e293b !important; color: #ffffff !important; }
            .dispatch-console input::placeholder, .dark-card input::placeholder { color: #64748b !important; }
        </style>
    </head>
    <body class="font-sans antialiased selection:bg-tenant-accent selection:text-white {{ $isLight ? 'theme-light' : '' }}">
        <x-banner />

        <div class="min-h-screen flex w-full">
            <!-- Sidebar — always-dark shell, independent of tenant theme -->
            <div class="w-56 flex-shrink-0 sidebar-shell hidden md:flex flex-col">
                @livewire('navigation-menu')
            </div>

            <!-- Main Content Area -->
            <div class="flex-grow flex flex-col min-w-0">
                <!-- Top Navigation Bar -->
                <header class="h-14 topbar-shell flex items-center justify-between px-4 sm:px-6 flex-shrink-0">
                    <!-- Mobile: logo / VA name -->
                    <div class="md:hidden flex items-center">
                        @if (auth()->check() && auth()->user()->tenant && auth()->user()->tenant->logo_path)
                            <img src="{{ Storage::url(auth()->user()->tenant->logo_path) }}" alt="VA Logo" class="block h-8 w-auto mr-3 object-contain">
                        @else
                            <h1 class="text-base font-bold tracking-widest uppercase">
                                {{ auth()->check() && auth()->user()->tenant ? auth()->user()->tenant->name : 'V-Ops' }}
                            </h1>
                        @endif
                    </div>

                    <!-- Desktop: VA name (sidebar has brand, topbar shows page context) -->
                    <div class="hidden md:flex items-center">
                        <h1 class="text-sm font-bold tracking-widest uppercase">
                            {{ auth()->check() && auth()->user()->tenant ? auth()->user()->tenant->name : 'V-Ops' }}
                        </h1>
                    </div>

                    <!-- Right side: Clock + User dropdown -->
                    <div class="flex items-center gap-5">
                        <!-- Zulu Clock -->
                        <div x-data="{ time: '' }" x-init="setInterval(() => { let d = new Date(); time = d.toISOString().substring(11,19) + 'z'; }, 1000)"
                             class="hidden sm:flex items-center gap-1.5 opacity-80 text-xs font-mono" title="Zulu Time (UTC)">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span x-text="time"></span>
                        </div>

                        @php
                            $user = Auth::user();
                            $userAirlines  = $user ? $user->userAirlines()->with('tenant')->get() : collect();
                            $activeTenantId = session('active_airline_id', $user->tenant_id ?? null);
                        @endphp

                        <!-- User / Airline Dropdown -->
                        <div class="relative" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false">
                            <button @click="open = !open" type="button"
                                class="flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg border border-black/20 bg-black/20 hover:bg-black/30 transition-all text-right group focus:outline-none shadow-sm">
                                <div>
                                    <div class="text-xs font-bold flex items-center justify-end gap-1.5">
                                        <span>{{ $user->full_name }}</span>
                                        @if($user->hasRole('Master Admin'))
                                            <span class="bg-black/30 text-white border border-white/30 text-[9px] font-bold px-1.5 py-0.5 rounded uppercase tracking-wider">Sys Admin</span>
                                        @elseif($user->hasRole('VA Owner'))
                                            <span class="bg-black/30 text-white border border-white/30 text-[9px] font-bold px-1.5 py-0.5 rounded uppercase tracking-wider">Owner</span>
                                        @endif
                                    </div>
                                    <div class="text-[10px] opacity-80 flex items-center justify-end gap-1.5 font-mono">
                                        <span class="font-bold underline underline-offset-2">{{ $user->activeCallsign() }}</span>
                                        <span>&bull;</span>
                                        <span>{{ $user->active_rank_name }}</span>
                                    </div>
                                </div>
                                <svg class="w-3.5 h-3.5 opacity-70 group-hover:opacity-100 transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>

                            <!-- Dropdown -->
                            <div x-show="open"
                                 x-transition:enter="transition ease-out duration-150"
                                 x-transition:enter-start="transform opacity-0 scale-95"
                                 x-transition:enter-end="transform opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-100"
                                 x-transition:leave-start="transform opacity-100 scale-100"
                                 x-transition:leave-end="transform opacity-0 scale-95"
                                 class="absolute right-0 mt-1 w-72 rounded-lg bg-[#111827] border border-[#1e2436] shadow-2xl z-50 overflow-hidden divide-y divide-[#1e2436]"
                                 style="display: none;">

                                <!-- User Header -->
                                <div class="px-4 py-3 bg-[#0d1117]">
                                    <p class="text-xs font-bold text-white">{{ $user->full_name }}</p>
                                    <p class="text-[10px] text-slate-500 truncate mt-0.5">{{ $user->email }}</p>
                                </div>

                                <!-- Enrolled VAs -->
                                <div class="p-2 bg-[#0d1117]/60">
                                    <div class="flex items-center justify-between mb-1.5 px-1">
                                        <span class="text-[9px] font-bold uppercase tracking-widest text-slate-600">My Virtual Airlines</span>
                                        <span class="text-[9px] text-slate-600 font-mono">{{ $userAirlines->count() }} joined</span>
                                    </div>
                                    <div class="space-y-0.5 max-h-44 overflow-y-auto">
                                        @forelse ($userAirlines as $ua)
                                            @php $isActive = ($activeTenantId == $ua->tenant_id); @endphp
                                            <form action="{{ route('session.switch-airline') }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="tenant_id" value="{{ $ua->tenant_id }}">
                                                <button type="submit"
                                                    class="w-full text-left px-2 py-1.5 rounded-md flex items-center justify-between transition-colors group {{ $isActive ? 'bg-sky-500/10 border border-sky-500/20' : 'hover:bg-white/5 border border-transparent' }}">
                                                    <div class="flex items-center gap-2">
                                                        <div class="w-6 h-6 rounded bg-[#1a2035] border border-[#2a3250] flex items-center justify-center text-[9px] font-bold text-sky-400 font-mono flex-shrink-0">
                                                            {{ strtoupper(substr($ua->tenant->icao ?? 'VA', 0, 3)) }}
                                                        </div>
                                                        <div>
                                                            <p class="text-[11px] font-bold {{ $isActive ? 'text-sky-300' : 'text-slate-300 group-hover:text-white' }} transition-colors leading-tight">{{ $ua->tenant->name }}</p>
                                                            <p class="text-[9px] text-slate-500 font-mono">{{ $ua->callsign }} &bull; {{ $ua->rank ?? 'Cadet' }}</p>
                                                        </div>
                                                    </div>
                                                    @if ($isActive)
                                                        <span class="flex items-center gap-1 text-[9px] text-emerald-400 font-semibold">
                                                            Active <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                                        </span>
                                                    @else
                                                        <span class="text-[9px] text-slate-600 group-hover:text-slate-400 transition-colors">Switch →</span>
                                                    @endif
                                                </button>
                                            </form>
                                        @empty
                                            <p class="text-[10px] text-slate-600 py-2 px-1">No virtual airlines enrolled yet.</p>
                                        @endforelse
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div class="p-1.5 space-y-0.5 bg-[#0d1117]/80 text-xs">
                                    <a href="{{ route('onboarding.select-airline') }}"
                                       class="flex items-center gap-2 px-2.5 py-1.5 rounded-md text-sky-500 hover:text-sky-400 hover:bg-sky-500/8 transition-colors font-medium text-[11px]">
                                        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                        Join / Create Virtual Airline
                                    </a>
                                    <a href="{{ route('profile.account') }}"
                                       class="flex items-center gap-2 px-2.5 py-1.5 rounded-md text-slate-400 hover:text-slate-200 hover:bg-white/5 transition-colors text-[11px]">
                                        <svg class="w-3.5 h-3.5 flex-shrink-0 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        Account Settings
                                    </a>
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit"
                                            class="w-full text-left flex items-center gap-2 px-2.5 py-1.5 rounded-md text-red-500 hover:text-red-400 hover:bg-red-500/8 transition-colors text-[11px]">
                                            <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                            Sign Out
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </header>

                <!-- Page Content -->
                <main class="flex-grow overflow-y-auto p-4 sm:p-6 lg:p-8">
                    <div class="max-w-[1600px] mx-auto w-full bg-[#181D29]/80 backdrop-blur-xl border border-white/10 rounded-2xl p-6 sm:p-8 shadow-2xl min-h-[calc(100vh-8rem)] space-y-6">
                        {{ $slot }}
                    </div>
                </main>
            </div>
        </div>

        @stack('modals')

        @livewireScripts
        @stack('scripts')
    </body>
</html>
