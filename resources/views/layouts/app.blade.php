<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'V-Air Ops') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <!-- Styles -->
        @livewireStyles
        @php
            /* ── Tenant colour computation ────────────────────────── */
            $activeTenant = null;
            if (auth()->check()) {
                $activeTenantId = session('active_airline_id', auth()->user()->tenant_id);
                if ($activeTenantId) {
                    $activeTenant = \App\Models\Tenant::find($activeTenantId);
                }
                if (!$activeTenant) {
                    $activeTenant = auth()->user()->tenant;
                }
            }
            $tenantAccent   = $activeTenant && $activeTenant->accent_color ? $activeTenant->accent_color : '#21A19D';
            $tenantBg       = $activeTenant && $activeTenant->bg_color ? $activeTenant->bg_color : '#0A1835';
            $tenantPanelBg  = $activeTenant && $activeTenant->panel_bg_color ? $activeTenant->panel_bg_color : $tenantAccent;
            $tenantCardBg   = $activeTenant && $activeTenant->card_bg_color ? $activeTenant->card_bg_color : $tenantBg;
            $tenantCardText = $activeTenant && $activeTenant->card_text_color ? $activeTenant->card_text_color : null;
            $tenantCardMuted = $activeTenant && $activeTenant->card_muted_text_color ? $activeTenant->card_muted_text_color : null;
            $tenantPanelText = $activeTenant && $activeTenant->panel_text_color ? $activeTenant->panel_text_color : null;

            $tenantBtnBg       = $activeTenant && $activeTenant->button_bg_color ? $activeTenant->button_bg_color : $tenantAccent;
            $tenantBtnText     = $activeTenant && $activeTenant->button_text_color ? $activeTenant->button_text_color : null;
            $tenantBtnSecBg    = $activeTenant && $activeTenant->button_secondary_bg_color ? $activeTenant->button_secondary_bg_color : '#1f2937';
            $tenantBtnSecText  = $activeTenant && $activeTenant->button_secondary_text_color ? $activeTenant->button_secondary_text_color : '#f3f4f6';

            $tenantInputBg     = $activeTenant && $activeTenant->input_bg_color ? $activeTenant->input_bg_color : '#0a0d14';
            $tenantInputText   = $activeTenant && $activeTenant->input_text_color ? $activeTenant->input_text_color : '#ffffff';
            $tenantInputBorder = $activeTenant && $activeTenant->input_border_color ? $activeTenant->input_border_color : '#374151';

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
            $panelLuminance  = preg_match('/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/', $tenantPanelBg)
                                 ? $hexLuminance($tenantPanelBg) : 0;
            $cardLuminance   = preg_match('/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/', $tenantCardBg)
                                 ? $hexLuminance($tenantCardBg) : 0;
            $accentLuminance = preg_match('/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/', $tenantAccent)
                                 ? $hexLuminance($tenantAccent) : 0;

            $isLight              = $bgLuminance > 0.6;
            $isPanelLight         = $panelLuminance > 0.55;
            $isCardLight          = $cardLuminance > 0.55;

            if (!$tenantPanelText) {
                $tenantPanelText = $isPanelLight ? '#0f172a' : '#ffffff';
            }
            if (!$tenantCardText) {
                $tenantCardText  = $isCardLight ? '#0f172a' : '#f8fafc';
            }
            if (!$tenantCardMuted) {
                $tenantCardMuted = $isCardLight ? '#475569' : '#94a3b8';
            }

            $isAccentLight        = $accentLuminance > 0.55;
            $accentTextColor      = $isAccentLight ? '#0f172a' : '#ffffff';
            $accentMutedColor     = $isAccentLight ? 'rgba(15, 23, 42, 0.75)' : 'rgba(255, 255, 255, 0.8)';
            $accentHoverBg        = $isAccentLight ? 'rgba(0, 0, 0, 0.08)' : 'rgba(255, 255, 255, 0.15)';
            $accentActiveBg       = $isAccentLight ? 'rgba(0, 0, 0, 0.18)' : 'rgba(0, 0, 0, 0.28)';
            $accentBorderColor    = $isAccentLight ? 'rgba(0, 0, 0, 0.15)' : 'rgba(0, 0, 0, 0.25)';

            if (!$tenantBtnText) {
                $btnLuminance  = preg_match('/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/', $tenantBtnBg) ? $hexLuminance($tenantBtnBg) : 0;
                $tenantBtnText = $btnLuminance > 0.55 ? '#0f172a' : '#ffffff';
            }

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
                --tenant-panel-bg:           {{ $tenantPanelBg }};
                --tenant-panel-text:         {{ $tenantPanelText }};
                --tenant-card-bg:            {{ $tenantCardBg }};
                --tenant-card-text:          {{ $tenantCardText }};
                --tenant-card-muted:         {{ $tenantCardMuted }};

                /* Buttons */
                --tenant-btn-bg:             {{ $tenantBtnBg }};
                --tenant-btn-text:           {{ $tenantBtnText }};
                --tenant-btn-sec-bg:         {{ $tenantBtnSecBg }};
                --tenant-btn-sec-text:       {{ $tenantBtnSecText }};

                /* Form Controls / Inputs */
                --tenant-input-bg:           {{ $tenantInputBg }};
                --tenant-input-text:         {{ $tenantInputText }};
                --tenant-input-border:       {{ $tenantInputBorder }};

                /* Sidebar / Topbar shell — follows VA accent color */
                --sidebar-bg:                var(--tenant-accent);
                --sidebar-border:            var(--tenant-accent-border);
                --sidebar-text:              var(--tenant-accent-muted);
                --sidebar-text-hover:        var(--tenant-accent-text);
                --sidebar-active-bg:         var(--tenant-accent-active-bg);
                --sidebar-active-text:       var(--tenant-accent-text);

                /* Content cards */
                --card-bg:                   var(--tenant-card-bg);
                --card-header-bg:            var(--tenant-card-bg);
                --card-body-bg:              var(--tenant-card-bg);
                --card-border:               rgba(255, 255, 255, 0.12);
                --card-row-hover:            rgba(255, 255, 255, 0.05);
            }

            /* ── Base body background ─────────────────────────────── */
            body {
                background-color: var(--tenant-bg) !important;
            }

            /* ── Tenant utility classes ───────────────────────────── */
            .text-tenant-accent   { color: var(--tenant-accent) !important; }
            .bg-tenant-accent     { background-color: var(--tenant-btn-bg) !important; color: var(--tenant-btn-text) !important; }
            .border-tenant-accent { border-color: var(--tenant-accent) !important; }
            .ring-tenant-accent   { --tw-ring-color: var(--tenant-accent) !important; }

            /* ── Primary & Secondary Buttons follow Button Color Settings ─ */
            .btn-primary,
            button.bg-tenant-accent,
            a.bg-tenant-accent {
                background-color: var(--tenant-btn-bg) !important;
                color: var(--tenant-btn-text) !important;
            }
            .btn-primary:hover,
            button.bg-tenant-accent:hover,
            a.bg-tenant-accent:hover {
                filter: brightness(0.92);
            }

            .btn-secondary,
            button.bg-slate-800,
            a.bg-slate-800,
            .bg-slate-800 {
                background-color: var(--tenant-btn-sec-bg) !important;
                color: var(--tenant-btn-sec-text) !important;
            }
            .btn-secondary:hover,
            button.bg-slate-800:hover,
            a.bg-slate-800:hover {
                filter: brightness(0.92);
            }

            /* ── Form Control & Text Field Styling (Follows VA Settings) ─ */
            input[type="text"],
            input[type="number"],
            input[type="email"],
            input[type="password"],
            input[type="time"],
            input[type="date"],
            select,
            textarea,
            .glass-input,
            .bg-\[\#212631\],
            .bg-\[\#1e2532\],
            .bg-\[\#0a0d14\],
            .bg-\[\#111827\],
            .bg-black\/40 {
                background-color: var(--tenant-input-bg) !important;
                color: var(--tenant-input-text) !important;
                border-color: var(--tenant-input-border) !important;
            }

            /* Sub-panels inside cards */
            .bg-\[\#141a24\] {
                background-color: var(--tenant-card-bg) !important;
                border: 1px solid var(--tenant-input-border) !important;
            }

            /* ── Master Back Card & Inner Cards (Controlled by VA Settings) ─ */
            .va-main-panel,
            .bg-\[\#181D29\]\/80 {
                background-color: var(--tenant-panel-bg) !important;
                color: var(--tenant-panel-text) !important;
                border: 1px solid rgba(255, 255, 255, 0.15) !important;
                box-shadow: 0 20px 35px -10px rgba(0, 0, 0, 0.35);
            }
            .va-main-panel h1,
            .va-main-panel h2,
            .va-main-panel h3,
            .va-main-panel h4 {
                color: var(--tenant-panel-text) !important;
            }

            /* Inner cards and headers across all views */
            .va-card,
            .va-card-body,
            .va-card-header,
            .glass-panel,
            .va-main-panel .va-card,
            .va-main-panel .va-card-body,
            .va-main-panel .va-card-header,
            .va-main-panel .bg-\[\#12161F\],
            .va-main-panel .bg-\[\#181D29\],
            .va-main-panel .bg-\[\#0d111a\],
            .va-main-panel .bg-\[\#0d131f\],
            .va-main-panel .bg-\[\#090d15\],
            .va-main-panel .bg-\[\#1C212E\],
            .va-main-panel .bg-\[\#0f172a\],
            .va-main-panel .bg-\[\#2c323f\],
            .bg-\[\#12161F\],
            .bg-\[\#181D29\],
            .bg-\[\#0d111a\],
            .bg-\[\#0d131f\],
            .bg-\[\#090d15\],
            .bg-\[\#1C212E\],
            .bg-\[\#0f172a\],
            .bg-\[\#2c323f\] {
                background-color: var(--tenant-card-bg) !important;
            }

            .va-card,
            .glass-panel {
                border: 1px solid rgba(255, 255, 255, 0.12) !important;
                box-shadow: 0 10px 20px -5px rgba(0, 0, 0, 0.3);
            }

            .va-card-header {
                border-bottom: 1px solid rgba(255, 255, 255, 0.12) !important;
                border-top: 2px solid var(--tenant-accent) !important;
            }

            .va-main-panel thead,
            .va-main-panel thead tr,
            thead.bg-\[\#181D29\],
            tbody.bg-\[\#12161F\] {
                background-color: var(--tenant-card-bg) !important;
            }

            /* ── Global Typography & Contrast Hierarchy ────────────────── */
            .va-main-panel h1,
            .va-main-panel h2,
            .va-main-panel h3,
            .va-main-panel h4 {
                color: var(--tenant-panel-text) !important;
            }

            .va-card,
            .va-card-body,
            .glass-panel,
            .va-main-panel .va-card,
            .va-main-panel .va-card-body,
            .va-main-panel .glass-panel,
            .va-table td,
            table.va-table td,
            .va-card table td,
            .va-main-panel table td {
                color: var(--tenant-card-text) !important;
            }

            .va-card h1, .va-card h2, .va-card h3, .va-card h4, .va-card h5, .va-card h6,
            .va-card-header h1, .va-card-header h2, .va-card-header h3, .va-card-header h4,
            .glass-panel h1, .glass-panel h2, .glass-panel h3, .glass-panel h4,
            .va-card .text-white,
            .va-card-header .text-white,
            .glass-panel .text-white,
            .va-table .text-white,
            .va-main-panel .text-white,
            .va-card .text-gray-300,
            .va-card .text-gray-200,
            .va-card .text-slate-300,
            .va-card .text-slate-200,
            .va-main-panel .text-gray-300,
            .va-main-panel .text-slate-300,
            .va-card .font-bold.text-white,
            .va-card-header .font-bold,
            .va-card label,
            .glass-panel label {
                color: var(--tenant-card-text) !important;
            }

            /* Subtitles, muted metadata, table column headers, and timestamps */
            .va-card .text-slate-400,
            .va-card .text-slate-500,
            .va-card .text-slate-600,
            .va-card .text-gray-400,
            .va-card .text-gray-500,
            .va-card .text-gray-600,
            .va-card-header .text-slate-400,
            .va-card-header .text-gray-400,
            .glass-panel .text-gray-400,
            .glass-panel .text-slate-400,
            .va-main-panel .text-slate-400,
            .va-main-panel .text-gray-400,
            .va-main-panel .text-slate-500,
            .va-main-panel .text-gray-500,
            .va-main-panel thead th,
            .va-card thead th,
            .va-table thead th,
            table thead th {
                color: var(--tenant-card-muted) !important;
            }

            /* ── Dispatch Console & Avionics Cards */
            .dispatch-console {
                color: var(--tenant-card-text, #ffffff);
            }
            .dispatch-console .va-card,
            .dispatch-console .dispatch-card {
                background-color: var(--tenant-card-bg, #12161F);
                color: var(--tenant-card-text, #ffffff);
                border-color: var(--tenant-input-border, rgba(255,255,255,0.1));
            }
            .dispatch-console .text-tenant-main {
                color: var(--tenant-card-text, #ffffff) !important;
            }
            .dispatch-console .text-tenant-muted {
                color: var(--tenant-card-muted, #94a3b8) !important;
            }

            /* Dedicated Raw Avionics Terminals (always dark for realistic flight deck displays) */
            .avionics-terminal {
                background-color: #090C12 !important;
                color: #e2e8f0 !important;
                border-color: rgba(255,255,255,0.12) !important;
            }
            .avionics-terminal pre,
            .avionics-terminal code {
                color: #4ade80 !important;
            }
        </style>
    </head>
    <body class="font-sans antialiased selection:bg-tenant-accent selection:text-white {{ $isLight ? 'theme-light' : '' }}">
        <x-banner />

        <div class="min-h-screen flex w-full" x-data="{ mobileNavOpen: false }">
            <!-- Mobile Off-Canvas Navigation Drawer -->
            <div x-show="mobileNavOpen"
                 class="relative z-50 md:hidden"
                 style="display: none;"
                 role="dialog"
                 aria-modal="true">
                
                <!-- Backdrop overlay -->
                <div x-show="mobileNavOpen"
                     x-transition:enter="transition-opacity ease-linear duration-200"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="transition-opacity ease-linear duration-150"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     @click="mobileNavOpen = false"
                     class="fixed inset-0 bg-black/80 backdrop-blur-sm"></div>

                <div class="fixed inset-0 flex">
                    <!-- Drawer Content Panel -->
                    <div x-show="mobileNavOpen"
                         x-transition:enter="transition ease-in-out duration-250 transform"
                         x-transition:enter-start="-translate-x-full"
                         x-transition:enter-end="translate-x-0"
                         x-transition:leave="transition ease-in-out duration-200 transform"
                         x-transition:leave-start="translate-x-0"
                         x-transition:leave-end="-translate-x-full"
                         class="relative mr-16 flex w-full max-w-xs flex-1 flex-col sidebar-shell shadow-2xl">
                        
                        <!-- Close button -->
                        <div class="absolute right-0 top-0 -mr-12 pt-3">
                            <button type="button" @click="mobileNavOpen = false" class="flex h-10 w-10 items-center justify-center rounded-full text-white hover:bg-white/10 focus:outline-none" aria-label="Close Navigation">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>

                        <!-- Brand Header in Mobile Drawer -->
                        <div class="px-5 pt-5 pb-3 flex items-center justify-between border-b border-black/20">
                            @if (auth()->check() && auth()->user()->tenant && auth()->user()->tenant->logo_path)
                                <img src="{{ Storage::url(auth()->user()->tenant->logo_path) }}" alt="VA Logo" class="h-8 w-auto object-contain">
                            @else
                                <div class="flex items-center gap-2.5">
                                    <img src="{{ asset('images/v-air-ops-mark.png') }}" alt="V-Air Ops" class="h-7 w-auto object-contain">
                                    <div class="font-bold text-xs uppercase tracking-widest leading-tight text-inherit">
                                        {{ auth()->check() && auth()->user()->tenant ? auth()->user()->tenant->name : 'V-Air Ops' }}
                                    </div>
                                </div>
                            @endif
                        </div>

                        <!-- Navigation Items -->
                        <div class="flex-1 overflow-y-auto" @click="if ($event.target.closest('a')) mobileNavOpen = false">
                            @livewire('navigation-menu')
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar — always-dark shell, independent of tenant theme -->
            <div class="w-56 flex-shrink-0 sidebar-shell hidden md:flex flex-col">
                @livewire('navigation-menu')
            </div>

            <!-- Main Content Area -->
            <div class="flex-grow flex flex-col min-w-0">
                <!-- Top Navigation Bar -->
                <header class="h-14 topbar-shell flex items-center justify-between px-4 sm:px-6 flex-shrink-0">
                    <!-- Mobile: hamburger + logo / VA name -->
                    <div class="md:hidden flex items-center gap-2.5">
                        <button @click="mobileNavOpen = true" type="button" class="p-1.5 rounded-lg border border-black/20 bg-black/20 text-inherit hover:bg-black/30 transition focus:outline-none" aria-label="Open Navigation Menu">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                            </svg>
                        </button>
                        @if (auth()->check() && auth()->user()->tenant && auth()->user()->tenant->logo_path)
                            <img src="{{ Storage::url(auth()->user()->tenant->logo_path) }}" alt="VA Logo" class="block h-7 w-auto object-contain">
                        @else
                            <h1 class="text-sm font-bold tracking-wider uppercase truncate max-w-[140px] xs:max-w-[200px]">
                                {{ auth()->check() && auth()->user()->tenant ? auth()->user()->tenant->name : 'V-Air Ops' }}
                            </h1>
                        @endif
                    </div>

                    <!-- Desktop: VA name (sidebar has brand, topbar shows page context) -->
                    <div class="hidden md:flex items-center">
                        <h1 class="text-sm font-bold tracking-widest uppercase">
                            {{ auth()->check() && auth()->user()->tenant ? auth()->user()->tenant->name : 'V-Air Ops' }}
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
                    <div class="va-main-panel max-w-[1600px] mx-auto w-full backdrop-blur-xl border border-white/10 rounded-2xl p-6 sm:p-8 shadow-2xl min-h-[calc(100vh-8rem)] space-y-6" style="background-color: var(--tenant-panel-bg) !important; color: var(--tenant-panel-text) !important;">
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
