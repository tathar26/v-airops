<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <x-google-analytics />

    <title>{{ config('app.name', 'V-Air Ops') }} &bull; Virtual Airline Management System</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">

    <!-- Alpine.js & Tailwind -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        mono: ['JetBrains Mono', 'monospace'],
                        heading: ['Plus Jakarta Sans', 'sans-serif'],
                    },
                    colors: {
                        navy: {
                            950: '#060E22',
                            900: '#0A1835',
                            800: '#0F224A',
                            700: '#142954',
                        },
                        teal: {
                            500: '#21A19D',
                            600: '#1C8C88',
                            700: '#177673',
                        },
                        purple: {
                            500: '#6F3B84',
                            600: '#5B306D',
                        },
                        vops: {
                            navy: '#0A1835',
                            'navy-dark': '#060E22',
                            'navy-light': '#0F224A',
                            dark: '#0A1835',
                            card: '#0F224A',
                            teal: '#21A19D',
                            accent: '#21A19D',
                            primary: '#21A19D',
                            secondary: '#6F3B84',
                            purple: '#6F3B84',
                            success: '#10B981',
                        }
                    }
                }
            }
        }
    </script>

    <style>
        body {
            background-color: #0A1835;
            color: #f1f5f9;
        }

        .flat-card {
            background-color: #0F224A;
            border: 1px solid #142954;
        }

        .flat-card-hover {
            transition: background-color 0.2s ease, border-color 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease;
        }
        .flat-card-hover:hover {
            background-color: #142954;
            border-color: #21A19D;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px -10px rgba(0, 0, 0, 0.4);
        }
    </style>
