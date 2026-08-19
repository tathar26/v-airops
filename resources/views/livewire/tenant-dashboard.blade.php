<div class="space-y-6 max-w-[1600px] mx-auto w-full">
    
    <!-- Top Stats Row 1 -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <!-- PIREPs -->
        <div class="bg-[#2c323f] border border-[#3f475a] rounded-md overflow-hidden shadow-sm">
            <div class="px-4 py-2 bg-[#2c323f] border-b border-[#3f475a] text-xs text-gray-400 font-semibold tracking-wider flex justify-between">
                <span>PIREPs</span>
                <span class="text-[10px] text-gray-400">Total / Accepted / Rejected</span>
            </div>
            <div class="px-6 py-5 flex justify-between items-center bg-[#212631]">
                <div class="text-2xl font-bold text-green-500" title="Total Filed">{{ $userTotalPireps }}</div>
                <div class="text-xl font-bold text-emerald-400" title="Accepted">{{ $userAcceptedPireps }}</div>
                <div class="text-xl font-bold text-orange-400" title="Pending">{{ $userPendingPireps }}</div>
                <div class="text-xl font-bold text-red-400" title="Rejected">{{ $userRejectedPireps }}</div>
            </div>
        </div>

        <!-- Points -->
        <div class="bg-[#2c323f] border border-[#3f475a] rounded-md overflow-hidden shadow-sm">
            <div class="px-4 py-2 bg-[#2c323f] border-b border-[#3f475a] text-xs text-gray-400 font-semibold tracking-wider flex justify-between">
                <span>Points</span>
                <span class="text-[10px] text-gray-400">Career Score</span>
            </div>
            <div class="px-6 py-5 flex justify-between items-center bg-[#212631]">
                <div class="text-2xl font-bold text-blue-500">{{ number_format($profile->points) }}</div>
                <div class="text-xs font-semibold text-gray-400">PTS</div>
            </div>
        </div>

        <!-- Time & Distance -->
        <div class="bg-[#2c323f] border border-[#3f475a] rounded-md overflow-hidden shadow-sm">
            <div class="px-4 py-2 bg-[#2c323f] border-b border-[#3f475a] text-xs text-gray-400 font-semibold tracking-wider flex justify-between">
                <span>Time Flown</span>
                <span class="text-[10px] text-gray-400">Active Airline</span>
            </div>
            <div class="px-6 py-5 flex justify-between items-center bg-[#212631]">
                <div class="text-2xl font-bold text-blue-500">{{ $flightTimeHours }}h {{ $flightTimeMins }}m</div>
            </div>
        </div>
    </div>

    <!-- Top Stats Row 2 -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <!-- Fleet & Routes Info -->
        <div class="bg-[#2c323f] border border-[#3f475a] rounded-md overflow-hidden shadow-sm">
            <div class="px-4 py-2 bg-[#2c323f] border-b border-[#3f475a] text-xs text-gray-400 font-semibold tracking-wider flex justify-between">
                <span>Airline Network</span>
                <span class="text-[10px] text-gray-400">Fleet &amp; Routes</span>
            </div>
            <div class="px-6 py-5 flex justify-between items-center bg-[#212631]">
                <div>
                    <div class="text-xs text-gray-400">Active Fleet</div>
                    <div class="text-xl font-bold text-blue-400">{{ $fleetCount }} Airframes</div>
                </div>
                <div class="text-right">
                    <div class="text-xs text-gray-400">Total Routes</div>
                    <div class="text-xl font-bold text-blue-400">{{ $routeCount }} Routes</div>
                </div>
            </div>
        </div>

        <!-- Rank & Airline Profile -->
        <div class="bg-[#2c323f] border border-[#3f475a] rounded-md overflow-hidden shadow-sm">
            <div class="px-4 py-2 bg-[#2c323f] border-b border-[#3f475a] text-xs text-gray-400 font-semibold tracking-wider flex justify-between">
                <span>Current Rank</span>
                <span class="text-[10px] text-gray-400">Pilot Classification</span>
            </div>
            <div class="px-6 py-5 flex justify-between items-center bg-[#212631]">
                <div class="text-2xl font-bold text-tenant-accent">{{ $rankName }}</div>
                <div class="text-sm font-mono text-gray-400">{{ $callsign }}</div>
            </div>
        </div>

        <!-- Pilot Card (Full Name, Callsign, Rank & Location) -->
        <div class="bg-[#2c323f] border border-[#3f475a] rounded-md overflow-hidden shadow-sm">
            <div class="px-4 py-2 bg-[#2c323f] border-b border-[#3f475a] text-xs text-gray-400 font-semibold tracking-wider flex justify-between">
                <span>Pilot</span>
                <span class="text-[10px]">Your Name &amp; Current Location</span>
            </div>
            <div class="px-6 py-4 flex justify-between items-center bg-[#212631]">
                <div>
                    <div class="text-xl font-bold text-gray-100 tracking-tight">{{ $user->full_name }}</div>
                    <div class="text-xs text-tenant-accent font-semibold flex items-center gap-1.5 mt-0.5">
                        <span class="font-mono bg-tenant-accent/10 px-1.5 py-0.5 rounded border border-tenant-accent/20">{{ $callsign }}</span>
                        <span class="text-gray-500">&bull;</span>
                        <span class="text-gray-300">{{ $rankName }}</span>
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-[11px] text-gray-400 uppercase tracking-wider font-semibold">Location</div>
                    <div class="text-2xl font-bold text-blue-400 font-mono tracking-wider">{{ $currentLocation }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Info Banners -->
    <div class="bg-[#fcd34d] text-black p-4 rounded-md shadow-sm text-sm font-medium">
        <h4 class="font-bold mb-1">Welcome to {{ auth()->user()->tenant->name ?? 'our VA' }}!</h4>
        <p class="mb-2">It's great to see you, {{ $user->first_name ?? $user->full_name }}!</p>
        <p>Please ensure you book and fly the same aircraft type. This tracking software is PC compatible only. Enjoy your flights with us!</p>
    </div>

</div>
