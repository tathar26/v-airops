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
            
            /* Light Theme Overrides */
            body.theme-light {
                color: #1a202c !important;
            }
            body.theme-light .text-white:not(.bg-tenant-accent), 
            body.theme-light .text-gray-200, 
            body.theme-light .text-gray-300, 
            body.theme-light .text-gray-400 {
                color: #1a202c !important;
            }
            body.theme-light .bg-\[\#212631\],
            body.theme-light .bg-vops-dark {
                background-color: #ffffff !important;
                border-color: #cbd5e1 !important;
                color: #1a202c !important;
            }
            body.theme-light .bg-\[\#2c323f\] {
                background-color: #f1f5f9 !important;
                color: #1a202c !important;
            }
            body.theme-light .border-\[\#3f475a\] {
                border-color: #cbd5e1 !important;
            }
            body.theme-light .bg-white\/5 {
                background-color: rgba(0, 0, 0, 0.05) !important;
            }
            body.theme-light .hover\:bg-white\/5:hover {
                background-color: rgba(0, 0, 0, 0.1) !important;
            }
            body.theme-light .hover\:text-white:hover {
                color: #000000 !important;
            }
            body.theme-light .border-white\/5,
            body.theme-light .border-white\/10 {
                border-color: rgba(0, 0, 0, 0.15) !important;
            }
            body.theme-light .divide-white\/5 > :not([hidden]) ~ :not([hidden]) {
                border-color: rgba(0, 0, 0, 0.1) !important;
            }
            body.theme-light .glass-panel {
                background-color: rgba(255, 255, 255, 0.7) !important;
                border-color: rgba(0, 0, 0, 0.1) !important;
            }
            body.theme-light input[type="text"],
            body.theme-light input[type="email"],
            body.theme-light input[type="password"],
            body.theme-light select,
            body.theme-light input[type="file"] {
                color: #1a202c !important;
            }
            body.theme-light .bg-gray-700 {
                background-color: #cbd5e1 !important;
            }
            body.theme-light .text-gray-500 {
                color: #4a5568 !important;
            }
            body.theme-light .bg-black\/20 {
                background-color: rgba(255, 255, 255, 0.5) !important;
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

                        <div class="text-right hidden sm:block border-l border-white/10 pl-6">
                            <div class="text-sm font-semibold text-tenant-accent">{{ Auth::user()->full_name }}</div>
                            <div class="text-xs text-gray-400 flex items-center justify-end gap-1.5 font-mono">
                                <span class="text-sky-400 font-bold">{{ Auth::user()->activeCallsign() }}</span>
                                <span>&bull;</span>
                                <span class="text-gray-300">{{ Auth::user()->active_rank_name }}</span>
                            </div>
                        </div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="text-gray-400 hover:text-white transition-colors">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                            </button>
                        </form>
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
