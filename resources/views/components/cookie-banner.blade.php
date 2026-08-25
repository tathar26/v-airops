<div x-data="cookieConsent()" x-init="initConsent()" class="font-sans antialiased text-slate-200">
    <!-- ── Cookie Consent Banner Bar (Bottom Float) ── -->
    <div x-show="showBanner"
         x-transition:enter="transition ease-out duration-300 transform"
         x-transition:enter-start="translate-y-full opacity-0"
         x-transition:enter-end="translate-y-0 opacity-100"
         x-transition:leave="transition ease-in duration-200 transform"
         x-transition:leave-start="translate-y-0 opacity-100"
         x-transition:leave-end="translate-y-full opacity-0"
         x-cloak
         class="fixed bottom-0 inset-x-0 z-[9999] p-3 sm:p-5 pointer-events-none">
        
        <div class="max-w-6xl mx-auto pointer-events-auto bg-slate-900/95 border border-slate-700/80 rounded-2xl shadow-2xl backdrop-blur-xl p-4 sm:p-6 text-slate-200 ring-1 ring-white/10">
            <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-5">
                <!-- Info Section -->
                <div class="flex items-start gap-4 flex-1">
                    <div class="w-10 h-10 rounded-xl bg-amber-500/10 border border-amber-500/30 flex items-center justify-center flex-shrink-0 text-amber-400 shadow-inner">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="space-y-1">
                        <h4 class="text-sm font-bold text-white tracking-wide flex items-center gap-2">
                            <span>Privacy &amp; Cookie Preferences</span>
                            <span class="text-[10px] px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 font-mono">GDPR Compliant</span>
                        </h4>
                        <p class="text-xs text-slate-300 leading-relaxed max-w-3xl">
                            We use cookies and telemetry storage to ensure core flight dispatching functions, remember your virtual airline settings, analyze ACARS performance, and cache flight plans. You can customize your preferences or accept all cookies.
                        </p>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex flex-wrap items-center gap-2 sm:gap-3 w-full lg:w-auto justify-end flex-shrink-0">
                    <button @click="openPreferencesModal()" type="button"
                            class="px-4 py-2 text-xs font-semibold text-slate-300 hover:text-white bg-slate-800/80 hover:bg-slate-700/80 border border-slate-600/60 rounded-xl transition shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-500">
                        Customize
                    </button>
                    <button @click="rejectNonEssential()" type="button"
                            class="px-4 py-2 text-xs font-semibold text-slate-300 hover:text-white bg-slate-800/80 hover:bg-slate-700/80 border border-slate-600/60 rounded-xl transition shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-500">
                        Essential Only
                    </button>
                    <button @click="acceptAll()" type="button"
                            class="px-5 py-2 text-xs font-bold text-slate-950 bg-gradient-to-r from-amber-400 to-amber-300 hover:from-amber-300 hover:to-amber-200 rounded-xl transition shadow-lg shadow-amber-500/20 focus:outline-none focus:ring-2 focus:ring-amber-400">
                        Accept All
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Cookie Preferences Modal (Customization) ── -->
    <div x-show="showModal"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         x-cloak
         class="fixed inset-0 z-[10000] overflow-y-auto bg-black/80 backdrop-blur-md flex items-center justify-center p-4">
        
        <div @click.outside="showModal = false"
             x-show="showModal"
             x-transition:enter="transition ease-out duration-200 transform"
             x-transition:enter-start="scale-95 opacity-0"
             x-transition:enter-end="scale-100 opacity-100"
             x-transition:leave="transition ease-in duration-150 transform"
             x-transition:leave-start="scale-100 opacity-100"
             x-transition:leave-end="scale-95 opacity-0"
             class="bg-slate-900 border border-slate-700/80 rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] flex flex-col overflow-hidden text-slate-200">
            
            <!-- Modal Header -->
            <div class="px-6 py-4 border-b border-slate-800 flex items-center justify-between bg-slate-950/50">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-amber-500/10 border border-amber-500/30 flex items-center justify-center text-amber-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-white">Cookie Consent Preferences</h3>
                        <p class="text-xs text-slate-400">Manage what cookies and local storage tokens we store</p>
                    </div>
                </div>
                <button @click="showModal = false" type="button" class="text-slate-400 hover:text-white p-1 rounded-lg hover:bg-slate-800 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Modal Body / Accordion Options -->
            <div class="p-6 overflow-y-auto space-y-4 flex-1 text-xs">
                <!-- 1. Strictly Necessary -->
                <div class="p-4 rounded-xl bg-slate-950/40 border border-slate-800 space-y-2">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-sm text-slate-100">Strictly Necessary Cookies</span>
                            <span class="text-[10px] px-2 py-0.5 rounded bg-sky-500/10 text-sky-400 border border-sky-500/20 font-mono">Always Active</span>
                        </div>
                        <div class="relative inline-flex items-center cursor-not-allowed opacity-75">
                            <input type="checkbox" checked disabled class="sr-only">
                            <div class="w-9 h-5 bg-sky-600 rounded-full"></div>
                            <div class="dot absolute left-4.5 top-0.5 bg-white w-4 h-4 rounded-full transition"></div>
                        </div>
                    </div>
                    <p class="text-slate-400 leading-relaxed">
                        Essential for basic platform navigation, user authentication (<code class="text-slate-300">vops_session</code>, <code class="text-slate-300">XSRF-TOKEN</code>), secure CSRF protection, and virtual airline tenant routing. These cannot be switched off.
                    </p>
                </div>

                <!-- 2. Functional & UI Preferences -->
                <div class="p-4 rounded-xl bg-slate-950/40 border border-slate-800 space-y-2">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-sm text-slate-100">Functional &amp; UI Preferences</span>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" x-model="preferences.functional" class="sr-only peer">
                            <div class="w-9 h-5 bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-amber-400"></div>
                        </label>
                    </div>
                    <p class="text-slate-400 leading-relaxed">
                        Remembers your selected Virtual Airline switcher view, interface theme parameters, live radar map center coordinates/zoom level, and audio alert sound preferences.
                    </p>
                </div>

                <!-- 3. Telemetry & Analytics -->
                <div class="p-4 rounded-xl bg-slate-950/40 border border-slate-800 space-y-2">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-sm text-slate-100">ACARS Telemetry &amp; Diagnostics</span>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" x-model="preferences.analytics" class="sr-only peer">
                            <div class="w-9 h-5 bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-amber-400"></div>
                        </label>
                    </div>
                    <p class="text-slate-400 leading-relaxed">
                        Allows anonymized telemetry performance metrics, landing rate touchdown diagnostic logs, and ACARS ping latency statistics to help us optimize flight tracking speed and prevent sim stuttering.
                    </p>
                </div>

                <!-- 4. External Services -->
                <div class="p-4 rounded-xl bg-slate-950/40 border border-slate-800 space-y-2">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-sm text-slate-100">External Flight Services Integration</span>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" x-model="preferences.marketing" class="sr-only peer">
                            <div class="w-9 h-5 bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-amber-400"></div>
                        </label>
                    </div>
                    <p class="text-slate-400 leading-relaxed">
                        Enables cached integrations for SimBrief OFP flight plans, VATSIM / IVAO pilot status checks, and AirLabs worldwide route data caching.
                    </p>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-4 border-t border-slate-800 bg-slate-950/50 flex flex-wrap items-center justify-between gap-3">
                <button @click="rejectNonEssential()" type="button"
                        class="px-4 py-2 text-xs font-semibold text-slate-400 hover:text-white transition">
                    Reject All Optional
                </button>
                <div class="flex items-center gap-2">
                    <button @click="saveCustomPreferences()" type="button"
                            class="px-5 py-2 text-xs font-bold text-slate-900 bg-gradient-to-r from-amber-400 to-amber-300 hover:from-amber-300 hover:to-amber-200 rounded-xl transition shadow-lg shadow-amber-500/20">
                        Save My Choices
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Floating Re-Open Trigger Badge (Bottom Left) ── -->
    <button @click="openPreferencesModal()" type="button"
            x-show="!showBanner"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-90"
            x-transition:enter-end="opacity-100 scale-100"
            x-cloak
            class="fixed bottom-4 left-4 z-40 p-2.5 rounded-full bg-slate-900/80 hover:bg-slate-800 text-slate-400 hover:text-amber-400 border border-slate-700/60 shadow-xl backdrop-blur-md transition-all group focus:outline-none focus:ring-2 focus:ring-amber-400"
            title="Privacy &amp; Cookie Settings">
        <svg class="w-4 h-4 transition group-hover:rotate-45" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
        </svg>
    </button>
