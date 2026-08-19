<div class="space-y-6 max-w-[1600px] mx-auto w-full">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <!-- PIREPs Card -->
        <div class="bg-[#2c323f] border border-[#3f475a] rounded-md overflow-hidden shadow-sm">
            <div class="px-4 py-2 bg-[#2c323f] border-b border-[#3f475a] text-xs text-gray-400 font-semibold tracking-wider flex justify-between">
                <span>PIREPs</span>
            </div>
            <div class="px-6 py-5 flex justify-between items-center bg-[#212631]">
                <div class="text-2xl font-bold text-tenant-accent">{{ $pirepsCount }}</div>
                <div class="text-xl font-bold text-green-500" title="Accepted">{{ $acceptedPireps }}</div>
                <div class="text-xl font-bold text-red-400" title="Rejected">{{ $rejectedPireps }}</div>
            </div>
        </div>

        <!-- Points Card -->
        <div class="bg-[#2c323f] border border-[#3f475a] rounded-md overflow-hidden shadow-sm">
            <div class="px-4 py-2 bg-[#2c323f] border-b border-[#3f475a] text-xs text-gray-400 font-semibold tracking-wider flex justify-between">
                <span>Points</span>
            </div>
            <div class="px-6 py-5 flex justify-between items-center bg-[#212631]">
                <div class="text-2xl font-bold text-tenant-accent">{{ number_format($profile->points) }}</div>
            </div>
        </div>

        <!-- Time & Distance Card -->
        <div class="bg-[#2c323f] border border-[#3f475a] rounded-md overflow-hidden shadow-sm">
            <div class="px-4 py-2 bg-[#2c323f] border-b border-[#3f475a] text-xs text-gray-400 font-semibold tracking-wider flex justify-between">
                <span>Time Flown</span>
            </div>
            <div class="px-6 py-5 flex justify-between items-center bg-[#212631]">
                <div class="text-2xl font-bold text-tenant-accent">
                    {{ floor($profile->flight_time / 60) }}h {{ $profile->flight_time % 60 }}m
                </div>
            </div>
        </div>
    </div>

    <!-- Rank Tracker -->
    <div class="bg-[#2c323f] border border-[#3f475a] rounded-md overflow-hidden shadow-sm mt-6">
        <div class="px-4 py-2 bg-[#2c323f] border-b border-[#3f475a] text-xs text-gray-400 font-semibold tracking-wider flex justify-between">
            <span>Rank Tracker</span>
        </div>
        <div class="p-6 bg-[#212631]">
            <div class="flex items-center space-x-6 mb-8">
                <div class="w-16 h-16 bg-[#2c323f] rounded-full flex items-center justify-center text-gray-300 font-bold text-xl border border-tenant-accent">
                    {{ substr($user->active_rank_name, 0, 2) }}
                </div>
                <div>
                    <div class="text-gray-400 text-sm">Current Rank</div>
                    <div class="text-2xl font-bold text-tenant-accent">{{ $user->active_rank_name }}</div>
                </div>
            </div>

            @if($nextRank)
                <div class="mb-4">
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-gray-400">Hours to Next Rank ({{ $nextRank->name }})</span>
                        <span class="text-gray-300">{{ max(0, $nextRank->min_hours - floor($profile->flight_time / 60)) }} to go</span>
                    </div>
                    <div class="w-full bg-[#2c323f] rounded-full h-2.5">
                        @php
                            $hourPercent = $nextRank->min_hours > 0 ? min(100, (floor($profile->flight_time / 60) / $nextRank->min_hours) * 100) : 100;
                        @endphp
                        <div class="bg-tenant-accent h-2.5 rounded-full" style="width: {{ $hourPercent }}%"></div>
                    </div>
                </div>

                <div class="mb-4">
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-gray-400">Points to Next Rank</span>
                        <span class="text-gray-300">{{ max(0, $nextRank->min_points - $profile->points) }} to go</span>
                    </div>
                    <div class="w-full bg-[#2c323f] rounded-full h-2.5">
                        @php
                            $pointPercent = $nextRank->min_points > 0 ? min(100, ($profile->points / $nextRank->min_points) * 100) : 100;
                        @endphp
                        <div class="bg-tenant-accent h-2.5 rounded-full opacity-80" style="width: {{ $pointPercent }}%"></div>
                    </div>
                </div>
            @else
                <div class="text-green-400 font-semibold mt-4">You have reached the highest rank!</div>
            @endif
        </div>
    </div>
</div>
