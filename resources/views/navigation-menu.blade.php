<nav class="flex-grow flex flex-col pt-6 pb-4 overflow-y-auto">
    <!-- Brand / Title -->
    <div class="px-6 mb-8 hidden md:block">
        @if (auth()->check() && auth()->user()->tenant && auth()->user()->tenant->logo_path)
            <img src="{{ Storage::url(auth()->user()->tenant->logo_path) }}" alt="VA Logo" class="h-10 w-auto object-contain">
        @else
            <div class="text-tenant-accent font-bold text-lg uppercase tracking-widest">
                {{ auth()->check() && auth()->user()->tenant ? auth()->user()->tenant->name : 'V-Ops Admin' }}
            </div>
        @endif
    </div>

    <!-- Navigation Links -->
    <ul class="flex-grow space-y-1 px-3">
        <!-- Dashboard Link -->
        <li>
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('dashboard') || request()->routeIs('admin.dashboard') ? 'bg-tenant-accent/10 text-tenant-accent border-r-4 border-tenant-accent' : 'text-gray-400 hover:bg-white/5 hover:text-white' }}">
                <svg class="w-5 h-5 {{ request()->routeIs('dashboard') || request()->routeIs('admin.dashboard') ? 'text-tenant-accent' : 'text-gray-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                Dashboard
            </a>
        </li>

        <!-- My Profile Dropdown -->
        @php
            $isProfileActive = request()->routeIs('profile.dashboard') || request()->routeIs('profile.map') || request()->routeIs('profile.statistics') || request()->routeIs('profile.awards') || request()->routeIs('profile.pireps') || request()->routeIs('profile.preferences') || request()->routeIs('profile.account');
        @endphp
        <li x-data="{ open: {{ $isProfileActive ? 'true' : 'false' }} }">
            <button @click="open = !open" class="w-full flex items-center justify-between px-4 py-3 rounded-lg text-sm font-medium transition-colors {{ $isProfileActive ? 'bg-tenant-accent/10 text-tenant-accent border-r-4 border-tenant-accent' : 'text-gray-400 hover:bg-white/5 hover:text-white' }}">
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5 {{ $isProfileActive ? 'text-tenant-accent' : 'text-gray-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    My Profile
                </div>
                <svg class="w-4 h-4 transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
            </button>
            <ul x-show="open" x-transition class="mt-1 space-y-1 pl-11 pr-3 py-2">
                <li>
                    <a href="{{ route('profile.dashboard') }}" class="block px-2 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('profile.dashboard') ? 'text-tenant-accent bg-white/5' : 'text-gray-400 hover:text-white hover:bg-white/5' }}">
                        Dashboard
                    </a>
                </li>
                <li>
                    <a href="{{ route('profile.map') }}" class="block px-2 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('profile.map') ? 'text-tenant-accent bg-white/5' : 'text-gray-400 hover:text-white hover:bg-white/5' }}">
                        Map
                    </a>
                </li>
                <li>
                    <a href="{{ route('profile.statistics') }}" class="block px-2 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('profile.statistics') ? 'text-tenant-accent bg-white/5' : 'text-gray-400 hover:text-white hover:bg-white/5' }}">
                        Statistics
                    </a>
                </li>
                <li>
                    <a href="{{ route('profile.awards') }}" class="block px-2 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('profile.awards') ? 'text-tenant-accent bg-white/5' : 'text-gray-400 hover:text-white hover:bg-white/5' }}">
                        Awards
                    </a>
                </li>
                <li>
                    <a href="{{ route('profile.pireps') }}" class="block px-2 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('profile.pireps') ? 'text-tenant-accent bg-white/5' : 'text-gray-400 hover:text-white hover:bg-white/5' }}">
                        PIREPs
                    </a>
                </li>
                <li>
                    <a href="{{ route('profile.preferences') }}" class="block px-2 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('profile.preferences') ? 'text-tenant-accent bg-white/5' : 'text-gray-400 hover:text-white hover:bg-white/5' }}">
                        Preferences
                    </a>
                </li>
                <li>
                    <a href="{{ route('profile.account') }}" class="block px-2 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('profile.account') ? 'text-tenant-accent bg-white/5' : 'text-gray-400 hover:text-white hover:bg-white/5' }}">
                        Account Settings
                    </a>
                </li>
            </ul>
        </li>

        <!-- Flight Centre -->
        @php
            $isFlightCentreActive = request()->routeIs('flight-centre.*');
        @endphp
        <li x-data="{ open: {{ $isFlightCentreActive ? 'true' : 'false' }} }">
            <button @click="open = !open" class="w-full flex items-center justify-between px-4 py-3 rounded-lg text-sm font-medium transition-colors {{ $isFlightCentreActive ? 'bg-tenant-accent/10 text-tenant-accent border-r-4 border-tenant-accent' : 'text-gray-400 hover:bg-white/5 hover:text-white' }}">
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5 {{ $isFlightCentreActive ? 'text-tenant-accent' : 'text-gray-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Flight Centre
                </div>
                <svg class="w-4 h-4 transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
            </button>
            <ul x-show="open" x-transition class="mt-1 space-y-1 pl-11 pr-3 py-2">
                <li>
                    <a href="{{ route('flight-centre.index') }}" class="block px-2 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('flight-centre.index') ? 'text-tenant-accent bg-white/5' : 'text-gray-400 hover:text-white hover:bg-white/5' }}">
                        Dashboard
                    </a>
                </li>
                <li>
                    <a href="{{ route('flight-centre.book') }}" class="block px-2 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('flight-centre.book') ? 'text-tenant-accent bg-white/5' : 'text-gray-400 hover:text-white hover:bg-white/5' }}">
                        Book a Flight
                    </a>
                </li>
                <li>
                    <a href="{{ route('flight-centre.flights') }}" class="block px-2 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('flight-centre.flights') ? 'text-tenant-accent bg-white/5' : 'text-gray-400 hover:text-white hover:bg-white/5' }}">
                        Flights List
                    </a>
                </li>
                <li>
                    <a href="{{ route('flight-centre.destinations') }}" class="block px-2 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('flight-centre.destinations') ? 'text-tenant-accent bg-white/5' : 'text-gray-400 hover:text-white hover:bg-white/5' }}">
                        Destination Map
                    </a>
                </li>
            </ul>
        </li>

        <!-- NOTAMs -->
        <li>
            <a href="#" class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium text-gray-400 hover:bg-white/5 hover:text-white transition-colors">
                <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                NOTAMs
            </a>
        </li>

        @if (auth()->check() && auth()->user()->hasRole('VA Owner'))
        <!-- Fleet Dropdown -->
        <li x-data="{ open: {{ request()->routeIs('fleet') || request()->routeIs('aircraft-types') ? 'true' : 'false' }} }">
            <button @click="open = !open" class="w-full flex items-center justify-between px-4 py-3 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('fleet') || request()->routeIs('aircraft-types') ? 'bg-tenant-accent/10 text-tenant-accent border-r-4 border-tenant-accent' : 'text-gray-400 hover:bg-white/5 hover:text-white' }}">
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5 {{ request()->routeIs('fleet') || request()->routeIs('aircraft-types') ? 'text-tenant-accent' : 'text-gray-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                    Fleet
                </div>
                <svg class="w-4 h-4 transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
            </button>
            <ul x-show="open" x-transition class="mt-1 space-y-1 pl-11 pr-3 py-2">
                <li>
                    <a href="{{ route('aircraft-types') }}" class="block px-2 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('aircraft-types') ? 'text-tenant-accent bg-white/5' : 'text-gray-400 hover:text-white hover:bg-white/5' }}">
                        Aircraft Types
                    </a>
                </li>
                <li>
                    <a href="{{ route('fleet') }}" class="block px-2 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('fleet') ? 'text-tenant-accent bg-white/5' : 'text-gray-400 hover:text-white hover:bg-white/5' }}">
                        Airframes
                    </a>
                </li>
            </ul>
        </li>

        <!-- Route Manager -->
        <li>
            <a href="{{ route('routes') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('routes') ? 'bg-tenant-accent/10 text-tenant-accent border-r-4 border-tenant-accent' : 'text-gray-400 hover:bg-white/5 hover:text-white' }}">
                <svg class="w-5 h-5 {{ request()->routeIs('routes') ? 'text-tenant-accent' : 'text-gray-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path></svg>
                Route Manager
            </a>
        </li>

        <!-- Airport Manager -->
        <li>
            <a href="{{ route('airports') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('airports') ? 'bg-tenant-accent/10 text-tenant-accent border-r-4 border-tenant-accent' : 'text-gray-400 hover:bg-white/5 hover:text-white' }}">
                <svg class="w-5 h-5 {{ request()->routeIs('airports') ? 'text-tenant-accent' : 'text-gray-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                Airport Manager
            </a>
        </li>

        <!-- PIREPs (Admin) -->
        <li>
            <a href="{{ route('pireps') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('pireps') || request()->routeIs('pireps.show') ? 'bg-tenant-accent/10 text-tenant-accent border-r-4 border-tenant-accent' : 'text-gray-400 hover:bg-white/5 hover:text-white' }}">
                <svg class="w-5 h-5 {{ request()->routeIs('pireps') || request()->routeIs('pireps.show') ? 'text-tenant-accent' : 'text-gray-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                PIREP Management
            </a>
        </li>

        <!-- Settings -->
        <li>
            <a href="{{ route('settings') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('settings') ? 'bg-tenant-accent/10 text-tenant-accent border-r-4 border-tenant-accent' : 'text-gray-400 hover:bg-white/5 hover:text-white' }}">
                <svg class="w-5 h-5 {{ request()->routeIs('settings') ? 'text-tenant-accent' : 'text-gray-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                VA Settings
            </a>
        </li>
        @endif
    </ul>
    
    <div class="px-6 py-4 border-t border-white/5 text-xs text-gray-600">
        v-ops v1.0
    </div>
</nav>