</head>
<body class="bg-navy-900 text-slate-100 antialiased overflow-x-hidden selection:bg-[#21A19D] selection:text-white">

    <!-- Navigation Header -->
    <header class="sticky top-0 z-50 bg-navy-950 border-b border-navy-700 transition-all" x-data="{ mobileMenuOpen: false }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
            <!-- Brand Logo -->
            <a href="/" class="flex items-center gap-3 group">
                <img src="{{ asset('images/v-air-ops-logo-cropped.png') }}" alt="V-Air Ops" class="h-9 sm:h-11 w-auto object-contain transition duration-200 group-hover:opacity-90">
            </a>

            <!-- Desktop Nav Links -->
            <nav class="hidden md:flex items-center gap-8 text-sm font-medium text-slate-300">
                <a href="#features" class="hover:text-[#21A19D] transition">Features</a>
                <a href="#dispatch" class="hover:text-[#21A19D] transition">Dispatch Integration</a>
                <a href="#portal" class="hover:text-[#21A19D] transition">Pilot Roster</a>
                <a href="#tracking" class="hover:text-[#21A19D] transition">ACARS Tracking</a>
                <a href="#architecture" class="hover:text-[#21A19D] transition">System Architecture</a>
            </nav>

            <!-- Desktop Authentication / CTAs -->
            <div class="hidden md:flex items-center gap-4">
                <?php if (auth()->check()): ?>
                    <a href="{{ url('/dashboard') }}" class="px-5 py-2.5 rounded-lg bg-navy-800 hover:bg-navy-700 text-[#21A19D] border border-navy-700 text-xs font-bold font-mono transition">
                        OPERATIONS CONSOLE &rarr;
                    </a>
                <?php else: ?>
                    <a href="{{ route('login') }}" class="text-xs font-bold text-slate-300 hover:text-white px-4 py-2 transition">
                        Sign In
                    </a>
                    <a href="{{ route('register') }}" class="px-5 py-2.5 rounded-lg bg-[#21A19D] hover:bg-[#1C8C88] text-white font-heading font-bold text-xs shadow-md transition">
                        Register Airline
                    </a>
                <?php endif; ?>
            </div>

            <!-- Mobile Actions -->
            <div class="flex items-center gap-2 md:hidden">
                <?php if (auth()->check()): ?>
                    <a href="{{ url('/dashboard') }}" class="px-3 py-1.5 rounded-lg bg-navy-800 text-[#21A19D] border border-navy-700 text-xs font-bold font-mono">
                        Console &rarr;
                    </a>
                <?php else: ?>
                    <a href="{{ route('login') }}" class="px-3 py-1.5 rounded-lg bg-navy-800 text-slate-200 border border-navy-700 text-xs font-semibold">
                        Sign In
                    </a>
                <?php endif; ?>

                <button @click="mobileMenuOpen = !mobileMenuOpen"
                        type="button"
                        class="p-2 rounded-lg bg-navy-900 border border-navy-700 text-slate-300 hover:text-white hover:bg-navy-800 transition focus:outline-none"
                        aria-label="Toggle Navigation">
                    <svg x-show="!mobileMenuOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                    <svg x-show="mobileMenuOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:none;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Mobile Dropdown Navigation Menu -->
        <div x-show="mobileMenuOpen"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-2"
             @click.away="mobileMenuOpen = false"
             class="md:hidden bg-navy-950 border-b border-navy-700 px-4 pt-3 pb-6 space-y-4 shadow-2xl"
             style="display: none;">
            
            <nav class="flex flex-col space-y-1 text-sm font-medium text-slate-200">
                <a href="#features" @click="mobileMenuOpen = false" class="px-3 py-2.5 rounded-lg hover:bg-navy-900 hover:text-[#21A19D] transition flex items-center justify-between">
                    <span>Features</span>
                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
                <a href="#dispatch" @click="mobileMenuOpen = false" class="px-3 py-2.5 rounded-lg hover:bg-navy-900 hover:text-[#21A19D] transition flex items-center justify-between">
                    <span>Dispatch Integration</span>
                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
                <a href="#portal" @click="mobileMenuOpen = false" class="px-3 py-2.5 rounded-lg hover:bg-navy-900 hover:text-[#21A19D] transition flex items-center justify-between">
                    <span>Pilot Roster</span>
                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
                <a href="#tracking" @click="mobileMenuOpen = false" class="px-3 py-2.5 rounded-lg hover:bg-navy-900 hover:text-[#21A19D] transition flex items-center justify-between">
                    <span>ACARS Tracking</span>
                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
            </nav>

            <div class="pt-3 border-t border-navy-700 flex flex-col gap-2.5">
                <?php if (auth()->check()): ?>
                    <a href="{{ url('/dashboard') }}" class="w-full text-center px-4 py-3 rounded-lg bg-navy-800 text-[#21A19D] border border-navy-700 text-xs font-bold font-mono transition">
                        OPERATIONS CONSOLE &rarr;
                    </a>
                <?php else: ?>
                    <a href="{{ route('login') }}" class="w-full text-center px-4 py-2.5 rounded-lg bg-navy-900 border border-navy-700 text-xs font-bold text-slate-200 hover:text-white transition">
                        Sign In
                    </a>
                    <a href="{{ route('register') }}" class="w-full text-center px-4 py-3 rounded-lg bg-[#21A19D] hover:bg-[#1C8C88] text-white font-heading font-bold text-xs shadow-md transition">
                        Register Airline
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- SECTION 1: HERO SECTION -->
    <section class="relative pt-16 pb-20 lg:pt-24 lg:pb-28 bg-navy-900 border-b border-navy-700 overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="text-center max-w-4xl mx-auto space-y-8">
                
                <!-- Professional Badge -->
                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-lg flat-card text-xs text-slate-300 shadow-sm border border-navy-700">
                    <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                    <span class="font-mono font-medium uppercase tracking-wider text-[11px]">Virtual Airline Operations &amp; Flight Tracking Platform</span>
                </div>

                <!-- Main Heading -->
                <h1 class="font-heading font-black text-4xl sm:text-6xl lg:text-7xl tracking-tight leading-[1.08] text-white">
                    Complete Management System <br class="hidden sm:inline" />
                    <span class="text-[#21A19D]">For Virtual Airlines</span>
                </h1>

                <!-- Subheadline -->
                <p class="text-slate-300 text-lg sm:text-xl max-w-3xl mx-auto leading-relaxed font-normal">
                    V-Air Ops provides schedule management, live ACARS tracking, SimBrief flight plan dispatching, and comprehensive pilot roster tools for virtual aviation operations.
                </p>

                <!-- Call To Actions -->
                <div class="flex flex-wrap items-center justify-center gap-4 pt-4">
                    <a href="{{ route('register') }}" class="px-8 py-4 rounded-xl bg-[#21A19D] hover:bg-[#1C8C88] text-white font-heading font-bold text-base shadow-md transition flex items-center gap-3">
                        <span>Get Started</span>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                    </a>

                    <a href="#features" class="px-8 py-4 rounded-xl bg-navy-800 hover:bg-navy-700 text-slate-200 font-heading font-semibold text-base border border-navy-700 transition flex items-center gap-2">
                        <span>Explore Features</span>
                    </a>
                </div>
            </div>

            <!-- Application Screenshot: Operations Console -->
            <div class="mt-16 lg:mt-20 relative max-w-6xl mx-auto">
                <div class="relative rounded-xl flat-card border border-navy-700 overflow-hidden shadow-2xl group">
                    <div class="bg-navy-950 border-b border-navy-700 px-4 py-3 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-red-500/60"></span>
                            <span class="w-3 h-3 rounded-full bg-yellow-500/60"></span>
                            <span class="w-3 h-3 rounded-full bg-green-500/60"></span>
                            <span class="text-xs font-mono text-slate-400 ml-2">vops-console.app &bull; Operations Dashboard</span>
                        </div>
                        <div class="flex items-center gap-3 text-xs font-mono text-teal-400">
                            <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                            <span>LIVE APP PREVIEW</span>
                        </div>
                    </div>
                    
                    <img src="{{ asset('images/screenshots/console-dashboard.jpg') }}" alt="V-Air Ops Operations Dashboard Screenshot" class="w-full h-auto object-cover rounded-b-xl">
                </div>
            </div>
        </div>
    </section>

    <!-- SECTION 2: SYSTEM CAPABILITIES HIGHLIGHTS -->
    <section class="py-12 bg-navy-950 border-b border-navy-700 relative z-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center divide-x divide-navy-700">
                <div class="space-y-1">
                    <span class="font-heading font-bold text-lg text-[#21A19D] block">Multi-Simulator</span>
                    <span class="text-xs text-slate-400 block font-mono">MSFS, X-Plane &amp; P3D</span>
                </div>
                <div class="space-y-1 pl-4">
                    <span class="font-heading font-bold text-lg text-[#6F3B84] block">SimBrief Integration</span>
                    <span class="text-xs text-slate-400 block font-mono">LIDO OFP Flight Plans</span>
                </div>
                <div class="space-y-1 pl-4">
                    <span class="font-heading font-bold text-lg text-teal-300 block">vPilot ACARS</span>
                    <span class="text-xs text-slate-400 block font-mono">Native Telemetry Client</span>
                </div>
                <div class="space-y-1 pl-4">
                    <span class="font-heading font-bold text-lg text-emerald-400 block">Multi-Tenant</span>
                    <span class="text-xs text-slate-400 block font-mono">Dedicated VA Isolation</span>
                </div>
            </div>
        </div>
    </section>

    <!-- SECTION 3: CORE FEATURES GRID -->
    <section id="features" class="py-20 lg:py-28 bg-navy-900 border-b border-navy-700 relative z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-16">
            
            <div class="text-center max-w-3xl mx-auto space-y-4">
                <span class="text-xs font-mono uppercase tracking-widest text-[#21A19D] font-bold bg-navy-950 px-3 py-1 rounded-md border border-navy-700">
                    VIRTUAL AIRLINE MANAGEMENT SUITE
                </span>
                <h2 class="font-heading font-black text-3xl sm:text-5xl text-white tracking-tight">
                    Essential Tools for Virtual Airline Operations
                </h2>
                <p class="text-slate-400 text-base sm:text-lg">
                    V-Air Ops provides schedule management, pilot administration, automated flight plan dispatch, and flight tracking tailored for virtual aviation communities.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                <!-- Feature 1 -->
                <div id="dispatch" class="flat-card flat-card-hover p-8 rounded-xl space-y-4 relative overflow-hidden group">
                    <div class="w-12 h-12 rounded-lg bg-navy-950 border border-navy-700 flex items-center justify-center text-[#21A19D]">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    </div>
                    <h3 class="font-heading font-bold text-xl text-white">SimBrief Flight Dispatch</h3>
                    <p class="text-slate-400 text-sm leading-relaxed">
                        Integrated SimBrief flight plan generation with official LIDO format layout, route validation, ETOPS calculations, and fuel weight balance.
                    </p>
                </div>

                <!-- Feature 2 -->
                <div id="tracking" class="flat-card flat-card-hover p-8 rounded-xl space-y-4 relative overflow-hidden group">
                    <div class="w-12 h-12 rounded-lg bg-navy-950 border border-navy-700 flex items-center justify-center text-[#6F3B84]">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                    </div>
                    <h3 class="font-heading font-bold text-xl text-white">ACARS Telemetry Engine</h3>
                    <p class="text-slate-400 text-sm leading-relaxed">
                        Lightweight desktop tracking client logging altitude, airspeed, position, fuel flow, and touchdown sink rate across modern flight simulators.
                    </p>
                </div>

                <!-- Feature 3 -->
                <div class="flat-card flat-card-hover p-8 rounded-xl space-y-4 relative overflow-hidden group">
                    <div class="w-12 h-12 rounded-lg bg-navy-950 border border-navy-700 flex items-center justify-center text-teal-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    </div>
                    <h3 class="font-heading font-bold text-xl text-white">Schedule &amp; Fleet Management</h3>
                    <p class="text-slate-400 text-sm leading-relaxed">
                        Import and organize routes, flight numbers, aircraft registration details, airport ICAOs, and active aircraft type assignments.
                    </p>
                </div>

                <!-- Feature 4 -->
                <div id="architecture" class="flat-card flat-card-hover p-8 rounded-xl space-y-4 relative overflow-hidden group">
                    <div class="w-12 h-12 rounded-lg bg-navy-950 border border-navy-700 flex items-center justify-center text-emerald-400">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                    </div>
                    <h3 class="font-heading font-bold text-xl text-white">Multi-Tenant Infrastructure</h3>
                    <p class="text-slate-400 text-sm leading-relaxed">
                        Isolated databases and tenant configuration models, allowing independent management, custom ranks, and dedicated rosters per airline.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- SECTION 4: THE PILOT EXPERIENCE -->
    <section id="portal" class="py-20 lg:py-28 bg-navy-950 border-b border-navy-700 relative z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
                
                <!-- Left Details -->
                <div class="space-y-8">
                    <div class="space-y-4">
                        <span class="text-xs font-mono uppercase tracking-widest text-[#6F3B84] font-bold bg-navy-900 px-3 py-1 rounded-md border border-navy-700">
                            PILOT MANAGEMENT &amp; PROGRESSION
                        </span>
                        <h2 class="font-heading font-black text-3xl sm:text-5xl text-white tracking-tight">
                            Streamlined Roster &amp; Flight Log Verification
                        </h2>
                        <p class="text-slate-400 text-base sm:text-lg leading-relaxed">
                            Manage pilot qualifications, track flight hours, process automated PIREPs, and integrate community Discord announcements.
                        </p>
                    </div>

                    <div class="space-y-6">
                        <!-- Item 1 -->
                        <div class="flex gap-4 items-start">
                            <div class="w-10 h-10 rounded-lg bg-navy-900 border border-navy-700 flex items-center justify-center text-[#21A19D] shrink-0 mt-1">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            </div>
                            <div class="space-y-1">
                                <h4 class="font-heading font-bold text-lg text-white">Automated Rank &amp; Qualification Tracking</h4>
                                <p class="text-slate-400 text-sm">Define custom hour thresholds and rank structures. Ranks and type ratings update automatically as pilots log flight hours.</p>
                            </div>
                        </div>

                        <!-- Item 2 -->
                        <div class="flex gap-4 items-start">
                            <div class="w-10 h-10 rounded-lg bg-navy-900 border border-navy-700 flex items-center justify-center text-[#6F3B84] shrink-0 mt-1">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                            </div>
                            <div class="space-y-1">
                                <h4 class="font-heading font-bold text-lg text-white">Discord Notification Integration</h4>
                                <p class="text-slate-400 text-sm">Synchronize community roles and trigger automated flight dispatch or PIREP completion notices to community Discord webhooks.</p>
                            </div>
                        </div>

                        <!-- Item 3 -->
                        <div class="flex gap-4 items-start">
                            <div class="w-10 h-10 rounded-lg bg-navy-900 border border-navy-700 flex items-center justify-center text-teal-300 shrink-0 mt-1">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                            </div>
                            <div class="space-y-1">
                                <h4 class="font-heading font-bold text-lg text-white">Operational Statistics &amp; Analytics</h4>
                                <p class="text-slate-400 text-sm">Review pilot landing rate performance, total flight hours, aircraft type usage, and route statistics.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Application Screenshot: Pilot Roster & Profile -->
                <div class="flat-card rounded-xl border border-navy-700 overflow-hidden shadow-xl">
                    <div class="bg-navy-950 border-b border-navy-700 px-4 py-2.5 flex items-center justify-between text-xs font-mono text-slate-400">
                        <span class="font-bold text-white">PILOT PROFILE &amp; ROSTER UI</span>
                        <span class="text-teal-400">VERIFIED LOGBOOK</span>
                    </div>
                    <img src="{{ asset('images/screenshots/pilot-portal.jpg') }}" alt="V-Air Ops Pilot Roster & Profile Portal Screenshot" class="w-full h-auto object-cover">
                </div>
            </div>
        </div>
    </section>

    <!-- SECTION 5: REAL-TIME FLIGHT MONITORING -->
    <section class="py-20 lg:py-28 bg-navy-900 border-b border-navy-700 relative z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
            <div class="text-center max-w-3xl mx-auto space-y-4">
                <span class="text-xs font-mono uppercase tracking-widest text-emerald-400 font-bold bg-navy-950 px-3 py-1 rounded-md border border-navy-700">
                    REAL-TIME FLIGHT TRACKING
                </span>
                <h2 class="font-heading font-black text-3xl sm:text-5xl text-white tracking-tight">
                    Live Fleet &amp; Position Tracking
                </h2>
                <p class="text-slate-400 text-base sm:text-lg">
                    Monitor active flights across your virtual airline network with live map tracking and low-latency position updates via the vPilot ACARS client.
                </p>
                <div class="pt-2 flex items-center justify-center gap-4">
                    <a href="{{ route('resources.vpilot-acars') }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-[#21A19D] hover:bg-[#1C8C88] text-white font-heading font-bold text-xs shadow-md transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        <span>Download vPilot ACARS Client</span>
                        <svg class="w-3.5 h-3.5 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    </a>
                </div>
            </div>

            <!-- Application Screenshot: Live ACARS Radar -->
            <div class="flat-card rounded-xl border border-navy-700 overflow-hidden shadow-2xl">
                <div class="bg-navy-950 border-b border-navy-700 p-4 flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <span class="w-3 h-3 rounded-full bg-emerald-400"></span>
                        <span class="font-heading font-bold text-white text-sm">FLIGHT MONITORING CONSOLE &bull; ACARS RADAR UI</span>
                    </div>
                    <div class="flex items-center gap-4 text-xs font-mono text-slate-400">
                        <span>TELEMETRY: <strong class="text-emerald-400">ACTIVE</strong></span>
                    </div>
                </div>

                <img src="{{ asset('images/screenshots/live-radar-map.jpg') }}" alt="V-Air Ops Live ACARS Radar Tracking Screenshot" class="w-full h-auto object-cover">
            </div>
        </div>
    </section>

    <!-- SECTION 6: FINAL CTA -->
    <section class="py-20 lg:py-28 bg-navy-900 relative z-10 overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flat-card border border-navy-700 rounded-2xl p-8 sm:p-14 text-center relative overflow-hidden space-y-6 shadow-xl">
                <h2 class="font-heading font-black text-3xl sm:text-5xl text-white tracking-tight leading-tight">
                    Start Managing Your Virtual Airline
                </h2>
                
                <p class="text-slate-300 text-base sm:text-lg max-w-2xl mx-auto font-normal leading-relaxed">
                    Create your virtual airline or join an existing community on V-Air Ops today.
                </p>

                <div class="flex flex-wrap items-center justify-center gap-4 pt-2">
                    <a href="{{ route('register') }}" class="px-8 py-4 rounded-xl bg-[#21A19D] hover:bg-[#1C8C88] text-white font-heading font-bold text-base shadow-md transition flex items-center gap-3">
                        <span>Register Your Airline</span>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                    </a>
                </div>

                <p class="text-xs font-mono text-slate-400 pt-2">
                    Free platform for virtual aviation communities &bull; Multi-Tenant System
                </p>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="border-t border-navy-700 bg-navy-950 py-12 relative z-20 text-xs text-slate-400">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
            <div class="grid grid-cols-2 md:grid-cols-5 gap-8">
                <!-- Brand Info -->
                <div class="col-span-2 space-y-4">
                    <a href="/" class="inline-block">
                        <img src="{{ asset('images/v-air-ops-logo.png') }}" alt="V-Air Ops" class="h-16 w-auto object-contain">
                    </a>
                    <p class="text-slate-400 max-w-sm leading-relaxed">
                        Virtual Airline Operations SaaS platform &amp; ACARS flight tracking system for virtual aviation communities.
                    </p>
                </div>

                <!-- Links Column 1 -->
                <div class="space-y-3">
                    <h4 class="font-heading font-bold text-white text-xs uppercase tracking-wider">Product</h4>
                    <ul class="space-y-2">
                        <li><a href="#dispatch" class="hover:text-[#21A19D] transition">SimBrief Dispatch</a></li>
                        <li><a href="#tracking" class="hover:text-[#21A19D] transition">ACARS Telemetry</a></li>
                        <li><a href="#portal" class="hover:text-[#21A19D] transition">Pilot Roster</a></li>
                        <li><a href="#architecture" class="hover:text-[#21A19D] transition">Multi-Tenant Architecture</a></li>
                    </ul>
                </div>

                <!-- Links Column 2 -->
                <div class="space-y-3">
                    <h4 class="font-heading font-bold text-white text-xs uppercase tracking-wider">Developers &amp; Docs</h4>
                    <ul class="space-y-2">
                        <li><a href="#" class="hover:text-[#21A19D] transition">Documentation</a></li>
                        <li><a href="#" class="hover:text-[#21A19D] transition">REST API Access</a></li>
                        <li><a href="{{ route('resources.vpilot-acars') }}" target="_blank" rel="noopener noreferrer" class="hover:text-[#21A19D] transition">vPilot ACARS Client</a></li>
                        <li><a href="#" class="hover:text-[#21A19D] transition">System Status</a></li>
                    </ul>
                </div>

                <!-- Links Column 3 -->
                <div class="space-y-3">
                    <h4 class="font-heading font-bold text-white text-xs uppercase tracking-wider">Legal &amp; Support</h4>
                    <ul class="space-y-2">
                        <li><a href="{{ route('terms.show') }}" class="hover:text-[#21A19D] transition">Terms of Service</a></li>
                        <li><a href="{{ route('policy.show') }}" class="hover:text-[#21A19D] transition">Privacy Policy</a></li>
                        <li><a href="javascript:void(0)" onclick="if(window.openCookieSettings) window.openCookieSettings();" class="hover:text-[#21A19D] transition">Cookie Preferences</a></li>
                        <li><a href="#" class="hover:text-[#21A19D] transition">Discord Support</a></li>
                        <li><a href="{{ route('security.show') }}" class="hover:text-[#21A19D] transition">Security Policy</a></li>
                    </ul>
                </div>
            </div>

            <div class="border-t border-navy-700 pt-8 flex flex-wrap items-center justify-between gap-4 font-mono text-[11px]">
                <p>&copy; {{ date('Y') }} V-Air Ops Platform. All rights reserved. Not affiliated with any real-world airline.</p>
                <div class="flex items-center gap-2 text-emerald-400">
                    <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                    <span>SYSTEM OPERATIONAL</span>
                </div>
            </div>
        </div>
    </footer>

    <x-cookie-banner />

</body>
</html>
