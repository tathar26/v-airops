<nav class="flex-grow flex flex-col pt-5 pb-4 overflow-y-auto">
    <!-- Brand -->
    <div class="px-5 mb-6 hidden md:block">
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

    <!-- Navigation Links -->
    <ul class="flex-grow space-y-0.5 px-2">

        <!-- Dashboard -->
        <li>
            <a href="{{ route('dashboard') }}"
               class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                <span>Dashboard</span>
            </a>
        </li>

        @if (auth()->check() && auth()->user()->hasRole('Master Admin'))
        <!-- Platform Administration -->
        <li class="nav-section-label">Platform Administration</li>
        <li>
            <a href="{{ route('admin.dashboard') }}"
               class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                <span>System Overview</span>
            </a>
        </li>
        <li>
            <a href="{{ route('global-network') }}"
               class="nav-link {{ request()->routeIs('global-network') ? 'active' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>Global Network</span>
            </a>
        </li>
        <li>
            <a href="{{ route('admin.email-queue') }}"
               class="nav-link {{ request()->routeIs('admin.email-queue') ? 'active' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                <span>Email Queue</span>
            </a>
        </li>
        @endif

        <!-- Pilot Portal -->
        <li class="nav-section-label">Pilot Portal</li>

        @php
            $isProfileActive = request()->routeIs('profile.dashboard') || request()->routeIs('profile.map') || request()->routeIs('profile.statistics') || request()->routeIs('profile.awards') || request()->routeIs('profile.pireps') || request()->routeIs('profile.preferences') || request()->routeIs('profile.account');
        @endphp
        <li x-data="{ open: {{ $isProfileActive ? 'true' : 'false' }} }">
            <button @click="open = !open"
                class="nav-link w-full justify-between {{ $isProfileActive ? 'active' : '' }}">
                <div class="flex items-center gap-3">
                    <svg class="w-4 h-4 flex-shrink-0 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    <span>My Profile</span>
                </div>
                <svg class="w-3.5 h-3.5 opacity-60 transition-transform duration-200 flex-shrink-0" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <ul x-show="open" x-transition class="mt-0.5 space-y-0.5 pl-9 pr-2 pb-1 bg-black/10 rounded-lg">
                <li><a href="{{ route('profile.dashboard') }}" class="block px-2.5 py-1.5 rounded text-xs font-medium transition-colors {{ request()->routeIs('profile.dashboard') ? 'bg-black/25 font-bold' : 'opacity-80 hover:opacity-100 hover:bg-black/10' }}">Dashboard</a></li>
                <li><a href="{{ route('profile.map') }}" class="block px-2.5 py-1.5 rounded text-xs font-medium transition-colors {{ request()->routeIs('profile.map') ? 'bg-black/25 font-bold' : 'opacity-80 hover:opacity-100 hover:bg-black/10' }}">Map</a></li>
                <li><a href="{{ route('profile.statistics') }}" class="block px-2.5 py-1.5 rounded text-xs font-medium transition-colors {{ request()->routeIs('profile.statistics') ? 'bg-black/25 font-bold' : 'opacity-80 hover:opacity-100 hover:bg-black/10' }}">Statistics</a></li>
                <li><a href="{{ route('profile.awards') }}" class="block px-2.5 py-1.5 rounded text-xs font-medium transition-colors {{ request()->routeIs('profile.awards') ? 'bg-black/25 font-bold' : 'opacity-80 hover:opacity-100 hover:bg-black/10' }}">Awards</a></li>
                <li><a href="{{ route('profile.pireps') }}" class="block px-2.5 py-1.5 rounded text-xs font-medium transition-colors {{ request()->routeIs('profile.pireps') ? 'bg-black/25 font-bold' : 'opacity-80 hover:opacity-100 hover:bg-black/10' }}">PIREPs</a></li>
                <li><a href="{{ route('profile.preferences') }}" class="block px-2.5 py-1.5 rounded text-xs font-medium transition-colors {{ request()->routeIs('profile.preferences') ? 'bg-black/25 font-bold' : 'opacity-80 hover:opacity-100 hover:bg-black/10' }}">Preferences</a></li>
                <li><a href="{{ route('profile.account') }}" class="block px-2.5 py-1.5 rounded text-xs font-medium transition-colors {{ request()->routeIs('profile.account') ? 'bg-black/25 font-bold' : 'opacity-80 hover:opacity-100 hover:bg-black/10' }}">Account Settings</a></li>
            </ul>
        </li>

        <!-- Flight Centre -->
        @php
            $isFlightCentreActive = request()->routeIs('flight-centre.*') || request()->routeIs('profile.dispatch');
            $activeBooking = auth()->check() ? \App\Models\Booking::where('user_id', auth()->id())->whereIn('status', ['pending', 'dispatched'])->latest()->first() : null;
        @endphp
        <li x-data="{ open: {{ $isFlightCentreActive ? 'true' : 'false' }} }">
            <button @click="open = !open"
                class="nav-link w-full justify-between {{ $isFlightCentreActive ? 'active' : '' }}">
                <div class="flex items-center gap-3">
                    <svg class="w-4 h-4 flex-shrink-0 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                    <span>Flight Centre</span>
                </div>
                <svg class="w-3.5 h-3.5 opacity-60 transition-transform duration-200 flex-shrink-0" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <ul x-show="open" x-transition class="mt-0.5 space-y-0.5 pl-9 pr-2 pb-1 bg-black/10 rounded-lg">
                <li><a href="{{ route('flight-centre.index') }}" class="block px-2.5 py-1.5 rounded text-xs font-medium transition-colors {{ request()->routeIs('flight-centre.index') ? 'bg-black/25 font-bold' : 'opacity-80 hover:opacity-100 hover:bg-black/10' }}">Overview</a></li>
                @if($activeBooking)
                    <li>
                        <a href="{{ route('profile.dispatch', $activeBooking->id) }}"
                           class="flex items-center justify-between px-2.5 py-1.5 rounded text-xs font-bold bg-emerald-500/20 text-emerald-200 hover:bg-emerald-500/30 transition-colors">
                            <span>✈ Active Flight</span>
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                        </a>
                    </li>
                @else
                    <li><a href="{{ route('flight-centre.book') }}" class="block px-2.5 py-1.5 rounded text-xs font-medium transition-colors {{ request()->routeIs('flight-centre.book') ? 'bg-black/25 font-bold' : 'opacity-80 hover:opacity-100 hover:bg-black/10' }}">Book a Flight</a></li>
                @endif
                <li><a href="{{ route('flight-centre.flights') }}" class="block px-2.5 py-1.5 rounded text-xs font-medium transition-colors {{ request()->routeIs('flight-centre.flights') ? 'bg-black/25 font-bold' : 'opacity-80 hover:opacity-100 hover:bg-black/10' }}">Flights List</a></li>
                <li><a href="{{ route('flight-centre.destinations') }}" class="block px-2.5 py-1.5 rounded text-xs font-medium transition-colors {{ request()->routeIs('flight-centre.destinations') ? 'bg-black/25 font-bold' : 'opacity-80 hover:opacity-100 hover:bg-black/10' }}">Destination Map</a></li>
            </ul>
        </li>

        <!-- NOTAMs -->
        <li>
            <a href="#" class="nav-link opacity-50 cursor-not-allowed">
                <svg class="w-4 h-4 flex-shrink-0 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                <span>NOTAMs</span>
                <span class="ml-auto text-[9px] bg-black/30 px-1.5 py-0.5 rounded font-mono">Soon</span>
            </a>
        </li>

        @php
            $user = auth()->user();
            $canViewFleet = $user && ($user->hasAirlinePermission('view_fleet') || $user->hasAirlinePermission('manage_fleet'));
            $canViewRoutes = $user && ($user->hasAirlinePermission('view_routes') || $user->hasAirlinePermission('create_routes') || $user->hasAirlinePermission('edit_routes'));
            $canViewAirports = $user && ($user->hasAirlinePermission('view_airports') || $user->hasAirlinePermission('manage_airports'));
            $canViewPireps = $user && ($user->hasAirlinePermission('view_pireps') || $user->hasAirlinePermission('review_pireps'));
            $canViewSettings = $user && ($user->hasAirlinePermission('view_settings') || $user->hasAirlinePermission('manage_airline_settings') || $user->hasAirlinePermission('manage_roles') || $user->hasAirlinePermission('manage_ranks') || $user->hasAirlinePermission('manage_pilots'));
            $showAirlineManagement = $user && ($user->isSystemAdmin() || $user->hasRole('VA Owner') || $canViewFleet || $canViewRoutes || $canViewAirports || $canViewPireps || $canViewSettings);
        @endphp

        @if ($showAirlineManagement)
        <!-- Airline Management -->
        <li class="nav-section-label">Airline Management</li>

        <!-- Fleet Dropdown -->
        @if ($user->isSystemAdmin() || $canViewFleet)
        <li x-data="{ open: {{ request()->routeIs('fleet') || request()->routeIs('aircraft-types') ? 'true' : 'false' }} }">
            <button @click="open = !open"
                class="nav-link w-full justify-between {{ request()->routeIs('fleet') || request()->routeIs('aircraft-types') ? 'active' : '' }}">
                <div class="flex items-center gap-3">
                    <svg class="w-4 h-4 flex-shrink-0 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                    <span>Fleet Management</span>
                </div>
                <svg class="w-3.5 h-3.5 opacity-60 transition-transform duration-200 flex-shrink-0" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <ul x-show="open" x-transition class="mt-0.5 space-y-0.5 pl-9 pr-2 pb-1 bg-black/10 rounded-lg">
                <li><a href="{{ route('aircraft-types') }}" class="block px-2.5 py-1.5 rounded text-xs font-medium transition-colors {{ request()->routeIs('aircraft-types') ? 'bg-black/25 font-bold' : 'opacity-80 hover:opacity-100 hover:bg-black/10' }}">Aircraft Types</a></li>
                <li><a href="{{ route('fleet') }}" class="block px-2.5 py-1.5 rounded text-xs font-medium transition-colors {{ request()->routeIs('fleet') ? 'bg-black/25 font-bold' : 'opacity-80 hover:opacity-100 hover:bg-black/10' }}">Airframes</a></li>
            </ul>
        </li>
        @endif

        @if ($user->isSystemAdmin() || $canViewRoutes)
        <li>
            <a href="{{ route('routes') }}" class="nav-link {{ request()->routeIs('routes') ? 'active' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                <span>Route Management</span>
            </a>
        </li>
        @endif

        @if ($user->isSystemAdmin() || $canViewAirports)
        <li>
            <a href="{{ route('airports') }}" class="nav-link {{ request()->routeIs('airports') ? 'active' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>Airport Management</span>
            </a>
        </li>
        @endif

        @if ($user->isSystemAdmin() || $canViewPireps)
        <li>
            <a href="{{ route('pireps') }}" class="nav-link {{ request()->routeIs('pireps') || request()->routeIs('pireps.show') ? 'active' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span>PIREP Management</span>
            </a>
        </li>
        @endif

        @if ($user->isSystemAdmin() || $canViewSettings)
        <li>
            <a href="{{ route('settings') }}" class="nav-link {{ request()->routeIs('settings') ? 'active' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <span>VA Settings</span>
            </a>
        </li>
        @endif
        @endif
    </ul>

    <div class="px-5 py-3 border-t border-black/20 text-[10px] font-mono flex items-center justify-between opacity-80">
        <span class="tracking-wider text-[9px] uppercase font-semibold">V-Air Ops</span>
        <span class="px-1.5 py-0.5 rounded bg-black/40 font-bold text-white border border-white/10 shadow-sm">{{ \App\Services\VersionService::getVersion() }}</span>
    </div>
</nav>
