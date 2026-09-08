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
            <span class="flex items-center gap-2">
                <span>🎖️</span>
                <span>Rank &amp; Milestone Progression</span>
            </span>
            @if($nextRank)
                <span class="text-xs text-slate-400 font-mono flex items-center gap-1.5">
                    <span>Next:</span>
                    <strong class="text-tenant-accent">{{ $nextRank->name }}</strong>
                    <span class="text-[10px] text-slate-500">({{ $nextRank->abbreviation }})</span>
                </span>
            @else
                <span class="text-xs text-emerald-400 font-semibold flex items-center gap-1">
                    <span>🏆</span> Max Regular Rank Achieved
                </span>
            @endif
        </div>
        <div class="va-card-body">
            <!-- Current Display Rank with Epaulette -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-4 rounded-xl bg-slate-900/60 border border-white/5 mb-6">
                <div class="flex items-center gap-4">
                    <div class="p-1 rounded-lg bg-slate-950 border border-white/10 shadow-inner flex items-center justify-center">
                        <img src="{{ $user->getDisplayRankImageUrl() }}" alt="{{ $user->getDisplayRank() }}" class="w-[85px] h-[36px] object-contain rounded" />
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-[10px] text-slate-400 uppercase tracking-wider font-semibold">Active Display Rank</span>
                            @if($user->isDisplayingHonoraryRank())
                                <span class="text-[10px] uppercase font-bold tracking-wider px-1.5 py-0.5 rounded bg-amber-500/20 text-amber-300 border border-amber-500/30">
                                    Honorary
                                </span>
                            @endif
                        </div>
                        <div class="text-xl font-bold text-tenant-accent leading-tight flex items-center gap-2 mt-0.5">
                            <span>{{ $user->getDisplayRank() }}</span>
                        </div>
                        @if($profile->rank && $profile->honoraryRank)
                            <div class="text-xs text-slate-400 mt-0.5 flex items-center gap-2">
                                <span>Regular: <strong class="text-slate-200">{{ $profile->rank->name }}</strong></span>
                                <span class="text-slate-600">&bull;</span>
                                <span>Honorary: <strong class="text-amber-300">{{ $profile->honoraryRank->name }}</strong></span>
                            </div>
                        @endif
                    </div>
                </div>

                @if($profile->honoraryRank)
                    <div class="text-xs text-slate-400 sm:text-right border-t sm:border-t-0 pt-2 sm:pt-0 border-white/10">
                        <span class="text-slate-500 block text-[10px] uppercase">Display Preference</span>
                        <a href="{{ route('pilot.preferences') }}" class="text-tenant-accent hover:underline font-medium inline-flex items-center gap-1 mt-0.5">
                            <span>Prefer: {{ $profile->prefer_honorary_rank ? 'Honorary Rank' : 'Regular Rank' }}</span>
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </a>
                    </div>
                @endif
            </div>

            @if($nextRank)
                <div class="space-y-4">
                    <div class="flex items-center justify-between pb-1 border-b border-white/5">
                        <span class="text-xs font-bold text-slate-300 uppercase tracking-wider">Milestones to Unlock {{ $nextRank->name }}</span>
                        <span class="text-[10px] text-slate-500 italic">All criteria must be satisfied</span>
                    </div>

                    <!-- 1. Flight Hours Progress -->
                    @php
                        $userHours = floor($profile->flight_time / 60);
                        $targetHours = $nextRank->min_hours;
                        $hourPercent = $targetHours > 0 ? min(100, ($userHours / $targetHours) * 100) : 100;
                        $hoursMet = $userHours >= $targetHours;
                    @endphp
                    <div>
                        <div class="flex justify-between text-xs mb-1.5">
                            <span class="text-slate-400 flex items-center gap-1.5">
                                <span class="{{ $hoursMet ? 'text-emerald-400' : 'text-slate-500' }}">{{ $hoursMet ? '✓' : '○' }}</span>
                                <span>Flight Hours</span>
                            </span>
                            <span class="text-slate-400 font-mono">
                                {{ $userHours }}h / {{ $targetHours }}h
                                <span class="{{ $hoursMet ? 'text-emerald-400 font-semibold' : 'text-slate-500' }} ml-1">
                                    {{ $hoursMet ? '(Achieved)' : '(' . ($targetHours - $userHours) . 'h to go)' }}
                                </span>
                            </span>
                        </div>
                        <div class="w-full rounded-full h-2 bg-slate-800 overflow-hidden">
                            <div class="h-2 rounded-full transition-all duration-500 {{ $hoursMet ? 'bg-emerald-500' : 'bg-tenant-accent' }}" style="width: {{ $hourPercent }}%;"></div>
                        </div>
                    </div>

                    <!-- 2. Points Progress -->
                    @php
                        $userPoints = $profile->points;
                        $targetPoints = $nextRank->min_points;
                        $pointPercent = $targetPoints > 0 ? min(100, ($userPoints / $targetPoints) * 100) : 100;
                        $pointsMet = $userPoints >= $targetPoints;
                    @endphp
                    <div>
                        <div class="flex justify-between text-xs mb-1.5">
                            <span class="text-slate-400 flex items-center gap-1.5">
                                <span class="{{ $pointsMet ? 'text-emerald-400' : 'text-slate-500' }}">{{ $pointsMet ? '✓' : '○' }}</span>
                                <span>Total Flight Points</span>
                            </span>
                            <span class="text-slate-400 font-mono">
                                {{ number_format($userPoints) }} / {{ number_format($targetPoints) }}
                                <span class="{{ $pointsMet ? 'text-emerald-400 font-semibold' : 'text-slate-500' }} ml-1">
                                    {{ $pointsMet ? '(Achieved)' : '(' . number_format($targetPoints - $userPoints) . ' to go)' }}
                                </span>
                            </span>
                        </div>
                        <div class="w-full rounded-full h-2 bg-slate-800 overflow-hidden">
                            <div class="h-2 rounded-full transition-all duration-500 {{ $pointsMet ? 'bg-emerald-500' : 'bg-tenant-accent' }}" style="width: {{ $pointPercent }}%; opacity: 0.85;"></div>
                        </div>
                    </div>

                    <!-- 3. Cumulative Bonus Points Progress (if required) -->
                    @if($nextRank->min_bonus_points > 0)
                        @php
                            $userBonus = $profile->bonus_points ?? 0;
                            $targetBonus = $nextRank->min_bonus_points;
                            $bonusPercent = $targetBonus > 0 ? min(100, ($userBonus / $targetBonus) * 100) : 100;
                            $bonusMet = $userBonus >= $targetBonus;
                        @endphp
                        <div>
                            <div class="flex justify-between text-xs mb-1.5">
                                <span class="text-slate-400 flex items-center gap-1.5">
                                    <span class="{{ $bonusMet ? 'text-emerald-400' : 'text-slate-500' }}">{{ $bonusMet ? '✓' : '○' }}</span>
                                    <span>Cumulative Bonus Points</span>
                                </span>
                                <span class="text-slate-400 font-mono">
                                    {{ number_format($userBonus) }} / {{ number_format($targetBonus) }}
                                    <span class="{{ $bonusMet ? 'text-emerald-400 font-semibold' : 'text-slate-500' }} ml-1">
                                        {{ $bonusMet ? '(Achieved)' : '(' . number_format($targetBonus - $userBonus) . ' to go)' }}
                                    </span>
                                </span>
                            </div>
                            <div class="w-full rounded-full h-2 bg-slate-800 overflow-hidden">
                                <div class="h-2 rounded-full transition-all duration-500 {{ $bonusMet ? 'bg-emerald-500' : 'bg-amber-500' }}" style="width: {{ $bonusPercent }}%;"></div>
                            </div>
                        </div>
                    @endif

                    <!-- 4. Filed PIREPs Progress (if required) -->
                    @if($nextRank->min_pireps > 0)
                        @php
                            $userPireps = $acceptedPireps;
                            $targetPireps = $nextRank->min_pireps;
                            $pirepPercent = $targetPireps > 0 ? min(100, ($userPireps / $targetPireps) * 100) : 100;
                            $pirepsMet = $userPireps >= $targetPireps;
                        @endphp
                        <div>
                            <div class="flex justify-between text-xs mb-1.5">
                                <span class="text-slate-400 flex items-center gap-1.5">
                                    <span class="{{ $pirepsMet ? 'text-emerald-400' : 'text-slate-500' }}">{{ $pirepsMet ? '✓' : '○' }}</span>
                                    <span>Accepted PIREPs</span>
                                </span>
                                <span class="text-slate-400 font-mono">
                                    {{ $userPireps }} / {{ $targetPireps }}
                                    <span class="{{ $pirepsMet ? 'text-emerald-400 font-semibold' : 'text-slate-500' }} ml-1">
                                        {{ $pirepsMet ? '(Achieved)' : '(' . ($targetPireps - $userPireps) . ' to go)' }}
                                    </span>
                                </span>
                            </div>
                            <div class="w-full rounded-full h-2 bg-slate-800 overflow-hidden">
                                <div class="h-2 rounded-full transition-all duration-500 {{ $pirepsMet ? 'bg-emerald-500' : 'bg-sky-500' }}" style="width: {{ $pirepPercent }}%;"></div>
                            </div>
                        </div>
                    @endif
                </div>
            @else
                <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-300 text-sm flex items-center gap-3">
                    <span class="text-xl">🏆</span>
                    <div>
                        <div class="font-bold">You have reached the highest regular rank!</div>
                        <div class="text-xs text-emerald-400/80">Congratulations on mastering all career progression milestones.</div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
