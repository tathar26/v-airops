<div class="space-y-4 max-w-[1400px] mx-auto w-full">
    <!-- Stat Cards Row -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <!-- PIREPs Card -->
        <div class="va-card">
            <div class="va-card-header flex justify-between items-center">
                <span>PIREPs</span>
                <svg class="w-3.5 h-3.5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </div>
            <div class="va-card-body flex justify-between items-center">
                <div>
                    <div class="text-2xl font-bold text-tenant-accent">{{ $pirepsCount }}</div>
                    <div class="text-[10px] text-slate-500 uppercase tracking-wider mt-0.5">Total</div>
                </div>
                <div class="flex items-center gap-4">
                    <div class="text-center">
                        <div class="text-lg font-bold text-emerald-400">{{ $acceptedPireps }}</div>
                        <div class="text-[10px] text-slate-500 uppercase tracking-wider">Accept</div>
                    </div>
                    <div class="text-center">
                        <div class="text-lg font-bold text-red-400">{{ $rejectedPireps }}</div>
                        <div class="text-[10px] text-slate-500 uppercase tracking-wider">Reject</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Points Card -->
        <div class="va-card">
            <div class="va-card-header flex justify-between items-center">
                <span>Points</span>
                <svg class="w-3.5 h-3.5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
            </div>
            <div class="va-card-body">
                <div class="text-2xl font-bold text-tenant-accent">{{ number_format($profile->points) }}</div>
                <div class="text-[10px] text-slate-500 uppercase tracking-wider mt-0.5">Earned points</div>
            </div>
        </div>

        <!-- Time Flown Card -->
        <div class="va-card">
            <div class="va-card-header flex justify-between items-center">
                <span>Time Flown</span>
                <svg class="w-3.5 h-3.5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div class="va-card-body">
                <div class="text-2xl font-bold text-tenant-accent font-mono">
                    {{ floor($profile->flight_time / 60) }}<span class="text-base text-slate-400 font-sans">h</span>
                    {{ str_pad($profile->flight_time % 60, 2, '0', STR_PAD_LEFT) }}<span class="text-base text-slate-400 font-sans">m</span>
                </div>
                <div class="text-[10px] text-slate-500 uppercase tracking-wider mt-0.5">Block hours</div>
            </div>
        </div>
    </div>

    <!-- Rank Tracker -->
    <div class="va-card">
        <div class="va-card-header flex justify-between items-center">
            <span>Rank Tracker</span>
            @if($nextRank)
                <span class="text-[10px] text-slate-500 font-mono">Next: {{ $nextRank->name }}</span>
            @else
                <span class="text-[10px] text-emerald-400 font-semibold">Max Rank ✓</span>
            @endif
        </div>
        <div class="va-card-body">
            <div class="flex items-center gap-4 mb-6">
                <div class="w-14 h-14 rounded-full flex items-center justify-center font-bold text-lg border-2 border-tenant-accent text-tenant-accent"
                     style="background-color: rgba(var(--tenant-accent-rgb, 249,115,22), 0.1);">
                    {{ substr($user->active_rank_name, 0, 2) }}
                </div>
                <div>
                    <div class="text-[10px] text-slate-500 uppercase tracking-wider">Current Rank</div>
                    <div class="text-xl font-bold text-tenant-accent leading-tight">{{ $user->active_rank_name }}</div>
                </div>
            </div>

            @if($nextRank)
                <div class="space-y-4">
                    <!-- Hours Progress -->
                    <div>
                        <div class="flex justify-between text-xs mb-1.5">
                            <span class="text-slate-500">Hours to <span class="text-slate-300">{{ $nextRank->name }}</span></span>
                            <span class="text-slate-400 font-mono">
                                {{ floor($profile->flight_time / 60) }}h / {{ $nextRank->min_hours }}h
                                <span class="text-slate-600 ml-1">({{ max(0, $nextRank->min_hours - floor($profile->flight_time / 60)) }} to go)</span>
                            </span>
                        </div>
                        <div class="w-full rounded-full h-2" style="background-color: var(--card-header-bg);">
                            @php $hourPercent = $nextRank->min_hours > 0 ? min(100, (floor($profile->flight_time / 60) / $nextRank->min_hours) * 100) : 100; @endphp
                            <div class="h-2 rounded-full transition-all duration-500 bg-tenant-accent" style="width: {{ $hourPercent }}%; opacity: 0.85;"></div>
                        </div>
                    </div>

                    <!-- Points Progress -->
                    <div>
                        <div class="flex justify-between text-xs mb-1.5">
                            <span class="text-slate-500">Points to <span class="text-slate-300">{{ $nextRank->name }}</span></span>
                            <span class="text-slate-400 font-mono">
                                {{ number_format($profile->points) }} / {{ number_format($nextRank->min_points) }}
                                <span class="text-slate-600 ml-1">({{ number_format(max(0, $nextRank->min_points - $profile->points)) }} to go)</span>
                            </span>
                        </div>
                        <div class="w-full rounded-full h-2" style="background-color: var(--card-header-bg);">
                            @php $pointPercent = $nextRank->min_points > 0 ? min(100, ($profile->points / $nextRank->min_points) * 100) : 100; @endphp
                            <div class="h-2 rounded-full transition-all duration-500 bg-tenant-accent" style="width: {{ $pointPercent }}%; opacity: 0.6;"></div>
                        </div>
                    </div>
                </div>
            @else
                <div class="text-emerald-400 font-semibold text-sm">🏆 You have reached the highest rank!</div>
            @endif
        </div>
    </div>
</div>
