<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

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
                    },
                    animation: {
                        'float': 'float 6s ease-in-out infinite',
                        'radar-sweep': 'radar-sweep 4s linear infinite',
                    },
                    keyframes: {
                        float: {
                            '0%, 100%': { transform: 'translateY(0)' },
                            '50%': { transform: 'translateY(-6px)' },
                        },
                        'radar-sweep': {
                            '0%': { transform: 'rotate(0deg)' },
                            '100%': { transform: 'rotate(360deg)' },
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
            transform: translateY(-3px);
            box-shadow: 0 12px 24px -10px rgba(0, 0, 0, 0.5);
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
                <a href="#dispatch" class="hover:text-[#21A19D] transition">Smart Dispatch</a>
                <a href="#portal" class="hover:text-[#21A19D] transition">Pilot Portal</a>
                <a href="#radar" class="hover:text-[#21A19D] transition">Live ACARS</a>
                <a href="#infrastructure" class="hover:text-[#21A19D] transition">Enterprise Ops</a>
            </nav>

            <!-- Desktop Authentication / CTAs -->
            <div class="hidden md:flex items-center gap-4">
                <?php if (auth()->check()): ?>
                    <a href="{{ url('/dashboard') }}" class="px-5 py-2.5 rounded-lg bg-navy-800 hover:bg-navy-700 text-[#21A19D] border border-navy-700 text-xs font-bold font-mono transition">
                        GO TO CONSOLE &rarr;
                    </a>
                <?php else: ?>
                    <a href="{{ route('login') }}" class="text-xs font-bold text-slate-300 hover:text-white px-4 py-2 transition">
                        Sign In
                    </a>
                    <a href="{{ route('register') }}" class="px-5 py-2.5 rounded-lg bg-[#21A19D] hover:bg-[#1C8C88] text-white font-heading font-bold text-xs shadow-md transition">
                        Join or create your own airline.
                    </a>
                <?php endif; ?>
            </div>

            <!-- Mobile Actions (Console/Sign-in + Hamburger button) -->
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
                    <span>Smart Dispatch</span>
                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
                <a href="#portal" @click="mobileMenuOpen = false" class="px-3 py-2.5 rounded-lg hover:bg-navy-900 hover:text-[#21A19D] transition flex items-center justify-between">
                    <span>Pilot Portal</span>
                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
                <a href="#radar" @click="mobileMenuOpen = false" class="px-3 py-2.5 rounded-lg hover:bg-navy-900 hover:text-[#21A19D] transition flex items-center justify-between">
                    <span>Live ACARS</span>
                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
                <a href="#infrastructure" @click="mobileMenuOpen = false" class="px-3 py-2.5 rounded-lg hover:bg-navy-900 hover:text-[#21A19D] transition flex items-center justify-between">
                    <span>Enterprise Ops</span>
                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
            </nav>

            <div class="pt-3 border-t border-navy-700 flex flex-col gap-2.5">
                <?php if (auth()->check()): ?>
                    <a href="{{ url('/dashboard') }}" class="w-full text-center px-4 py-3 rounded-lg bg-navy-800 text-[#21A19D] border border-navy-700 text-xs font-bold font-mono transition">
                        GO TO CONSOLE &rarr;
                    </a>
                <?php else: ?>
                    <a href="{{ route('login') }}" class="w-full text-center px-4 py-2.5 rounded-lg bg-navy-900 border border-navy-700 text-xs font-bold text-slate-200 hover:text-white transition">
                        Sign In
                    </a>
                    <a href="{{ route('register') }}" class="w-full text-center px-4 py-3 rounded-lg bg-[#21A19D] hover:bg-[#1C8C88] text-white font-heading font-bold text-xs shadow-md transition">
                        Join or create your own airline.
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- SECTION 1: HERO SECTION -->
    <section class="relative pt-16 pb-24 lg:pt-24 lg:pb-32 bg-navy-900 border-b border-navy-700 overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="text-center max-w-4xl mx-auto space-y-8">
                
                <!-- Badge -->
                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-lg flat-card text-xs text-teal-300 shadow-md">
                    <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                    <span class="font-mono font-semibold uppercase tracking-wider text-[11px]">100% Free &bull; Built for MSFS 2024 &amp; X-Plane 12</span>
                    <span class="text-slate-500">&bull;</span>
                    <span class="text-purple-300 font-bold">PMDG &bull; iFly &bull; Fenix Ready</span>
                </div>

                <!-- Main Heading -->
                <h1 class="font-heading font-black text-4xl sm:text-6xl lg:text-7xl tracking-tight leading-[1.08] text-white">
                    Run Your Virtual Airline <br class="hidden sm:inline" />
                    <span class="text-[#21A19D]">Like the Real Thing.</span>
                </h1>

                <!-- Subheadline -->
                <p class="text-slate-300 text-lg sm:text-xl max-w-2xl mx-auto leading-relaxed font-light">
                    The modern, high-performance SaaS platform for virtual aviation communities. Smart SimBrief dispatching, sub-second telemetry ACARS, automated pilot rosters, and zero-maintenance cloud infrastructure.
                </p>

                <!-- Call To Actions -->
                <div class="flex flex-wrap items-center justify-center gap-4 pt-4">
                    <a href="{{ route('register') }}" class="px-8 py-4 rounded-xl bg-[#21A19D] hover:bg-[#1C8C88] text-white font-heading font-extrabold text-base shadow-lg hover:scale-[1.01] transition flex items-center gap-3">
                        <span>Join or create your own airline.</span>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                    </a>

                    <a href="#features" class="px-8 py-4 rounded-xl bg-navy-800 hover:bg-navy-700 text-slate-200 font-heading font-bold text-base border border-navy-700 transition flex items-center gap-2">
                        <svg class="w-5 h-5 text-[#21A19D]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <span>Explore Features</span>
                    </a>
                </div>

                <!-- Micro Proof Text -->
                <div class="flex items-center justify-center gap-6 text-xs text-slate-400 font-mono pt-2">
                    <span class="flex items-center gap-1.5"><svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg> 100% Free Forever</span>
                    <span class="flex items-center gap-1.5"><svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg> No Credit Card Required</span>
                    <span class="flex items-center gap-1.5"><svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg> Instant 2-Minute Setup</span>
                </div>
            </div>

            <!-- Hero Mockup Container -->
            <div class="mt-16 lg:mt-24 relative max-w-6xl mx-auto">
                <div class="relative rounded-xl flat-card border border-navy-700 overflow-hidden shadow-2xl">
                    <!-- Window Header Controls -->
                    <div class="bg-navy-950 border-b border-navy-700 px-4 py-3 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-red-500/80"></span>
                            <span class="w-3 h-3 rounded-full bg-yellow-500/80"></span>
                            <span class="w-3 h-3 rounded-full bg-green-500/80"></span>
                            <span class="text-xs font-mono text-slate-400 ml-2">vops-console.app/admin/live-radar</span>
                        </div>
                        <div class="flex items-center gap-3 text-xs font-mono text-teal-400">
                            <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                            <span>ACARS STREAM: ACTIVE (128 FLIGHTS IN AIR)</span>
                        </div>
                    </div>

                    <!-- Mockup Inner Content -->
                    <div class="p-6 bg-navy-900 space-y-6">
                        <!-- Top Stat Bar inside Mockup -->
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div class="p-4 rounded-lg bg-navy-950 border border-navy-700">
                                <span class="text-[10px] font-mono uppercase text-slate-400 block">Enroute Flights</span>
                                <span class="text-2xl font-bold font-mono text-[#21A19D]">142 FLTS</span>
                            </div>
                            <div class="p-4 rounded-lg bg-navy-950 border border-navy-700">
                                <span class="text-[10px] font-mono uppercase text-slate-400 block">Network Fleet Load</span>
                                <span class="text-2xl font-bold font-mono text-[#6F3B84]">89.4%</span>
                            </div>
                            <div class="p-4 rounded-lg bg-navy-950 border border-navy-700">
                                <span class="text-[10px] font-mono uppercase text-slate-400 block">Avg Landing Rate</span>
                                <span class="text-2xl font-bold font-mono text-emerald-400">-142 FPM</span>
                            </div>
                            <div class="p-4 rounded-lg bg-navy-950 border border-navy-700">
                                <span class="text-[10px] font-mono uppercase text-slate-400 block">System Health</span>
                                <span class="text-2xl font-bold font-mono text-teal-300">100% OK</span>
                            </div>
                        </div>

                        <!-- Dark Live Map Mockup -->
                        <div class="h-[380px] sm:h-[460px] rounded-lg bg-navy-950 border border-navy-700 relative overflow-hidden flex items-center justify-center">
                            <!-- Simulated Vector Paths -->
                            <svg class="absolute inset-0 w-full h-full opacity-40" viewBox="0 0 1000 500" fill="none">
                                <path d="M 150 350 Q 300 150 500 200 T 850 120" stroke="#6F3B84" stroke-width="2" stroke-dasharray="6,6"/>
                                <path d="M 200 400 Q 450 300 750 180" stroke="#21A19D" stroke-width="3"/>
                                <path d="M 100 180 Q 400 120 800 380" stroke="#10B981" stroke-width="2"/>
                            </svg>

                            <!-- Live Flight Pins -->
                            <div class="absolute top-[22%] left-[48%] animate-float flex items-center gap-2 bg-navy-900 border border-[#21A19D] px-3 py-1.5 rounded-lg shadow-xl z-20">
                                <span class="w-2 h-2 rounded-full bg-[#21A19D]"></span>
                                <div class="text-left font-mono">
                                    <span class="text-xs font-bold text-white block">SVK101 &bull; B738</span>
                                    <span class="text-[10px] text-teal-300">FL360 &bull; 460 KTS &bull; EGLL &rarr; LFPG</span>
                                </div>
                            </div>

                            <div class="absolute top-[52%] left-[28%] flex items-center gap-2 bg-navy-900 border border-[#6F3B84] px-3 py-1.5 rounded-lg shadow-xl z-20">
                                <span class="w-2 h-2 rounded-full bg-[#6F3B84]"></span>
                                <div class="text-left font-mono">
                                    <span class="text-xs font-bold text-white block">EZS8999 &bull; A320</span>
                                    <span class="text-[10px] text-purple-300">FL380 &bull; 442 KTS &bull; LFLL &rarr; EGKK</span>
                                </div>
                            </div>

                            <div class="absolute bottom-[28%] right-[22%] flex items-center gap-2 bg-navy-900 border border-emerald-500 px-3 py-1.5 rounded-lg shadow-xl z-20">
                                <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                                <div class="text-left font-mono">
                                    <span class="text-xs font-bold text-white block">DLH404 &bull; A359</span>
                                    <span class="text-[10px] text-emerald-300">FL410 &bull; 488 KTS &bull; EDDF &rarr; KJFK</span>
                                </div>
                            </div>

                            <!-- Map Overlay Badge -->
                            <div class="absolute bottom-4 left-4 bg-navy-950 border border-navy-700 px-4 py-2 rounded-lg text-xs font-mono text-slate-300">
                                Global Live Radar Stream &bull; Sub-Second Telemetry Sync
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- SECTION 2: LIVE STATISTICS BAR (SOCIAL PROOF) -->
    <section class="py-12 bg-navy-950 border-b border-navy-700 relative z-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center divide-x divide-navy-700">
                <div class="space-y-1">
                    <span class="font-heading font-black text-3xl sm:text-5xl text-[#21A19D] font-mono">1,420+</span>
                    <span class="text-xs font-mono uppercase tracking-wider text-slate-400 block">Active Flights Today</span>
                </div>
                <div class="space-y-1 pl-4">
                    <span class="font-heading font-black text-3xl sm:text-5xl text-[#6F3B84] font-mono">38,500+</span>
                    <span class="text-xs font-mono uppercase tracking-wider text-slate-400 block">Registered Pilots</span>
                </div>
                <div class="space-y-1 pl-4">
                    <span class="font-heading font-black text-3xl sm:text-5xl text-teal-300 font-mono">1.8M+</span>
                    <span class="text-xs font-mono uppercase tracking-wider text-slate-400 block">Block Hours Logged</span>
                </div>
                <div class="space-y-1 pl-4">
                    <span class="font-heading font-black text-3xl sm:text-5xl text-emerald-400 font-mono">99.99%</span>
                    <span class="text-xs font-mono uppercase tracking-wider text-slate-400 block">Uptime Guarantee</span>
                </div>
            </div>
        </div>
    </section>

    <!-- SECTION 3: CORE FEATURES GRID -->
    <section id="features" class="py-24 lg:py-32 bg-navy-900 border-b border-navy-700 relative z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-16">
            
            <div class="text-center max-w-3xl mx-auto space-y-4">
                <span class="text-xs font-mono uppercase tracking-widest text-[#21A19D] font-bold bg-navy-950 px-3 py-1 rounded-md border border-navy-700">
                    COMPLETE VIRTUAL AIRLINE SUITE
                </span>
                <h2 class="font-heading font-black text-3xl sm:text-5xl text-white tracking-tight">
                    Everything You Need to Scale Your Airline
                </h2>
                <p class="text-slate-400 text-base sm:text-lg">
                    Ditch legacy spreadsheets and outdated PHP scripts. V-Air Ops provides an enterprise SaaS backbone built specifically for virtual airline managers and dedicated flight simmers.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                <!-- Feature 1 -->
                <div class="flat-card flat-card-hover p-8 rounded-xl space-y-4 relative overflow-hidden group">
                    <div class="w-12 h-12 rounded-lg bg-navy-950 border border-navy-700 flex items-center justify-center text-[#21A19D]">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    </div>
                    <h3 class="font-heading font-bold text-xl text-white">Smart SimBrief Dispatch</h3>
                    <p class="text-slate-400 text-sm leading-relaxed">
                        One-click official LIDO flight plan generation, automated route validation, real-time ETOPS calculations, and passenger/cargo payload balancing.
                    </p>
                </div>

                <!-- Feature 2 -->
                <div class="flat-card flat-card-hover p-8 rounded-xl space-y-4 relative overflow-hidden group">
                    <div class="w-12 h-12 rounded-lg bg-navy-950 border border-navy-700 flex items-center justify-center text-[#6F3B84]">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                    </div>
                    <h3 class="font-heading font-bold text-xl text-white">Real-Time ACARS Telemetry</h3>
                    <p class="text-slate-400 text-sm leading-relaxed">
                        Lightweight background tracking client compatible across MSFS 2024, X-Plane 12, and P3D. Captures pitch, bank, G-force, and fuel burn sub-secondly.
                    </p>
                </div>

                <!-- Feature 3 -->
                <div class="flat-card flat-card-hover p-8 rounded-xl space-y-4 relative overflow-hidden group">
                    <div class="w-12 h-12 rounded-lg bg-navy-950 border border-navy-700 flex items-center justify-center text-teal-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    </div>
                    <h3 class="font-heading font-bold text-xl text-white">Advanced Flight Analytics</h3>
                    <p class="text-slate-400 text-sm leading-relaxed">
                        Detailed vertical profile graphs, touchdown FPM scoring, overspeed/stall penalty detection, and automated pilot logbook audits.
                    </p>
                </div>

                <!-- Feature 4 -->
                <div class="flat-card flat-card-hover p-8 rounded-xl space-y-4 relative overflow-hidden group">
                    <div class="w-12 h-12 rounded-lg bg-navy-950 border border-navy-700 flex items-center justify-center text-emerald-400">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                    </div>
                    <h3 class="font-heading font-bold text-xl text-white">Rock-Solid Infrastructure</h3>
                    <p class="text-slate-400 text-sm leading-relaxed">
                        Built on containerized, auto-scaling cloud microservices. Guarantees zero downtime during 100+ pilot community group flights and VATSIM events.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- SECTION 4: THE PILOT EXPERIENCE -->
    <section id="portal" class="py-24 lg:py-32 bg-navy-950 border-b border-navy-700 relative z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
                
                <!-- Left Details -->
                <div class="space-y-8">
                    <div class="space-y-4">
                        <span class="text-xs font-mono uppercase tracking-widest text-[#6F3B84] font-bold bg-navy-900 px-3 py-1 rounded-md border border-navy-700">
                            THE PILOT EXPERIENCE
                        </span>
                        <h2 class="font-heading font-black text-3xl sm:text-5xl text-white tracking-tight">
                            Empower Your Pilots with a World-Class Portal
                        </h2>
                        <p class="text-slate-400 text-base sm:text-lg leading-relaxed">
                            Keep your community engaged with gamified rank progression, community flight goals, detailed pilot profiles, and seamless Discord integration.
                        </p>
                    </div>

                    <div class="space-y-6">
                        <!-- Item 1 -->
                        <div class="flex gap-4 items-start">
                            <div class="w-10 h-10 rounded-lg bg-navy-900 border border-navy-700 flex items-center justify-center text-[#21A19D] shrink-0 mt-1">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            </div>
                            <div class="space-y-1">
                                <h4 class="font-heading font-bold text-lg text-white">Automated Rank Progression &amp; Badges</h4>
                                <p class="text-slate-400 text-sm">Set custom hour thresholds and points criteria. Pilots automatically unlock ranks and type ratings as they log flight hours.</p>
                            </div>
                        </div>

                        <!-- Item 2 -->
                        <div class="flex gap-4 items-start">
                            <div class="w-10 h-10 rounded-lg bg-navy-900 border border-navy-700 flex items-center justify-center text-[#6F3B84] shrink-0 mt-1">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                            </div>
                            <div class="space-y-1">
                                <h4 class="font-heading font-bold text-lg text-white">Native Discord Bot &amp; Role Syncing</h4>
                                <p class="text-slate-400 text-sm">Automatically grant Discord roles when pilots join or rank up. Post flight dispatch notifications and landing debriefs directly into community channels.</p>
                            </div>
                        </div>

                        <!-- Item 3 -->
                        <div class="flex gap-4 items-start">
                            <div class="w-10 h-10 rounded-lg bg-navy-900 border border-navy-700 flex items-center justify-center text-teal-300 shrink-0 mt-1">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 30a1 1 0 001 1h2a1 1 0 001-1v-1a1 1 0 00-1-1h-2a1 1 0 00-1 1v1z"></path></svg>
                            </div>
                            <div class="space-y-1">
                                <h4 class="font-heading font-bold text-lg text-white">Community Goals &amp; Event Operations</h4>
                                <p class="text-slate-400 text-sm">Create airline-wide destination challenges, event routes, and group flight leaderboards with live statistics.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Feature Mockup Card -->
                <div class="flat-card rounded-xl p-6 sm:p-8 space-y-6 shadow-xl">
                    <div class="flex items-center justify-between border-b border-navy-700 pb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-[#21A19D] font-bold flex items-center justify-center text-white font-heading text-sm">
                                CPT
                            </div>
                            <div>
                                <h4 class="font-heading font-bold text-white text-sm">Capt. Alexander Wright</h4>
                                <span class="text-xs font-mono text-[#21A19D]">Senior Captain &bull; SVK-104</span>
                            </div>
                        </div>
                        <span class="px-3 py-1 rounded-md bg-navy-950 text-emerald-400 text-xs font-mono font-bold border border-navy-700">
                            RANK: SENIOR COMMANDER
                        </span>
                    </div>

                    <!-- Progress Cards -->
                    <div class="space-y-4 text-xs font-mono">
                        <div>
                            <div class="flex justify-between text-slate-300 mb-1">
                                <span>Flight Hours Progress</span>
                                <span class="text-[#21A19D] font-bold">1,240h / 1,500h</span>
                            </div>
                            <div class="w-full bg-navy-950 h-2.5 rounded-full overflow-hidden border border-navy-700">
                                <div class="bg-[#21A19D] h-full w-[82%] rounded-full"></div>
                            </div>
                        </div>

                        <div>
                            <div class="flex justify-between text-slate-300 mb-1">
                                <span>Landing Rating Score</span>
                                <span class="text-emerald-400 font-bold">98.5% (Butter Average)</span>
                            </div>
                            <div class="w-full bg-navy-950 h-2.5 rounded-full overflow-hidden border border-navy-700">
                                <div class="bg-emerald-500 h-full w-[98%] rounded-full"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Flights Log Preview -->
                    <div class="space-y-3 pt-2">
                        <span class="text-[11px] font-mono text-slate-400 uppercase tracking-wider block font-bold">Recent Verified PIREPs</span>
                        
                        <div class="p-3 bg-navy-950 rounded-lg border border-navy-700 flex items-center justify-between text-xs font-mono">
                            <div class="flex items-center gap-3">
                                <span class="text-[#21A19D] font-bold">EGLL &rarr; LFPG</span>
                                <span class="text-slate-400">A320</span>
                            </div>
                            <span class="text-emerald-400 font-bold">-112 FPM</span>
                            <span class="text-slate-300">01:15 &bull; +100 PTS</span>
                        </div>

                        <div class="p-3 bg-navy-950 rounded-lg border border-navy-700 flex items-center justify-between text-xs font-mono">
                            <div class="flex items-center gap-3">
                                <span class="text-[#21A19D] font-bold">EDDF &rarr; KJFK</span>
                                <span class="text-slate-400">B789</span>
                            </div>
                            <span class="text-emerald-400 font-bold">-148 FPM</span>
                            <span class="text-slate-300">07:42 &bull; +100 PTS</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- SECTION 5: INTERACTIVE RADAR OVERVIEW -->
    <section id="radar" class="py-24 lg:py-32 bg-navy-900 border-b border-navy-700 relative z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
            <div class="text-center max-w-3xl mx-auto space-y-4">
                <span class="text-xs font-mono uppercase tracking-widest text-emerald-400 font-bold bg-navy-950 px-3 py-1 rounded-md border border-navy-700">
                    REAL-TIME RADAR OVERVIEW
                </span>
                <h2 class="font-heading font-black text-3xl sm:text-5xl text-white tracking-tight">
                    Watch Your Entire Fleet Fly in Real Time
                </h2>
                <p class="text-slate-400 text-base sm:text-lg">
                    Our high-resolution live map provides flight dispatchers and admins complete situational awareness across the globe with interactive flight tracking.
                </p>
            </div>

            <!-- Stylized Flat Interactive Map Container -->
            <div class="flat-card rounded-xl overflow-hidden shadow-2xl relative">
                <!-- Map Header Bar -->
                <div class="bg-navy-950 border-b border-navy-700 p-4 flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <span class="w-3 h-3 rounded-full bg-emerald-400"></span>
                        <span class="font-heading font-bold text-white text-sm">GLOBAL FLIGHT MONITOR &bull; NETWORK RADAR</span>
                    </div>
                    <div class="flex items-center gap-4 text-xs font-mono">
                        <span class="text-slate-400">FILTER: <strong class="text-[#21A19D]">ALL FLEETS</strong></span>
                        <span class="text-slate-400">SYNC: <strong class="text-emerald-400">SUB-SECOND TELEMETRY</strong></span>
                    </div>
                </div>

                <!-- Map Body -->
                <div class="h-[500px] bg-navy-950 relative flex items-center justify-center overflow-hidden">
                    <!-- Simulated Aircraft Vectors -->
                    <div class="absolute top-[35%] left-[30%] group cursor-pointer">
                        <div class="w-8 h-8 rounded-full bg-navy-900 border border-[#21A19D] flex items-center justify-center">
                            <svg class="w-4 h-4 text-[#21A19D] transform rotate-45" fill="currentColor" viewBox="0 0 24 24"><path d="M21 16v-2l-8-5V3.5c0-.83-.67-1.5-1.5-1.5S10 2.67 10 3.5V9l-8 5v2l8-2.5V19l-2 1.5V22l3.5-1 3.5 1v-1.5L13 19v-5.5l8 2.5z"/></svg>
                        </div>
                        <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 bg-navy-900 border border-navy-700 px-3 py-1.5 rounded-md text-xs font-mono text-white whitespace-nowrap shadow-xl">
                            <span class="font-bold text-[#21A19D]">VOPS-142</span> &bull; B738 &bull; FL360
                        </div>
                    </div>

                    <div class="absolute top-[45%] left-[62%] group cursor-pointer">
                        <div class="w-8 h-8 rounded-full bg-navy-900 border border-[#6F3B84] flex items-center justify-center">
                            <svg class="w-4 h-4 text-[#6F3B84] transform rotate-90" fill="currentColor" viewBox="0 0 24 24"><path d="M21 16v-2l-8-5V3.5c0-.83-.67-1.5-1.5-1.5S10 2.67 10 3.5V9l-8 5v2l8-2.5V19l-2 1.5V22l3.5-1 3.5 1v-1.5L13 19v-5.5l8 2.5z"/></svg>
                        </div>
                        <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 bg-navy-900 border border-navy-700 px-3 py-1.5 rounded-md text-xs font-mono text-white whitespace-nowrap shadow-xl">
                            <span class="font-bold text-[#6F3B84]">VOPS-892</span> &bull; A320 &bull; FL380
                        </div>
                    </div>

                    <!-- Radar Sweep Circle Animation -->
                    <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                        <div class="w-[600px] h-[600px] rounded-full border border-navy-700 relative animate-radar-sweep">
                            <div class="absolute top-1/2 left-1/2 w-1/2 h-[1px] bg-[#21A19D] origin-left opacity-40"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- SECTION 6: FINAL CTA -->
    <section class="py-24 lg:py-32 bg-navy-900 relative z-10 overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flat-card border border-navy-700 rounded-2xl p-8 sm:p-16 text-center relative overflow-hidden space-y-8 shadow-2xl">
                <h2 class="font-heading font-black text-3xl sm:text-5xl lg:text-6xl text-white tracking-tight leading-tight">
                    Ready to Launch Your Airline?
                </h2>
                
                <p class="text-slate-300 text-base sm:text-xl max-w-2xl mx-auto font-light leading-relaxed">
                    Join thousands of virtual airline pilots and community managers already using V-Air Ops to power their flight ops infrastructure for 100% free.
                </p>

                <div class="flex flex-wrap items-center justify-center gap-4 pt-4">
                    <a href="{{ route('register') }}" class="px-8 py-4 rounded-xl bg-[#21A19D] hover:bg-[#1C8C88] text-white font-heading font-extrabold text-base shadow-lg hover:scale-[1.01] transition flex items-center gap-3">
                        <span>Join or create your own airline.</span>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                    </a>
                </div>

                <p class="text-xs font-mono text-slate-400">
                    100% Free Forever &bull; Instant Setup &bull; Free Migration Support from vAMSYS / phpVMS &bull; No Credit Card Required
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
                        High-performance Virtual Airline SaaS platform &amp; sub-second ACARS telemetry engine for modern flight simulation communities.
                    </p>
                </div>

                <!-- Links Column 1 -->
                <div class="space-y-3">
                    <h4 class="font-heading font-bold text-white text-xs uppercase tracking-wider">Product</h4>
                    <ul class="space-y-2">
                        <li><a href="#dispatch" class="hover:text-[#21A19D] transition">Smart Dispatch</a></li>
                        <li><a href="#radar" class="hover:text-[#21A19D] transition">Live ACARS Radar</a></li>
                        <li><a href="#portal" class="hover:text-[#21A19D] transition">Pilot Roster</a></li>
                        <li><a href="#infrastructure" class="hover:text-[#21A19D] transition">Analytics Engine</a></li>
                    </ul>
                </div>

                <!-- Links Column 2 -->
                <div class="space-y-3">
                    <h4 class="font-heading font-bold text-white text-xs uppercase tracking-wider">Developers &amp; Docs</h4>
                    <ul class="space-y-2">
                        <li><a href="#" class="hover:text-[#21A19D] transition">Documentation</a></li>
                        <li><a href="#" class="hover:text-[#21A19D] transition">REST API Access</a></li>
                        <li><a href="#" class="hover:text-[#21A19D] transition">ACARS Client SDK</a></li>
                        <li><a href="#" class="hover:text-[#21A19D] transition">System Status</a></li>
                    </ul>
                </div>

                <!-- Links Column 3 -->
                <div class="space-y-3">
                    <h4 class="font-heading font-bold text-white text-xs uppercase tracking-wider">Legal &amp; Support</h4>
                    <ul class="space-y-2">
                        <li><a href="#" class="hover:text-[#21A19D] transition">Terms of Service</a></li>
                        <li><a href="#" class="hover:text-[#21A19D] transition">Privacy Policy</a></li>
                        <li><a href="#" class="hover:text-[#21A19D] transition">Discord Support</a></li>
                        <li><a href="#" class="hover:text-[#21A19D] transition">Security Policy</a></li>
                    </ul>
                </div>
            </div>

            <div class="border-t border-navy-700 pt-8 flex flex-wrap items-center justify-between gap-4 font-mono text-[11px]">
                <p>&copy; {{ date('Y') }} V-Air Ops SaaS Platform. All rights reserved. Not affiliated with any real-world airline.</p>
                <div class="flex items-center gap-2 text-emerald-400">
                    <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                    <span>ALL SYSTEMS OPERATIONAL</span>
                </div>
            </div>
        </div>
    </footer>

</body>
</html>
