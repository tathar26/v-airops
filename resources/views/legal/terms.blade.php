<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <x-google-analytics />

    <title>Terms of Service &bull; {{ config('app.name', 'V-Air Ops') }}</title>

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
                        vops: {
                            navy: '#0A1835',
                            'navy-dark': '#060E22',
                            'navy-light': '#0F224A',
                            teal: '#21A19D',
                            purple: '#6F3B84',
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
    </style>
</head>
<body class="bg-navy-900 text-slate-100 antialiased selection:bg-[#21A19D] selection:text-white flex flex-col min-h-screen">

    <!-- Navigation Header -->
    <header class="sticky top-0 z-50 bg-navy-950 border-b border-navy-700 transition-all" x-data="{ mobileMenuOpen: false }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
            <a href="/" class="flex items-center gap-3 group">
                <img src="{{ asset('images/v-air-ops-logo-cropped.png') }}" alt="V-Air Ops" class="h-9 sm:h-11 w-auto object-contain transition duration-200 group-hover:opacity-90">
            </a>

            <nav class="hidden md:flex items-center gap-8 text-sm font-medium text-slate-300">
                <a href="/#features" class="hover:text-[#21A19D] transition">Features</a>
                <a href="/#dispatch" class="hover:text-[#21A19D] transition">Smart Dispatch</a>
                <a href="/#portal" class="hover:text-[#21A19D] transition">Pilot Portal</a>
                <a href="/#radar" class="hover:text-[#21A19D] transition">Live ACARS</a>
                <a href="/#infrastructure" class="hover:text-[#21A19D] transition">Enterprise Ops</a>
            </nav>

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

            <!-- Mobile Actions Button -->
            <div class="flex items-center gap-2 md:hidden">
                <button @click="mobileMenuOpen = !mobileMenuOpen" type="button" class="p-2 rounded-lg bg-navy-900 border border-navy-700 text-slate-300 hover:text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
            </div>
        </div>

        <!-- Mobile Navigation Menu -->
        <div x-show="mobileMenuOpen" class="md:hidden bg-navy-950 border-b border-navy-700 px-4 pt-3 pb-6 space-y-4" style="display: none;">
            <nav class="flex flex-col space-y-2 text-sm font-medium text-slate-200">
                <a href="/" class="px-3 py-2 rounded-lg hover:bg-navy-900">Home</a>
                <a href="/#features" class="px-3 py-2 rounded-lg hover:bg-navy-900">Features</a>
                <a href="{{ route('terms.show') }}" class="px-3 py-2 rounded-lg bg-navy-900 text-[#21A19D]">Terms of Service</a>
                <a href="{{ route('policy.show') }}" class="px-3 py-2 rounded-lg hover:bg-navy-900">Privacy Policy</a>
                <a href="{{ route('security.show') }}" class="px-3 py-2 rounded-lg hover:bg-navy-900">Security Policy</a>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-grow py-12 lg:py-20 bg-navy-900 relative">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
            
            <!-- Page Header -->
            <div class="space-y-4 border-b border-navy-700 pb-8">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-md bg-navy-950 border border-navy-700 text-xs font-mono text-[#21A19D]">
                    <span>LEGAL DOCUMENTATION &bull; TERMS OF SERVICE</span>
                </div>
                <h1 class="font-heading font-black text-3xl sm:text-5xl text-white tracking-tight">
                    Terms of Service
                </h1>
                <p class="text-slate-400 text-sm sm:text-base font-mono">
                    Last Updated: September 7, 2026 &bull; Version 1.2
                </p>
            </div>

            <!-- Content Container -->
            <div class="flat-card rounded-2xl p-6 sm:p-10 space-y-8 text-slate-300 text-sm sm:text-base leading-relaxed">
                
                <!-- Section 1 -->
                <section class="space-y-3">
                    <h2 class="font-heading font-bold text-xl text-white flex items-center gap-3">
                        <span class="text-[#21A19D] font-mono text-base">01.</span> Acceptance of Terms &amp; Platform Scope
                    </h2>
                    <p>
                        Welcome to <strong class="text-white">V-Air Ops</strong> ("the Platform", "we", "us", or "our"). By registering an account, creating or managing a virtual airline, or connecting your flight simulator using the vPilot ACARS client, you agree to be bound by these Terms of Service.
                    </p>
                    <p>
                        V-Air Ops is a software-as-a-service (SaaS) platform designed exclusively for flight simulation enthusiasts and virtual airline management communities. All operations, flight logging, schedules, aircraft telemetry, and pilot rosters managed on V-Air Ops are strictly for entertainment and educational virtual aviation purposes.
                    </p>
                </section>

                <!-- Section 2 -->
                <section class="space-y-3 pt-4 border-t border-navy-700">
                    <h2 class="font-heading font-bold text-xl text-white flex items-center gap-3">
                        <span class="text-[#21A19D] font-mono text-base">02.</span> Non-Affiliation Disclaimer
                    </h2>
                    <div class="p-4 rounded-xl bg-navy-950 border border-amber-500/30 text-amber-200/90 text-sm space-y-2">
                        <div class="font-bold flex items-center gap-2 text-amber-400">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            <span>Non-Commercial &amp; Virtual Aviation Use Only</span>
                        </div>
                        <p>
                            V-Air Ops is NOT affiliated with, endorsed by, or connected to any real-world commercial airline, aircraft manufacturer, or aviation authority (including FAA, EASA, or ICAO). All airline names, logos, callsigns, and trademarks referenced by users within the system remain the property of their respective owners.
                        </p>
                    </div>
                </section>

                <!-- Section 3 -->
                <section class="space-y-3 pt-4 border-t border-navy-700">
                    <h2 class="font-heading font-bold text-xl text-white flex items-center gap-3">
                        <span class="text-[#21A19D] font-mono text-base">03.</span> User &amp; Virtual Airline Manager Accounts
                    </h2>
                    <ul class="list-disc list-inside space-y-2 text-slate-300 pl-2">
                        <li><strong class="text-white">Account Responsibility:</strong> You are responsible for maintaining the confidentiality of your account credentials and for all activities conducted through your account.</li>
                        <li><strong class="text-white">Acceptable Use:</strong> Users agree not to submit fraudulent PIREPs, manipulate ACARS telemetry data, upload malicious or offensive content, or disrupt platform infrastructure.</li>
                        <li><strong class="text-white">Tenant Administration:</strong> Virtual Airline owners and administrators are responsible for managing member access, pilot permissions, and custom fleet assignments in accordance with community guidelines.</li>
                    </ul>
                </section>

                <!-- Section 4 -->
                <section class="space-y-3 pt-4 border-t border-navy-700">
                    <h2 class="font-heading font-bold text-xl text-white flex items-center gap-3">
                        <span class="text-[#21A19D] font-mono text-base">04.</span> ACARS Client &amp; Telemetry Usage
                    </h2>
                    <p>
                        The vPilot ACARS client logs flight telemetry (such as latitude, longitude, altitude, airspeed, vertical speed, fuel flow, and touchdown G-force) during active flight simulator sessions. By using the ACARS tracking software, you grant V-Air Ops authorization to process, display, and archive this telemetry data for leaderboards, PIREPs, and live radar tracking.
                    </p>
                </section>

                <!-- Section 5 -->
                <section class="space-y-3 pt-4 border-t border-navy-700">
                    <h2 class="font-heading font-bold text-xl text-white flex items-center gap-3">
                        <span class="text-[#21A19D] font-mono text-base">05.</span> Third-Party Services &amp; Integrations
                    </h2>
                    <p>
                        V-Air Ops integrates with third-party simulation tools including SimBrief (by Navigraph), VATSIM, IVAO, and Discord. Use of these integrated features is subject to the respective terms and privacy policies of those third-party services.
                    </p>
                </section>

                <!-- Section 6 -->
                <section class="space-y-3 pt-4 border-t border-navy-700">
                    <h2 class="font-heading font-bold text-xl text-white flex items-center gap-3">
                        <span class="text-[#21A19D] font-mono text-base">06.</span> Service Availability &amp; Modifications
                    </h2>
                    <p>
                        V-Air Ops is provided on an "AS-IS" and "AS-AVAILABLE" basis. While we strive to maintain 99.99% uptime for cloud services, we reserve the right to perform scheduled maintenance, update features, or suspend service for technical upgrades without prior notice.
                    </p>
                </section>

                <!-- Section 7 -->
                <section class="space-y-3 pt-4 border-t border-navy-700">
                    <h2 class="font-heading font-bold text-xl text-white flex items-center gap-3">
                        <span class="text-[#21A19D] font-mono text-base">07.</span> Termination &amp; Contact
                    </h2>
                    <p>
                        We reserve the right to suspend or terminate accounts that violate these terms or engage in abusive behavior towards the platform or community members. For questions regarding these terms, please reach out via our community channels or support desk.
                    </p>
                </section>

            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="border-t border-navy-700 bg-navy-950 py-12 relative z-20 text-xs text-slate-400 mt-auto">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
            <div class="grid grid-cols-2 md:grid-cols-5 gap-8">
                <div class="col-span-2 space-y-4">
                    <a href="/" class="inline-block">
                        <img src="{{ asset('images/v-air-ops-logo.png') }}" alt="V-Air Ops" class="h-16 w-auto object-contain">
                    </a>
                    <p class="text-slate-400 max-w-sm leading-relaxed">
                        High-performance Virtual Airline SaaS platform &amp; sub-second ACARS telemetry engine for modern flight simulation communities.
                    </p>
                </div>
                <div class="space-y-3">
                    <h4 class="font-heading font-bold text-white text-xs uppercase tracking-wider">Product</h4>
                    <ul class="space-y-2">
                        <li><a href="/#dispatch" class="hover:text-[#21A19D] transition">Smart Dispatch</a></li>
                        <li><a href="/#radar" class="hover:text-[#21A19D] transition">Live ACARS Radar</a></li>
                        <li><a href="/#portal" class="hover:text-[#21A19D] transition">Pilot Roster</a></li>
                        <li><a href="/#infrastructure" class="hover:text-[#21A19D] transition">Analytics Engine</a></li>
                    </ul>
                </div>
                <div class="space-y-3">
                    <h4 class="font-heading font-bold text-white text-xs uppercase tracking-wider">Developers &amp; Docs</h4>
                    <ul class="space-y-2">
                        <li><a href="#" class="hover:text-[#21A19D] transition">Documentation</a></li>
                        <li><a href="#" class="hover:text-[#21A19D] transition">REST API Access</a></li>
                        <li><a href="{{ route('resources.vpilot-acars') }}" target="_blank" rel="noopener noreferrer" class="hover:text-[#21A19D] transition">vPilot ACARS Client</a></li>
                        <li><a href="#" class="hover:text-[#21A19D] transition">System Status</a></li>
                    </ul>
                </div>
                <div class="space-y-3">
                    <h4 class="font-heading font-bold text-white text-xs uppercase tracking-wider">Legal &amp; Support</h4>
                    <ul class="space-y-2">
                        <li><a href="{{ route('terms.show') }}" class="text-[#21A19D] font-bold transition">Terms of Service</a></li>
                        <li><a href="{{ route('policy.show') }}" class="hover:text-[#21A19D] transition">Privacy Policy</a></li>
                        <li><a href="javascript:void(0)" onclick="if(window.openCookieSettings) window.openCookieSettings();" class="hover:text-[#21A19D] transition">Cookie Preferences</a></li>
                        <li><a href="#" class="hover:text-[#21A19D] transition">Discord Support</a></li>
                        <li><a href="{{ route('security.show') }}" class="hover:text-[#21A19D] transition">Security Policy</a></li>
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

    <x-cookie-banner />

</body>
</html>
