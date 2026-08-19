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
        <style>
            :root {
                --tenant-accent: {{ auth()->check() && auth()->user()->tenant ? auth()->user()->tenant->accent_color : '#f97316' }};
                --tenant-bg: {{ auth()->check() && auth()->user()->tenant && auth()->user()->tenant->bg_color ? auth()->user()->tenant->bg_color : '#1e1e1e' }};
            }
            .text-tenant-accent {
                color: var(--tenant-accent) !important;
            }
            .bg-tenant-accent {
                background-color: var(--tenant-accent) !important;
                color: #ffffff !important;
            }
            .border-tenant-accent {
                border-color: var(--tenant-accent) !important;
            }
            body {
                background-color: var(--tenant-bg) !important;
            }
            
            /* Clean Scoped Light Theme Styling */
            body.theme-light {
                color: #1e293b;
            }
            body.theme-light .glass-panel {
                background-color: rgba(255, 255, 255, 0.92) !important;
                border-color: rgba(0, 0, 0, 0.08) !important;
                color: #1e293b !important;
                box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.05) !important;
            }
            body.theme-light .glass-panel h1,
            body.theme-light .glass-panel h2,
            body.theme-light .glass-panel h3,
            body.theme-light .glass-panel h4 {
                color: #0f172a !important;
            }
            body.theme-light .glass-panel .text-white:not(.bg-tenant-accent):not(.bg-vops-primary):not(.bg-emerald-600):not(.bg-red-600) {
                color: #0f172a !important;
            }
            body.theme-light .glass-panel .text-gray-200,
            body.theme-light .glass-panel .text-gray-300 {
                color: #334155 !important;
            }
            body.theme-light .glass-panel .text-gray-400 {
                color: #475569 !important;
            }
            body.theme-light .glass-panel .text-gray-500 {
                color: #64748b !important;
            }
            body.theme-light .glass-panel .border-white\/5,
            body.theme-light .glass-panel .border-white\/10 {
                border-color: rgba(0, 0, 0, 0.08) !important;
            }
            body.theme-light .glass-panel .divide-white\/5 > :not([hidden]) ~ :not([hidden]) {
                border-color: rgba(0, 0, 0, 0.08) !important;
            }
            body.theme-light .glass-panel .bg-white\/5 {
                background-color: rgba(0, 0, 0, 0.03) !important;
            }
            body.theme-light .glass-panel .hover\:bg-white\/5:hover {
                background-color: rgba(0, 0, 0, 0.06) !important;
            }
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
            body.theme-light .glass-panel .bg-\[\#212631\],
            body.theme-light .glass-panel .bg-vops-dark {
                background-color: #f1f5f9 !important;
                border-color: #cbd5e1 !important;
                color: #0f172a !important;
            }
            body.theme-light .glass-panel .bg-black\/40,
            body.theme-light .glass-panel .bg-black\/20 {
                background-color: rgba(0, 0, 0, 0.03) !important;
            }
            body.theme-light .bg-\[\#2c323f\] {
                background-color: #f8fafc !important;
                border-color: #e2e8f0 !important;
                color: #0f172a !important;
            }
            body.theme-light .bg-\[\#212631\] {
                background-color: #ffffff !important;
                border-color: #e2e8f0 !important;
                color: #0f172a !important;
            }
            body.theme-light .border-\[\#3f475a\] {
                border-color: #cbd5e1 !important;
            }
            body.theme-light nav .text-gray-400 {
                color: #475569 !important;
            }
            body.theme-light nav .text-gray-500 {
                color: #64748b !important;
            }
            body.theme-light nav a:hover {
                color: #0f172a !important;
                background-color: rgba(0, 0, 0, 0.05) !important;
            }

            /* Dedicated Dark Avionics Cards & Dispatch Console Protection */
            .dispatch-console,
            .dark-card {
                color: #ffffff !important;
            }
            .dispatch-console .text-white,
            .dark-card .text-white {
                color: #ffffff !important;
            }
            .dispatch-console .text-gray-200,
            .dark-card .text-gray-200 {
                color: #e2e8f0 !important;
            }
            .dispatch-console .text-gray-300,
            .dark-card .text-gray-300 {
                color: #cbd5e1 !important;
            }
            .dispatch-console .text-gray-400,
            .dark-card .text-gray-400 {
                color: #94a3b8 !important;
            }
            .dispatch-console .text-gray-500,
            .dark-card .text-gray-500 {
                color: #64748b !important;
            }
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
            .dispatch-console select option,
            .dark-card select option {
                background-color: #1e293b !important;
                color: #ffffff !important;
            }
            .dispatch-console input::placeholder,
            .dark-card input::placeholder {
                color: #64748b !important;
            }
        </style>
    </head>
    @php
        $bgColor = auth()->check() && auth()->user()->tenant && auth()->user()->tenant->bg_color ? auth()->user()->tenant->bg_color : '#1e1e1e';
        $isLight = false;
        if (preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $bgColor)) {
            $hex = str_replace('#', '', $bgColor);
            if(strlen($hex) == 3) {
                $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
            }
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
            $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
            $isLight = $luminance > 0.6;
        }
    @endphp
    <body class="font-sans antialiased selection:bg-tenant-accent selection:text-white {{ $isLight ? 'theme-light' : 'text-gray-300' }}">
        <x-banner />

        <div class="min-h-screen flex w-full">
            <!-- Sidebar -->
            <div class="w-64 flex-shrink-0 bg-black/20 border-r border-white/5 hidden md:flex flex-col">
                @livewire('navigation-menu')
            </div>

            <!-- Main Content Area -->
            <div class="flex-grow flex flex-col min-w-0">
                <!-- Top Nav (Mobile Hamburger & User Profile) -->
                <header class="h-16 bg-black/20 border-b border-white/5 flex items-center justify-between px-4 sm:px-6">
                    <div class="md:hidden flex items-center">
                        @if (auth()->check() && auth()->user()->tenant && auth()->user()->tenant->logo_path)
                            <img src="{{ Storage::url(auth()->user()->tenant->logo_path) }}" alt="VA Logo" class="block h-9 w-auto mr-4 object-contain">
                        @else
                            <x-application-mark class="block h-9 w-auto mr-4" />
                            <h1 class="text-xl font-bold tracking-widest text-tenant-accent uppercase">
                                {{ auth()->check() && auth()->user()->tenant ? auth()->user()->tenant->name : 'V-Ops' }}
                            </h1>
                        @endif
                    </div>
                    <div class="hidden md:flex items-center">
                         <h1 class="text-xl font-bold tracking-widest text-tenant-accent uppercase">
                            {{ auth()->check() && auth()->user()->tenant ? auth()->user()->tenant->name : 'V-Ops' }}
                        </h1>
                    </div>
                    
                    <!-- User Profile Dropdown snippet -->
                    <div class="flex items-center gap-6">
                        <!-- Zulu Clock -->
                        <div x-data="{ time: '' }" x-init="setInterval(() => { let d = new Date(); time = d.toISOString().substring(11,19) + 'z'; }, 1000)" class="hidden sm:flex items-center gap-2 text-gray-400 text-sm font-mono" title="Zulu Time (UTC)">
                            <svg class="w-4 h-4 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path></svg>
                            <span x-text="time"></span>
                        </div>

                        @php
                            $user = Auth::user();
                            $userAirlines = $user ? $user->userAirlines()->with('tenant')->get() : collect();
                            $activeTenantId = session('active_airline_id', $user->tenant_id ?? null);
                        @endphp

                        <!-- User Profile & Airline Quick Switcher Dropdown (Hover/Click) -->
                        <div class="relative" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false">
                            <button @click="open = !open" type="button" class="flex items-center gap-3 px-3 py-1.5 rounded-xl border border-white/10 hover:border-white/20 bg-white/5 hover:bg-white/10 transition-all text-right group focus:outline-none">
                                <div>
                                    <div class="text-sm font-semibold text-tenant-accent flex items-center justify-end gap-2">
                                        <span>{{ $user->full_name }}</span>
                                        @if($user->hasRole('Master Admin'))
                                            <span class="bg-purple-500/20 text-purple-300 border border-purple-500/30 text-[10px] font-bold px-1.5 py-0.5 rounded uppercase tracking-wider">System Admin</span>
                                        @elseif($user->hasRole('VA Owner'))
                                            <span class="bg-amber-500/20 text-amber-300 border border-amber-500/30 text-[10px] font-bold px-1.5 py-0.5 rounded uppercase tracking-wider">VA Owner</span>
                                        @endif
                                    </div>
                                    <div class="text-xs text-gray-400 flex items-center justify-end gap-1.5 font-mono">
                                        <span class="text-sky-400 font-bold">{{ $user->activeCallsign() }}</span>
                                        <span>&bull;</span>
                                        <span class="text-gray-300">{{ $user->active_rank_name }}</span>
                                    </div>
                                </div>

                                <svg class="w-4 h-4 text-gray-400 group-hover:text-white transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>

                            <!-- Dropdown Menu -->
                            <div x-show="open" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="transform opacity-0 scale-95" x-transition:enter-end="transform opacity-100 scale-100" x-transition:leave="transition ease-in duration-100" x-transition:leave-start="transform opacity-100 scale-100" x-transition:leave-end="transform opacity-0 scale-95"
                                class="absolute right-0 mt-2 w-80 rounded-2xl bg-slate-900/95 border border-slate-700 shadow-2xl backdrop-blur-xl z-50 overflow-hidden divide-y divide-slate-800" style="display: none;">
                                
                                <!-- User Summary Header -->
                                <div class="p-4 bg-slate-950/70">
                                    <p class="text-xs font-bold text-white">{{ $user->full_name }}</p>
                                    <p class="text-[11px] text-gray-400 truncate">{{ $user->email }}</p>
                                </div>

                                <!-- Quick Access: Enrolled Virtual Airlines -->
                                <div class="p-3 bg-slate-950/40">
                                    <div class="flex items-center justify-between mb-2 px-1">
                                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">My Virtual Airlines</span>
                                        <span class="text-[10px] text-slate-500 font-mono">{{ $userAirlines->count() }} Joined</span>
                                    </div>

                                    <div class="space-y-1 max-h-48 overflow-y-auto">
                                        @forelse ($userAirlines as $ua)
                                            @php
                                                $isActive = ($activeTenantId == $ua->tenant_id);
                                            @endphp
                                            <form action="{{ route('session.switch-airline') }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="tenant_id" value="{{ $ua->tenant_id }}">
                                                <button type="submit" class="w-full text-left p-2 rounded-xl flex items-center justify-between transition-colors group {{ $isActive ? 'bg-sky-500/10 border border-sky-500/30' : 'hover:bg-slate-800/80 border border-transparent' }}">
                                                    <div class="flex items-center gap-2.5">
                                                        <div class="w-7 h-7 rounded-lg bg-slate-950 border border-slate-800 flex items-center justify-center text-xs font-bold text-sky-400 font-mono">
                                                            {{ strtoupper($ua->tenant->icao ?? 'VA') }}
                                                        </div>
                                                        <div>
                                                            <p class="text-xs font-bold {{ $isActive ? 'text-sky-300' : 'text-white group-hover:text-sky-400' }} transition-colors">
                                                                {{ $ua->tenant->name }}
                                                            </p>
                                                            <p class="text-[10px] text-slate-400 font-mono">{{ $ua->callsign }} &bull; {{ $ua->rank ?? 'Cadet' }}</p>
                                                        </div>
                                                    </div>

                                                    @if ($isActive)
                                                        <span class="inline-flex items-center gap-1 text-[10px] text-emerald-400 font-semibold px-2 py-0.5 rounded-full bg-emerald-500/20">
                                                            <span>Active</span>
                                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                                        </span>
                                                    @else
                                                        <span class="text-[10px] text-gray-500 group-hover:text-white transition-colors">Switch &rarr;</span>
                                                    @endif
                                                </button>
                                            </form>
                                        @empty
                                            <p class="text-xs text-gray-500 py-2 px-1">No virtual airlines enrolled yet.</p>
                                        @endforelse
                                    </div>
                                </div>

                                <!-- Actions & Links -->
                                <div class="p-2 space-y-1 bg-slate-950/60 text-xs">
                                    <a href="{{ route('onboarding.select-airline') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-sky-400 hover:text-sky-300 hover:bg-sky-500/10 transition-colors font-medium">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                        Join / Create Virtual Airline
                                    </a>

                                    <a href="{{ route('profile.account') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-gray-300 hover:text-white hover:bg-white/5 transition-colors">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                        Account Settings
                                    </a>

                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="w-full text-left flex items-center gap-2.5 px-3 py-2 rounded-xl text-red-400 hover:text-red-300 hover:bg-red-500/10 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
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
                    {{ $slot }}
                </main>
            </div>
        </div>

        @stack('modals')

        @livewireScripts
        @stack('scripts')
    </body>
</html>