</div>

<script>
function cookieConsent() {
    return {
        showBanner: false,
        showModal: false,
        preferences: {
            necessary: true,
            functional: true,
            analytics: true,
            marketing: false
        },

        initConsent() {
            const stored = localStorage.getItem('vops_cookie_consent');
            if (!stored) {
                this.showBanner = true;
            } else {
                try {
                    const parsed = JSON.parse(stored);
                    if (parsed && typeof parsed === 'object') {
                        this.preferences = {
                            necessary: true,
                            functional: parsed.functional ?? true,
                            analytics: parsed.analytics ?? true,
                            marketing: parsed.marketing ?? false
                        };
                    }
                } catch (e) {
                    this.showBanner = true;
                }
            }

            // Expose globally so buttons in footer or profile can reopen preferences
            window.openCookieSettings = () => {
                this.openPreferencesModal();
            };
        },

        openPreferencesModal() {
            this.showModal = true;
        },

        acceptAll() {
            this.preferences = {
                necessary: true,
                functional: true,
                analytics: true,
                marketing: true
            };
            this.saveConsent(this.preferences);
        },

        rejectNonEssential() {
            this.preferences = {
                necessary: true,
                functional: false,
                analytics: false,
                marketing: false
            };
            this.saveConsent(this.preferences);
        },

        saveCustomPreferences() {
            this.preferences.necessary = true;
            this.saveConsent(this.preferences);
        },

        saveConsent(consent) {
            const payload = {
                ...consent,
                timestamp: new Date().toISOString()
            };
            // 1. Save to LocalStorage
            localStorage.setItem('vops_cookie_consent', JSON.stringify(payload));

            // 2. Set Cookie (1 Year Expiration)
            const date = new Date();
            date.setTime(date.getTime() + (365 * 24 * 60 * 60 * 1000));
            document.cookie = `vops_cookie_consent=${encodeURIComponent(JSON.stringify(payload))}; expires=${date.toUTCString()}; path=/; SameSite=Lax`;

            this.showBanner = false;
            this.showModal = false;

            // Dispatch global event for any listening services
            window.dispatchEvent(new CustomEvent('cookie-consent-updated', { detail: payload }));
        }
    };
}
</script>
