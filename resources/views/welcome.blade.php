<!DOCTYPE html>
<html lang="en" class="dark scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>V-Air Ops &mdash; Next-Gen Virtual Airline Management SaaS Platform</title>

    <!-- Primary SEO Meta Tags -->
    <meta name="title" content="V-Air Ops — Next-Gen Virtual Airline Management SaaS Platform">
    <meta name="description" content="The modern, high-performance SaaS platform for virtual aviation communities. Featuring smart SimBrief dispatching, sub-second telemetry ACARS, automated pilot rosters, and zero-maintenance cloud infrastructure. Perfect for MSFS 2024 and X-Plane 12.">
    <meta name="keywords" content="Virtual Airline, ACARS, MSFS 2024, X-Plane 12, SimBrief, Flight Simulation, PMDG 737, Fenix A320, Virtual Aviation, Pilot Logbook, Flight Tracking, vAMSYS alternative, phpVMS alternative">
    <meta name="author" content="V-Air Ops Platform Team">
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
    <meta name="googlebot" content="index, follow">
    <link rel="canonical" href="https://vops-dev.artmex-hosting.com/">

    <!-- Open Graph / Facebook / Discord SEO -->
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="V-Air Ops Platform">
    <meta property="og:url" content="https://vops-dev.artmex-hosting.com/">
    <meta property="og:title" content="V-Air Ops — Next-Gen Virtual Airline Management SaaS Platform">
    <meta property="og:description" content="Run your virtual airline like the real thing. Smart SimBrief LIDO dispatching, sub-second ACARS telemetry, and automated pilot management for MSFS 2024 & X-Plane 12.">
    <meta property="og:image" content="https://vops-dev.artmex-hosting.com/og-preview.png">

    <!-- Twitter Card SEO -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="https://vops-dev.artmex-hosting.com/">
    <meta name="twitter:title" content="V-Air Ops — Next-Gen Virtual Airline Management SaaS Platform">
    <meta name="twitter:description" content="Run your virtual airline like the real thing. Smart SimBrief LIDO dispatching, sub-second ACARS telemetry, and automated pilot management for MSFS 2024 & X-Plane 12.">
    <meta name="twitter:image" content="https://vops-dev.artmex-hosting.com/og-preview.png">

    <!-- Structured Data JSON-LD (AI Crawlers & Google Schema.org) -->
    @verbatim
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "SoftwareApplication",
      "name": "V-Air Ops Virtual Airline Platform",
      "operatingSystem": "Web, Windows, macOS",
      "applicationCategory": "BusinessApplication",
      "offers": {
        "@type": "Offer",
        "price": "0.00",
        "priceCurrency": "USD",
        "seller": {
          "@type": "Organization",
          "name": "V-Air Ops SaaS"
        }
      },
      "description": "High-performance Virtual Airline SaaS platform & sub-second ACARS telemetry engine for modern flight simulation communities (MSFS 2024, X-Plane 12).",
      "url": "https://vops-dev.artmex-hosting.com/",
      "featureList": [
        "One-click SimBrief LIDO OFP dispatching",
        "Sub-second ACARS flight tracking & telemetry",
        "Automated pilot rank progression & hour calculation",
        "Discord bot integration with auto role syncing",
        "Vertical flight profile and touchdown G-force scoring"
      ]
    }
    </script>
    @endverbatim

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght%40300;400;500;600;700;800;900&family=JetBrains+Mono:wght%40400;500;600;700&family=Outfit:wght%40400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Tailwind CSS CDN (Self-contained for preview) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        heading: ['Outfit', 'sans-serif'],
                        mono: ['JetBrains Mono', 'monospace'],
                    },
                    colors: {
                        navy: {
                            950: '#060911',
                            900: '#0b1120',
                            850: '#10192d',
                            800: '#162238',
                            700: '#1f2e4d',
                        },
                        radar: {
                            green: '#22c55e',
                            cyan: '#38bdf8',
                            orange: '#f97316',
                            purple: '#a855f7',
                        }
                    },
                    animation: {
                        'pulse-slow': 'pulse 4s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                        'radar-sweep': 'sweep 6s linear infinite',
                        'float': 'float 6s ease-in-out infinite',
                    },
                    keyframes: {
                        sweep: {
                            '0%': { transform: 'rotate(0deg)' },
                            '100%': { transform: 'rotate(360deg)' },
                        },
                        float: {
                            '0%, 100%': { transform: 'translateY(0px)' },
                            '50%': { transform: 'translateY(-10px)' },
                        }
                    }
                }
            }
        }
    </script>

    <style>
        body {
            background-color: #060911;
            color: #f8fafc;
            font-family: 'Inter', sans-serif;
        }

        .glass-card {
            background: rgba(16, 25, 45, 0.65);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        .glass-card-hover {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .glass-card-hover:hover {
            background: rgba(22, 34, 56, 0.85);
            border-color: rgba(56, 189, 248, 0.3);
            transform: translateY(-4px);
            box-shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.5), 0 0 30px -10px rgba(56, 189, 248, 0.15);
        }

        .gradient-text {
            background: linear-gradient(135deg, #ffffff 0%, #cbd5e1 40%, #38bdf8 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .gradient-accent-text {
            background: linear-gradient(135deg, #38bdf8 0%, #818cf8 50%, #f97316 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .bg-grid-pattern {
            background-size: 40px 40px;
            background-image: 
                linear-gradient(to right, rgba(255, 255, 255, 0.03) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(255, 255, 255, 0.03) 1px, transparent 1px);
        }

        .radar-grid {
            background: radial-gradient(circle, rgba(56, 189, 248, 0.05) 0%, transparent 70%);
        }
    </style>
</head>
<body class="bg-navy-950 text-slate-100 antialiased overflow-x-hidden selection:bg-cyan-500 selection:text-black">

    <!-- Ambient Glow Pods -->
    <div class="fixed top-0 left-1/2 -translate-x-1/2 w-[1000px] h-[500px] bg-gradient-to-tr from-cyan-600/15 via-indigo-600/10 to-orange-500/10 rounded-full blur-[140px] pointer-events-none z-0"></div>
    <div class="fixed top-[40%] right-[-10%] w-[600px] h-[600px] bg-cyan-500/10 rounded-full blur-[160px] pointer-events-none z-0"></div>
    <div class="fixed bottom-[-10%] left-[-10%] w-[700px] h-[700px] bg-purple-600/10 rounded-full blur-[160px] pointer-events-none z-0"></div>

    <!-- Navigation Header -->
    <header class="sticky top-0 z-50 backdrop-blur-xl bg-navy-950/75 border-b border-white/5 transition-all">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
            <!-- Brand Logo -->
            <a href="/" class="flex items-center gap-3 group">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-cyan-500 via-indigo-500 to-orange-500 p-0.5 shadow-lg shadow-cyan-500/20 group-hover:shadow-cyan-500/40 transition">
                    <div class="w-full h-full bg-navy-950 rounded-[10px] flex items-center justify-center">
                        <svg class="w-5 h-5 text-cyan-400 transform group-hover:scale-110 transition duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                        </svg>
                    </div>
                </div>
                <div class="flex flex-col">
                    <span class="font-heading font-black text-xl tracking-tight text-white flex items-center gap-1.5">
                        V-AIR OPS <span class="text-[10px] font-mono uppercase bg-cyan-500/10 text-cyan-400 border border-cyan-500/30 px-1.5 py-0.5 rounded font-bold">PLATFORM</span>
                    </span>
                    <span class="text-[10px] text-slate-400 font-mono tracking-wider">NEXT-GEN VA INFRASTRUCTURE</span>
                </div>
            </a>

            <!-- Desktop Nav Links -->
            <nav class="hidden md:flex items-center gap-8 text-sm font-medium text-slate-300">
                <a href="#features" class="hover:text-cyan-400 transition">Features</a>
                <a href="#dispatch" class="hover:text-cyan-400 transition">Smart Dispatch</a>
                <a href="#portal" class="hover:text-cyan-400 transition">Pilot Portal</a>
                <a href="#radar" class="hover:text-cyan-400 transition">Live ACARS</a>
                <a href="#infrastructure" class="hover:text-cyan-400 transition">Enterprise Ops</a>
            </nav>

            <!-- Authentication / CTAs -->
            <div class="flex items-center gap-4">
                <?php if (auth()->check()): ?>
                    <a href="{{ url('/dashboard') }}" class="px-5 py-2.5 rounded-xl bg-cyan-500/10 hover:bg-cyan-500/20 text-cyan-400 border border-cyan-500/30 text-xs font-bold font-mono transition">
                        GO TO CONSOLE &rarr;
                    </a>
                <?php else: ?>
                    <a href="{{ route('login') }}" class="text-xs font-bold text-slate-300 hover:text-white px-4 py-2 transition">
                        Sign In
                    </a>
                    <a href="{{ route('register') }}" class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-cyan-500 via-indigo-500 to-orange-500 hover:opacity-95 text-white font-heading font-bold text-xs shadow-lg shadow-cyan-500/25 transition">
                        Start 14-Day Trial
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- SECTION 1: HERO SECTION -->
    <section class="relative pt-16 pb-24 lg:pt-24 lg:pb-32 bg-grid-pattern overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="text-center max-w-4xl mx-auto space-y-8">
                
                <!-- Badge -->
                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full glass-card border-cyan-500/30 text-xs text-cyan-300 shadow-xl">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                    <span class="font-mono font-semibold uppercase tracking-wider text-[11px]">Built for MSFS 2024 &amp; X-Plane 12 High-Fidelity Airliners</span>
                    <span class="text-slate-500">&bull;</span>
                    <span class="text-orange-400 font-bold">PMDG &bull; iFly &bull; Fenix Ready</span>
                </div>

                <!-- Main Heading -->
                <h1 class="font-heading font-black text-4xl sm:text-6xl lg:text-7xl tracking-tight leading-[1.08]">
                    Run Your Virtual Airline <br class="hidden sm:inline" />
                    <span class="gradient-accent-text">Like the Real Thing.</span>
                </h1>

                <!-- Subheadline -->
                <p class="text-slate-300 text-lg sm:text-xl max-w-2xl mx-auto leading-relaxed font-light">
                    The modern, high-performance SaaS platform for virtual aviation communities. Smart SimBrief dispatching, sub-second telemetry ACARS, automated pilot rosters, and zero-maintenance cloud infrastructure.
                </p>

                <!-- Call To Actions -->
                <div class="flex flex-wrap items-center justify-center gap-4 pt-4">
                    <a href="/register" class="px-8 py-4 rounded-2xl bg-gradient-to-r from-cyan-500 via-indigo-500 to-orange-500 hover:opacity-95 text-white font-heading font-extrabold text-base shadow-2xl shadow-cyan-500/30 hover:scale-[1.02] transition flex items-center gap-3">
                        <span>Start 14-Day Free Trial</span>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                    </a>

                    <a href="#features" class="px-8 py-4 rounded-2xl glass-card hover:bg-white/10 text-slate-200 font-heading font-bold text-base border border-white/10 transition flex items-center gap-2">
                        <svg class="w-5 h-5 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <span>Explore Features</span>
                    </a>
                </div>

                <!-- Micro Proof Text -->
                <div class="flex items-center justify-center gap-6 text-xs text-slate-400 font-mono pt-2">
                    <span class="flex items-center gap-1.5"><svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg> No Credit Card Required</span>
                    <span class="flex items-center gap-1.5"><svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg> Instant 2-Minute Setup</span>
                    <span class="flex items-center gap-1.5"><svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg> 99.99% Event Uptime</span>
                </div>
            </div>

            <!-- Hero Mockup Container -->
            <div class="mt-16 lg:mt-24 relative max-w-6xl mx-auto">
                <div class="absolute -inset-1.5 bg-gradient-to-r from-cyan-500 via-indigo-500 to-orange-500 rounded-3xl blur-xl opacity-30 animate-pulse-slow"></div>
                
                <div class="relative rounded-2xl glass-card border border-white/15 overflow-hidden shadow-2xl">
                    <!-- Window Header Controls -->
                    <div class="bg-navy-900/90 border-b border-white/10 px-4 py-3 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-red-500/80"></span>
                            <span class="w-3 h-3 rounded-full bg-yellow-500/80"></span>
                            <span class="w-3 h-3 rounded-full bg-green-500/80"></span>
                            <span class="text-xs font-mono text-slate-400 ml-2">vops-console.app/admin/live-radar</span>
                        </div>
                        <div class="flex items-center gap-3 text-xs font-mono text-cyan-400">
                            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                            <span>ACARS STREAM: ACTIVE (128 FLIGHTS IN AIR)</span>
                        </div>
                    </div>

                    <!-- Mockup Inner Content -->
                    <div class="p-6 bg-navy-950/90 space-y-6">
                        <!-- Top Stat Bar inside Mockup -->
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div class="p-4 rounded-xl bg-navy-900/60 border border-white/5">
                                <span class="text-[10px] font-mono uppercase text-slate-400 block">Enroute Flights</span>
                                <span class="text-2xl font-bold font-mono text-cyan-400">142 FLTS</span>
                            </div>
                            <div class="p-4 rounded-xl bg-navy-900/60 border border-white/5">
                                <span class="text-[10px] font-mono uppercase text-slate-400 block">Network Fleet Load</span>
                                <span class="text-2xl font-bold font-mono text-purple-400">89.4%</span>
                            </div>
                            <div class="p-4 rounded-xl bg-navy-900/60 border border-white/5">
                                <span class="text-[10px] font-mono uppercase text-slate-400 block">Avg Landing Rate</span>
                                <span class="text-2xl font-bold font-mono text-emerald-400">-142 FPM</span>
                            </div>
                            <div class="p-4 rounded-xl bg-navy-900/60 border border-white/5">
                                <span class="text-[10px] font-mono uppercase text-slate-400 block">System Health</span>
                                <span class="text-2xl font-bold font-mono text-orange-400">100% OK</span>
                            </div>
                        </div>

                        <!-- Dark Live Map Mockup -->
                        <div class="h-[380px] sm:h-[460px] rounded-xl bg-[#070b14] border border-white/10 relative overflow-hidden flex items-center justify-center radar-grid">
                            <!-- Simulated Vector Paths -->
                            <svg class="absolute inset-0 w-full h-full opacity-40" viewBox="0 0 1000 500" fill="none">
                                <path d="M 150 350 Q 300 150 500 200 T 850 120" stroke="#a855f7" stroke-width="2" stroke-dasharray="6,6"/>
                                <path d="M 200 400 Q 450 300 750 180" stroke="#38bdf8" stroke-width="3"/>
                                <path d="M 100 180 Q 400 120 800 380" stroke="#22c55e" stroke-width="2"/>
                            </svg>

                            <!-- Live Flight Pins -->
                            <div class="absolute top-[22%] left-[48%] animate-float flex items-center gap-2 bg-navy-900/90 border border-cyan-500/40 px-3 py-1.5 rounded-lg shadow-2xl z-20">
                                <span class="w-2 h-2 rounded-full bg-cyan-400 animate-pulse"></span>
                                <div class="text-left font-mono">
                                    <span class="text-xs font-bold text-white block">SVK101 &bull; B738</span>
                                    <span class="text-[10px] text-cyan-300">FL360 &bull; 460 KTS &bull; EGLL &rarr; LFPG</span>
                                </div>
                            </div>

                            <div class="absolute top-[52%] left-[28%] flex items-center gap-2 bg-navy-900/90 border border-purple-500/40 px-3 py-1.5 rounded-lg shadow-2xl z-20">
                                <span class="w-2 h-2 rounded-full bg-purple-400"></span>
                                <div class="text-left font-mono">
                                    <span class="text-xs font-bold text-white block">EZS8999 &bull; A320</span>
                                    <span class="text-[10px] text-purple-300">FL380 &bull; 442 KTS &bull; LFLL &rarr; EGKK</span>
                                </div>
                            </div>

                            <div class="absolute bottom-[28%] right-[22%] flex items-center gap-2 bg-navy-900/90 border border-emerald-500/40 px-3 py-1.5 rounded-lg shadow-2xl z-20">
                                <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                                <div class="text-left font-mono">
                                    <span class="text-xs font-bold text-white block">DLH404 &bull; A359</span>
                                    <span class="text-[10px] text-emerald-300">FL410 &bull; 488 KTS &bull; EDDF &rarr; KJFK</span>
                                </div>
                            </div>

                            <!-- Map Overlay Badge -->
                            <div class="absolute bottom-4 left-4 bg-navy-950/80 backdrop-blur border border-white/10 px-4 py-2 rounded-xl text-xs font-mono text-slate-300">
                                Global Live Radar Stream &bull; Sub-Second Telemetry Sync
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- SECTION 2: LIVE STATISTICS BAR (SOCIAL PROOF) -->
    <section class="py-12 bg-navy-900/80 border-y border-white/10 relative z-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center divide-x divide-white/5">
                <div class="space-y-1">
                    <span class="font-heading font-black text-3xl sm:text-5xl text-cyan-400 font-mono">1,420+</span>
                    <span class="text-xs font-mono uppercase tracking-wider text-slate-400 block">Active Flights Today</span>
                </div>
                <div class="space-y-1 pl-4">
                    <span class="font-heading font-black text-3xl sm:text-5xl text-purple-400 font-mono">38,500+</span>
                    <span class="text-xs font-mono uppercase tracking-wider text-slate-400 block">Registered Pilots</span>
                </div>
                <div class="space-y-1 pl-4">
                    <span class="font-heading font-black text-3xl sm:text-5xl text-orange-400 font-mono">1.8M+</span>
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
    <section id="features" class="py-24 lg:py-32 relative z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-16">
            
            <div class="text-center max-w-3xl mx-auto space-y-4">
                <span class="text-xs font-mono uppercase tracking-widest text-cyan-400 font-bold bg-cyan-500/10 px-3 py-1 rounded-full border border-cyan-500/20">
                    COMPLETE VIRTUAL AIRLINE SUITE
                </span>
                <h2 class="font-heading font-black text-3xl sm:text-5xl text-white tracking-tight">
                    Everything You Need to Scale Your Airline
                </h2>
                <p class="text-slate-400 text-base sm:text-lg">
                    Ditch legacy spreadsheets and outdated PHP scripts. V-Ops provides an enterprise SaaS backbone built specifically for virtual airline managers and dedicated flight simmers.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                <!-- Feature 1 -->
                <div class="glass-card glass-card-hover p-8 rounded-2xl space-y-4 relative overflow-hidden group">
                    <div class="w-14 h-14 rounded-2xl bg-cyan-500/10 border border-cyan-500/30 flex items-center justify-center text-cyan-400">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    </div>
                    <h3 class="font-heading font-bold text-xl text-white">Smart SimBrief Dispatch</h3>
                    <p class="text-slate-400 text-sm leading-relaxed">
                        One-click official LIDO flight plan generation, automated route validation, real-time ETOPS calculations, and passenger/cargo payload balancing.
                    </p>
                </div>

                <!-- Feature 2 -->
                <div class="glass-card glass-card-hover p-8 rounded-2xl space-y-4 relative overflow-hidden group">
                    <div class="w-14 h-14 rounded-2xl bg-purple-500/10 border border-purple-500/30 flex items-center justify-center text-purple-400">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                    </div>
                    <h3 class="font-heading font-bold text-xl text-white">Real-Time ACARS Telemetry</h3>
                    <p class="text-slate-400 text-sm leading-relaxed">
                        Lightweight background tracking client compatible across MSFS 2024, X-Plane 12, and P3D. Captures pitch, bank, G-force, and fuel burn sub-secondly.
                    </p>
                </div>

                <!-- Feature 3 -->
                <div class="glass-card glass-card-hover p-8 rounded-2xl space-y-4 relative overflow-hidden group">
                    <div class="w-14 h-14 rounded-2xl bg-orange-500/10 border border-orange-500/30 flex items-center justify-center text-orange-400">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    </div>
                    <h3 class="font-heading font-bold text-xl text-white">Advanced Flight Analytics</h3>
                    <p class="text-slate-400 text-sm leading-relaxed">
                        Detailed vertical profile graphs, touchdown FPM scoring, overspeed/stall penalty detection, and automated pilot logbook audits.
                    </p>
                </div>

                <!-- Feature 4 -->
                <div class="glass-card glass-card-hover p-8 rounded-2xl space-y-4 relative overflow-hidden group">
                    <div class="w-14 h-14 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 flex items-center justify-center text-emerald-400">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
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
    <section id="portal" class="py-24 lg:py-32 bg-navy-900/60 border-y border-white/5 relative z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
                
                <!-- Left Details -->
                <div class="space-y-8">
                    <div class="space-y-4">
                        <span class="text-xs font-mono uppercase tracking-widest text-orange-400 font-bold bg-orange-500/10 px-3 py-1 rounded-full border border-orange-500/20">
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
                            <div class="w-10 h-10 rounded-xl bg-cyan-500/10 border border-cyan-500/30 flex items-center justify-center text-cyan-400 shrink-0 mt-1">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            </div>
                            <div class="space-y-1">
                                <h4 class="font-heading font-bold text-lg text-white">Automated Rank Progression &amp; Badges</h4>
                                <p class="text-slate-400 text-sm">Set custom hour thresholds and points criteria. Pilots automatically unlock ranks and type ratings as they log flight hours.</p>
                            </div>
                        </div>

                        <!-- Item 2 -->
                        <div class="flex gap-4 items-start">
                            <div class="w-10 h-10 rounded-xl bg-purple-500/10 border border-purple-500/30 flex items-center justify-center text-purple-400 shrink-0 mt-1">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                            </div>
                            <div class="space-y-1">
                                <h4 class="font-heading font-bold text-lg text-white">Native Discord Bot &amp; Role Syncing</h4>
                                <p class="text-slate-400 text-sm">Automatically grant Discord roles when pilots join or rank up. Post flight dispatch notifications and landing debriefs directly into community channels.</p>
                            </div>
                        </div>

                        <!-- Item 3 -->
                        <div class="flex gap-4 items-start">
                            <div class="w-10 h-10 rounded-xl bg-orange-500/10 border border-orange-500/30 flex items-center justify-center text-orange-400 shrink-0 mt-1">
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
                <div class="glass-card border border-white/10 rounded-2xl p-6 sm:p-8 space-y-6">
                    <div class="flex items-center justify-between border-b border-white/10 pb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-gradient-to-tr from-cyan-500 to-indigo-500 font-bold flex items-center justify-center text-white font-heading">
                                CPT
                            </div>
                            <div>
                                <h4 class="font-heading font-bold text-white text-sm">Capt. Alexander Wright</h4>
                                <span class="text-xs font-mono text-cyan-400">Senior Captain &bull; SVK-104</span>
                            </div>
                        </div>
                        <span class="px-3 py-1 rounded-full bg-emerald-500/20 text-emerald-400 text-xs font-mono font-bold border border-emerald-500/30">
                            RANK: SENIOR COMMANDER
                        </span>
                    </div>

                    <!-- Progress Cards -->
                    <div class="space-y-4 text-xs font-mono">
                        <div>
                            <div class="flex justify-between text-slate-300 mb-1">
                                <span>Flight Hours Progress</span>
                                <span class="text-cyan-400 font-bold">1,240h / 1,500h</span>
                            </div>
                            <div class="w-full bg-navy-950 h-2.5 rounded-full overflow-hidden border border-white/5">
                                <div class="bg-gradient-to-r from-cyan-500 to-indigo-500 h-full w-[82%] rounded-full"></div>
                            </div>
                        </div>

                        <div>
                            <div class="flex justify-between text-slate-300 mb-1">
                                <span>Landing Rating Score</span>
                                <span class="text-emerald-400 font-bold">98.5% (Butter Average)</span>
                            </div>
                            <div class="w-full bg-navy-950 h-2.5 rounded-full overflow-hidden border border-white/5">
                                <div class="bg-gradient-to-r from-emerald-500 to-cyan-500 h-full w-[98%] rounded-full"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Flights Log Preview -->
                    <div class="space-y-3 pt-2">
                        <span class="text-[11px] font-mono text-slate-400 uppercase tracking-wider block font-bold">Recent Verified PIREPs</span>
                        
                        <div class="p-3 bg-navy-950/80 rounded-xl border border-white/5 flex items-center justify-between text-xs font-mono">
                            <div class="flex items-center gap-3">
                                <span class="text-cyan-400 font-bold">EGLL &rarr; LFPG</span>
                                <span class="text-slate-400">A320</span>
                            </div>
                            <span class="text-emerald-400 font-bold">-112 FPM</span>
                            <span class="text-slate-300">01:15 &bull; +100 PTS</span>
                        </div>

                        <div class="p-3 bg-navy-950/80 rounded-xl border border-white/5 flex items-center justify-between text-xs font-mono">
                            <div class="flex items-center gap-3">
                                <span class="text-cyan-400 font-bold">EDDF &rarr; KJFK</span>
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

    <!-- SECTION 5: INTERACTIVE ELEMENT / MAP PREVIEW -->
    <section id="radar" class="py-24 lg:py-32 relative z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
            <div class="text-center max-w-3xl mx-auto space-y-4">
                <span class="text-xs font-mono uppercase tracking-widest text-emerald-400 font-bold bg-emerald-500/10 px-3 py-1 rounded-full border border-emerald-500/20">
                    REAL-TIME RADAR OVERVIEW
                </span>
                <h2 class="font-heading font-black text-3xl sm:text-5xl text-white tracking-tight">
                    Watch Your Entire Fleet Fly in Real Time
                </h2>
                <p class="text-slate-400 text-base sm:text-lg">
                    Our high-resolution live map provides flight dispatchers and admins complete situational awareness across the globe with interactive flight tracking.
                </p>
            </div>

            <!-- Stylized Interactive Map Container -->
            <div class="glass-card border border-white/10 rounded-2xl overflow-hidden shadow-2xl relative">
                <!-- Map Header Bar -->
                <div class="bg-navy-900 border-b border-white/10 p-4 flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <span class="w-3 h-3 rounded-full bg-emerald-400 animate-ping"></span>
                        <span class="font-heading font-bold text-white text-sm">GLOBAL FLIGHT MONITOR &bull; NETWORK RADAR</span>
                    </div>
                    <div class="flex items-center gap-4 text-xs font-mono">
                        <span class="text-slate-400">FILTER: <strong class="text-cyan-400">ALL FLEETS</strong></span>
                        <span class="text-slate-400">SYNC: <strong class="text-emerald-400">SUB-SECOND TELEMETRY</strong></span>
                    </div>
                </div>

                <!-- Map Body -->
                <div class="h-[500px] bg-[#070b14] relative radar-grid flex items-center justify-center overflow-hidden">
                    <!-- World Vector Canvas Representation -->
                    <div class="absolute inset-0 opacity-20 bg-[radial-gradient(#38bdf8_1px,transparent_1px)] [background-size:24px_24px]"></div>

                    <!-- Simulated Aircraft Vectors & Telemetry Rings -->
                    <div class="absolute top-[35%] left-[30%] group cursor-pointer">
                        <div class="w-8 h-8 rounded-full bg-cyan-500/20 border border-cyan-400 flex items-center justify-center animate-ping"></div>
                        <div class="absolute top-0 left-0 w-8 h-8 flex items-center justify-center">
                            <svg class="w-5 h-5 text-cyan-400 transform rotate-45" fill="currentColor" viewBox="0 0 24 24"><path d="M21 16v-2l-8-5V3.5c0-.83-.67-1.5-1.5-1.5S10 2.67 10 3.5V9l-8 5v2l8-2.5V19l-2 1.5V22l3.5-1 3.5 1v-1.5L13 19v-5.5l8 2.5z"/></svg>
                        </div>
                        <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 bg-navy-900/90 backdrop-blur border border-cyan-500/40 px-3 py-1.5 rounded-lg text-xs font-mono text-white opacity-90 group-hover:opacity-100 transition whitespace-nowrap shadow-xl">
                            <span class="font-bold text-cyan-400">VOPS-142</span> &bull; B738 &bull; FL360
                        </div>
                    </div>

                    <div class="absolute top-[45%] left-[62%] group cursor-pointer">
                        <div class="w-8 h-8 rounded-full bg-purple-500/20 border border-purple-400 flex items-center justify-center animate-ping"></div>
                        <div class="absolute top-0 left-0 w-8 h-8 flex items-center justify-center">
                            <svg class="w-5 h-5 text-purple-400 transform rotate-90" fill="currentColor" viewBox="0 0 24 24"><path d="M21 16v-2l-8-5V3.5c0-.83-.67-1.5-1.5-1.5S10 2.67 10 3.5V9l-8 5v2l8-2.5V19l-2 1.5V22l3.5-1 3.5 1v-1.5L13 19v-5.5l8 2.5z"/></svg>
                        </div>
                        <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 bg-navy-900/90 backdrop-blur border border-purple-500/40 px-3 py-1.5 rounded-lg text-xs font-mono text-white opacity-90 group-hover:opacity-100 transition whitespace-nowrap shadow-xl">
                            <span class="font-bold text-purple-400">VOPS-892</span> &bull; A320 &bull; FL380
                        </div>
                    </div>

                    <!-- Radar Sweep Ray Animation -->
                    <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                        <div class="w-[700px] h-[700px] rounded-full border border-cyan-500/10 relative animate-radar-sweep">
                            <div class="absolute top-1/2 left-1/2 w-1/2 h-[2px] bg-gradient-to-r from-transparent via-cyan-500/40 to-cyan-400 origin-left"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- SECTION 6: FINAL CTA & FOOTER -->
    <section class="py-24 lg:py-32 relative z-10 overflow-hidden bg-grid-pattern">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="glass-card border border-white/15 rounded-3xl p-8 sm:p-16 text-center relative overflow-hidden space-y-8">
                <!-- Background Accent Glow -->
                <div class="absolute -top-24 -left-24 w-96 h-96 bg-cyan-500/20 rounded-full blur-3xl pointer-events-none"></div>
                <div class="absolute -bottom-24 -right-24 w-96 h-96 bg-orange-500/20 rounded-full blur-3xl pointer-events-none"></div>

                <h2 class="font-heading font-black text-3xl sm:text-5xl lg:text-6xl text-white tracking-tight leading-tight">
                    Ready to Launch Your Airline?
                </h2>
                
                <p class="text-slate-300 text-base sm:text-xl max-w-2xl mx-auto font-light leading-relaxed">
                    Join thousands of virtual airline pilots and community managers already using V-Ops to power their flight ops infrastructure.
                </p>

                <div class="flex flex-wrap items-center justify-center gap-4 pt-4">
                    <a href="/register" class="px-8 py-4 rounded-2xl bg-gradient-to-r from-cyan-500 via-indigo-500 to-orange-500 hover:opacity-95 text-white font-heading font-extrabold text-base shadow-2xl shadow-cyan-500/30 hover:scale-[1.02] transition flex items-center gap-3">
                        <span>Start 14-Day Free Trial</span>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                    </a>
                </div>

                <p class="text-xs font-mono text-slate-400">
                    Instant Setup &bull; Free Migration Support from vAMSYS / phpVMS &bull; No Credit Card Required
                </p>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="border-t border-white/10 bg-navy-950 py-12 relative z-20 text-xs text-slate-400">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
            <div class="grid grid-cols-2 md:grid-cols-5 gap-8">
                <!-- Brand Info -->
                <div class="col-span-2 space-y-4">
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-lg bg-gradient-to-tr from-cyan-500 to-orange-500 flex items-center justify-center text-white">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                        </div>
                        <span class="font-heading font-black text-lg text-white">V-AIR OPS</span>
                    </div>
                    <p class="text-slate-400 max-w-sm leading-relaxed">
                        High-performance Virtual Airline SaaS platform &amp; sub-second ACARS telemetry engine for modern flight simulation communities.
                    </p>
                </div>

                <!-- Links Column 1 -->
                <div class="space-y-3">
                    <h4 class="font-heading font-bold text-white text-xs uppercase tracking-wider">Product</h4>
                    <ul class="space-y-2">
                        <li><a href="#dispatch" class="hover:text-cyan-400 transition">Smart Dispatch</a></li>
                        <li><a href="#radar" class="hover:text-cyan-400 transition">Live ACARS Radar</a></li>
                        <li><a href="#portal" class="hover:text-cyan-400 transition">Pilot Roster</a></li>
                        <li><a href="#infrastructure" class="hover:text-cyan-400 transition">Analytics Engine</a></li>
                    </ul>
                </div>

                <!-- Links Column 2 -->
                <div class="space-y-3">
                    <h4 class="font-heading font-bold text-white text-xs uppercase tracking-wider">Developers &amp; Docs</h4>
                    <ul class="space-y-2">
                        <li><a href="#" class="hover:text-cyan-400 transition">Documentation</a></li>
                        <li><a href="#" class="hover:text-cyan-400 transition">REST API Access</a></li>
                        <li><a href="#" class="hover:text-cyan-400 transition">ACARS Client SDK</a></li>
                        <li><a href="#" class="hover:text-cyan-400 transition">System Status</a></li>
                    </ul>
                </div>

                <!-- Links Column 3 -->
                <div class="space-y-3">
                    <h4 class="font-heading font-bold text-white text-xs uppercase tracking-wider">Legal &amp; Support</h4>
                    <ul class="space-y-2">
                        <li><a href="#" class="hover:text-cyan-400 transition">Terms of Service</a></li>
                        <li><a href="#" class="hover:text-cyan-400 transition">Privacy Policy</a></li>
                        <li><a href="#" class="hover:text-cyan-400 transition">Discord Support</a></li>
                        <li><a href="#" class="hover:text-cyan-400 transition">Security Policy</a></li>
                    </ul>
                </div>
            </div>

            <div class="border-t border-white/5 pt-8 flex flex-wrap items-center justify-between gap-4 font-mono text-[11px]">
                <p>&copy; {{ date('Y') }} V-Air Ops SaaS Platform. All rights reserved. Not affiliated with any real-world airline.</p>
                <div class="flex items-center gap-2 text-emerald-400">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span>ALL SYSTEMS OPERATIONAL</span>
                </div>
            </div>
        </div>
    </footer>

</body>
</html>
