<div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8 space-y-8">
    <!-- Breadcrumb & Back Link -->
    <div class="flex items-center justify-between">
        <a href="{{ route('activities.index') }}" class="inline-flex items-center gap-2 text-xs font-bold transition opacity-70 hover:opacity-100 hover:text-tenant-accent">
            <span>&larr;</span> Back to Activities Directory
        </a>

        @if($registration && $registration->isCompleted())
            <span class="px-3 py-1 rounded-full text-xs font-black bg-emerald-500 text-black shadow flex items-center gap-1.5">
                <span>🏅</span> Activity Completed
            </span>
        @elseif($registration)
            <span class="px-3 py-1 rounded-full text-xs font-black bg-cyan-500 text-black shadow flex items-center gap-1.5">
                <span>⭐</span> Enrolled in Activity
            </span>
        @endif
    </div>

    <!-- Alerts -->
    @if (session()->has('detail_success'))
        <div class="p-4 rounded-2xl bg-emerald-500/15 border border-emerald-500/30 text-emerald-200 flex items-center justify-between shadow-lg">
            <span class="text-sm font-medium">{{ session('detail_success') }}</span>
            <span class="text-emerald-400 text-lg">✓</span>
        </div>
    @endif
    @if (session()->has('detail_error'))
        <div class="p-4 rounded-2xl bg-red-500/15 border border-red-500/30 text-red-200 flex items-center justify-between shadow-lg">
            <span class="text-sm font-medium">{{ session('detail_error') }}</span>
            <span class="text-red-400 text-lg">✕</span>
        </div>
    @endif

    <!-- Hero Card Banner -->
    <div class="va-card rounded-3xl border shadow-2xl overflow-hidden relative"
        style="background-color: var(--tenant-card-bg, #181D29); border-color: var(--tenant-input-border, rgba(255,255,255,0.1)); color: var(--tenant-card-text, #ffffff);">
        
        <div class="h-64 sm:h-80 relative bg-navy-950 overflow-hidden flex items-end p-6 sm:p-10">
            @if($activity->image_url)
                <img src="{{ $activity->image_url }}" alt="{{ $activity->name }}" class="absolute inset-0 w-full h-full object-cover opacity-50">
            @else
                <div class="absolute inset-0 bg-gradient-to-tr from-navy-950 via-navy-900 to-indigo-950 opacity-95"></div>
            @endif
            <div class="absolute inset-0 bg-gradient-to-t from-[#181D29] via-transparent to-transparent"></div>

            <div class="relative z-10 space-y-2 max-w-3xl">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider backdrop-blur-md shadow"
                        style="background-color: rgba(0,0,0,0.6); color: var(--tenant-accent, #21A19D);">
                        {{ $activity->type_label }}
                    </span>
                    <span class="px-3 py-1 rounded-full text-xs font-mono font-bold bg-black/60 backdrop-blur-md text-amber-300 shadow">
                        +{{ $activity->points_value }} {{ $activity->points_mode === 'percentage' ? '%' : 'pts' }}
                    </span>
                    @if($activity->tags)
                        @foreach($activity->tags as $tag)
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-mono bg-white/10 backdrop-blur-md text-slate-300">
                                #{{ $tag }}
                            </span>
                        @endforeach
                    @endif
                </div>

                <h1 class="text-2xl sm:text-4xl font-black tracking-tight text-white drop-shadow-md">
                    {{ $activity->name }}
                </h1>

                <div class="flex flex-wrap items-center gap-4 text-xs font-mono opacity-80 pt-1">
                    <div class="flex items-center gap-1.5">
                        <span>📅</span>
                        <span>{{ $activity->start_at->format('M d, Y H:i') }}z &mdash; {{ $activity->end_at->format('M d, Y H:i') }}z</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span>⏱️</span>
                        <span>{{ $activity->estimated_flight_time }}</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span>👥</span>
                        <span>{{ $activity->registrations_count }} Pilots Joined</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Ribbon -->
        <div class="p-6 border-t flex flex-wrap items-center justify-between gap-4 bg-black/10"
            style="border-color: var(--tenant-input-border, rgba(255,255,255,0.08));">
            <div>
                <p class="text-xs opacity-70">
                    Award rule: <strong class="text-slate-200 capitalize">{{ str_replace('_', ' ', $activity->time_award_scale) }}</strong>
                    @if($activity->time_leeway_minutes > 0)
                        &bull; ({{ $activity->time_leeway_minutes }}m grace leeway applied)
                    @endif
                </p>
            </div>

            <div class="flex items-center gap-3">
                @if($registration && $registration->isCompleted())
                    <div class="px-5 py-2.5 rounded-xl font-bold text-xs bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 flex items-center gap-2">
                        <span>🏆</span> Tour Completed (+{{ $registration->points_awarded }} pts)
                    </div>
                @elseif($registration)
                    <button wire:click="unregister"
                        wire:confirm="Are you sure you want to unregister? (Your logged flights will be preserved)."
                        class="px-4 py-2 rounded-xl text-xs font-bold text-red-400 hover:text-red-300 hover:bg-red-500/10 transition">
                        Leave Activity
                    </button>
                @elseif($activity->isRegistrationOpen())
                    <button wire:click="register"
                        class="px-6 py-2.5 rounded-xl text-xs font-black shadow-xl hover:opacity-90 transition flex items-center gap-2"
                        style="background-color: var(--tenant-button-bg, var(--tenant-accent)); color: var(--tenant-button-text, #ffffff);">
                        <span>⭐</span> Join & Track Activity
                    </button>
                @endif
            </div>
        </div>
    </div>

    <!-- Main Content Layout (2 Columns) -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Left: Legs / Waves / Challenge Board / Criteria -->
        <div class="lg:col-span-2 space-y-8">
            
            <!-- Description Card -->
            @if($activity->description)
                <div class="va-card rounded-3xl p-6 border shadow-xl space-y-3"
                    style="background-color: var(--tenant-card-bg, #181D29); border-color: var(--tenant-input-border, rgba(255,255,255,0.1));">
                    <h3 class="text-sm font-black uppercase tracking-wider opacity-80">Overview & Briefing</h3>
                    <div class="text-xs leading-relaxed opacity-90 whitespace-pre-line">
                        {{ $activity->description }}
                    </div>
                </div>
            @endif

            <!-- 1. MULTI-LEG PROGRESS STEPPER (Tours & Rosters) -->
            @if($activity->isMultiLeg())
                <div class="va-card rounded-3xl p-6 border shadow-xl space-y-6"
                    style="background-color: var(--tenant-card-bg, #181D29); border-color: var(--tenant-input-border, rgba(255,255,255,0.1));">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-base font-black uppercase tracking-wider flex items-center gap-2">
                                <span>🗺️</span> Journey Legs ({{ $activity->legs->count() }} Segments)
                            </h3>
                            <p class="text-xs opacity-70">
                                Flights must be flown sequentially in order. Other casual flights may be completed between legs.
                            </p>
                        </div>
                        @if($registration)
                            <span class="text-xs font-bold font-mono text-tenant-accent">
                                {{ $registration->completed_legs_count }} / {{ $activity->legs->count() }} Complete
                            </span>
                        @endif
                    </div>

                    @php
                        $nextLeg = $registration ? $registration->getNextIncompleteLeg() : ($activity->legs->first() ?? null);
                    @endphp

                    <div class="space-y-4">
                        @foreach($activity->legs as $leg)
                            @php
                                $isLegCompleted = isset($completedLegsMap[$leg->id]);
                                $isNext = $nextLeg && $nextLeg->id === $leg->id && !$isLegCompleted;
                                $isLocked = !$isLegCompleted && !$isNext;
                                $progress = $completedLegsMap[$leg->id] ?? null;
                            @endphp

                            <div class="p-5 rounded-2xl border transition duration-200 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 {{ $isNext ? 'border-tenant-accent bg-tenant-accent/5 shadow-lg' : ($isLegCompleted ? 'border-emerald-500/30 bg-emerald-500/5' : 'opacity-60 bg-black/20') }}"
                                style="border-color: {{ $isNext ? 'var(--tenant-accent)' : '' }};">
                                
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 rounded-2xl font-mono font-bold text-sm flex items-center justify-center shrink-0 {{ $isLegCompleted ? 'bg-emerald-500 text-black' : ($isNext ? 'bg-tenant-accent text-white shadow-md animate-pulse' : 'bg-white/10 text-white/60') }}">
                                        @if($isLegCompleted) ✓ @else {{ $leg->sequence }} @endif
                                    </div>

                                    <div class="space-y-1">
                                        <div class="flex items-center gap-2">
                                            <span class="font-mono text-base font-black tracking-wider text-white">
                                                {{ $leg->dep_icao }} &rarr; {{ $leg->arr_icao }}
                                            </span>
                                            @if($isNext)
                                                <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase bg-tenant-accent text-white">
                                                    Current Leg
                                                </span>
                                            @elseif($isLegCompleted)
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500/20 text-emerald-300">
                                                    Completed
                                                </span>
                                            @endif
                                        </div>

                                        <div class="flex flex-wrap items-center gap-3 text-xs opacity-75 font-mono">
                                            @if($leg->airframe)
                                                <span>Aircraft: <strong class="text-slate-200">{{ $leg->airframe->registration }}</strong></span>
                                            @elseif($leg->aircraftType)
                                                <span>Type: <strong class="text-slate-200">{{ $leg->aircraftType->icao }}</strong></span>
                                            @endif
                                            @if($leg->route && $leg->route->block_time)
                                                <span>Duration: {{ $leg->route->block_time }}</span>
                                            @endif
                                        </div>

                                        @if($leg->notes)
                                            <p class="text-[11px] opacity-70 italic">{{ $leg->notes }}</p>
                                        @endif
                                    </div>
                                </div>

                                <!-- Action Buttons -->
                                <div class="flex items-center gap-2 self-end sm:self-center">
                                    @if($isLegCompleted && $progress && $progress->pirep)
                                        <a href="{{ route('pireps.show', $progress->pirep_id) }}"
                                            class="px-3 py-1.5 rounded-xl text-xs font-bold border transition bg-black/20 hover:bg-black/40 text-emerald-300 border-emerald-500/20">
                                            View PIREP &rarr;
                                        </a>
                                    @elseif($isNext && $registration)
                                        <a href="{{ route('flight-centre.book') }}?dep={{ $leg->dep_icao }}&arr={{ $leg->arr_icao }}"
                                            class="px-5 py-2 rounded-xl text-xs font-black shadow-md transition hover:opacity-90 flex items-center gap-1.5"
                                            style="background-color: var(--tenant-button-bg, var(--tenant-accent)); color: var(--tenant-button-text, #ffffff);">
                                            <span>✈</span> Book Flight
                                        </a>
                                    @elseif($isLocked)
                                        <span class="text-xs opacity-50 flex items-center gap-1">
                                            <span>🔒</span> Locked
                                        </span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

            <!-- 2. SLOTTED FLYOUT (Waves & Slot Reservations) -->
            @elseif($activity->type === 'slotted_event')
                <div class="va-card rounded-3xl p-6 border shadow-xl space-y-6"
                    style="background-color: var(--tenant-card-bg, #181D29); border-color: var(--tenant-input-border, rgba(255,255,255,0.1));">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                        <div>
                            <h3 class="text-base font-black uppercase tracking-wider flex items-center gap-2">
                                <span>⏱️</span> Departure Slots & Waves
                            </h3>
                            <p class="text-xs opacity-70">
                                Reserve your exclusive departure slot time for this flyout event.
                            </p>
                        </div>

                        <!-- Network Selector -->
                        <div class="flex items-center gap-1 bg-black/30 p-1 rounded-xl border border-white/10 text-xs font-bold">
                            @foreach(['vatsim' => 'VATSIM', 'ivao' => 'IVAO', 'offline' => 'Offline'] as $netKey => $netLabel)
                                <button wire:click="$set('selectedNetwork', '{{ $netKey }}')"
                                    class="px-3 py-1 rounded-lg transition {{ $selectedNetwork === $netKey ? 'bg-tenant-accent text-white shadow' : 'opacity-60 hover:opacity-100' }}">
                                    {{ $netLabel }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <!-- Waves Grid -->
                    <div class="space-y-6">
                        @foreach($activity->waves as $wave)
                            @php
                                $isWaveUnlocked = $wave->isUnlocked($selectedNetwork);
                                $slots = $networkSlots->get($wave->id) ?? collect();
                            @endphp

                            <div class="p-5 rounded-2xl border bg-black/20 space-y-4 {{ !$isWaveUnlocked ? 'opacity-50' : '' }}"
                                style="border-color: var(--tenant-input-border, rgba(255,255,255,0.1));">
                                <div class="flex items-center justify-between border-b pb-3 border-white/5">
                                    <div>
                                        <h4 class="font-bold text-sm flex items-center gap-2">
                                            <span>Wave {{ $wave->position }}:</span> {{ $wave->name }}
                                        </h4>
                                        <p class="text-[11px] font-mono opacity-70">
                                            Window: {{ $wave->start_time }}z &mdash; {{ $wave->end_time }}z &bull; {{ $wave->interval_minutes }}m Intervals
                                        </p>
                                    </div>
                                    @if(!$isWaveUnlocked)
                                        <span class="text-xs font-bold text-amber-400">🔒 Locked (Previous wave must be filled)</span>
                                    @else
                                        <span class="text-xs font-bold text-emerald-400">🔓 Booking Open</span>
                                    @endif
                                </div>

                                <!-- Slots Button Grid -->
                                <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-6 gap-2.5">
                                    @foreach($slots as $slot)
                                        @php
                                            $isMySlot = $slot->user_id === auth()->id();
                                            $isBookedOther = $slot->isBooked() && !$isMySlot;
                                            $canDispatch = $slot->canDispatch();
                                        @endphp

                                        <div class="p-2.5 rounded-xl border text-center font-mono text-xs flex flex-col justify-between gap-1.5 {{ $isMySlot ? 'border-tenant-accent bg-tenant-accent/20 shadow-md ring-1 ring-tenant-accent' : ($isBookedOther ? 'border-red-500/20 bg-red-500/5 opacity-60' : 'border-white/10 bg-black/30 hover:border-tenant-accent/50') }}">
                                            <div class="font-bold text-sm text-white">
                                                {{ $slot->slot_time->format('H:i') }}z
                                            </div>

                                            @if($isMySlot)
                                                <div class="space-y-1">
                                                    <span class="text-[10px] font-black text-tenant-accent block">YOUR SLOT</span>
                                                    <span class="text-[9px] opacity-70 block">Callsign: {{ $slot->callsign_suffix }}</span>
                                                    <button wire:click="releaseSlot({{ $slot->id }})" class="text-[10px] text-red-400 hover:underline">
                                                        Release
                                                    </button>
                                                </div>
                                            @elseif($isBookedOther)
                                                <span class="text-[10px] text-red-400 font-bold">Booked</span>
                                            @elseif($isWaveUnlocked)
                                                <button wire:click="bookSlot({{ $slot->id }})"
                                                    class="px-2 py-1 rounded bg-white/10 hover:bg-tenant-accent hover:text-white transition text-[11px] font-bold">
                                                    Reserve
                                                </button>
                                            @else
                                                <span class="text-[10px] opacity-50">Locked</span>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

            <!-- 3. COMMUNITY GOAL PROGRESS -->
            @elseif($activity->type === 'community_goal')
                <div class="va-card rounded-3xl p-6 border shadow-xl space-y-6"
                    style="background-color: var(--tenant-card-bg, #181D29); border-color: var(--tenant-input-border, rgba(255,255,255,0.1));">
                    <div>
                        <h3 class="text-base font-black uppercase tracking-wider flex items-center gap-2">
                            <span>🏆</span> Community Target Tracker
                        </h3>
                        <p class="text-xs opacity-70">
                            Every flight flown by company pilots automatically advances the goal towards completion!
                        </p>
                    </div>

                    @php
                        $cur = $activity->community_current;
                        $tgt = max(1, $activity->community_target);
                        $pct = min(100, round(($cur / $tgt) * 100, 1));
                    @endphp

                    <!-- Giant Meter -->
                    <div class="p-6 rounded-2xl bg-black/30 border border-white/10 space-y-4">
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-end gap-2">
                            <div>
                                <span class="text-xs uppercase font-bold tracking-wider opacity-60">Company Contribution</span>
                                <div class="text-3xl sm:text-4xl font-black font-mono text-tenant-accent">
                                    {{ number_format($cur) }} <span class="text-sm font-normal opacity-70">/ {{ number_format($tgt) }} {{ $activity->community_metric }}</span>
                                </div>
                            </div>
                            <div class="text-2xl sm:text-3xl font-black font-mono text-emerald-400">
                                {{ $pct }}%
                            </div>
                        </div>

                        <div class="w-full h-4 rounded-full bg-black/60 overflow-hidden p-0.5 border border-white/5">
                            <div class="h-full rounded-full bg-gradient-to-r from-cyan-500 via-tenant-accent to-emerald-400 transition-all duration-500"
                                style="width: {{ $pct }}%;"></div>
                        </div>
                    </div>

                    <!-- Top Contributors Leaderboard -->
                    @if(count($topContributors))
                        <div class="space-y-3 pt-2">
                            <h4 class="font-bold text-sm">Top Pilot Contributors</h4>
                            <div class="divide-y border rounded-2xl overflow-hidden"
                                style="border-color: var(--tenant-input-border, rgba(255,255,255,0.08));">
                                @foreach($topContributors as $idx => $contrib)
                                    <div class="p-3.5 flex items-center justify-between text-xs bg-black/10 hover:bg-black/20 transition">
                                        <div class="flex items-center gap-3">
                                            <span class="w-6 h-6 rounded-full bg-white/10 font-mono font-bold text-[11px] flex items-center justify-center">
                                                {{ $idx + 1 }}
                                            </span>
                                            <span class="font-bold">{{ $contrib->user->name ?? 'Pilot' }}</span>
                                            @if($idx === 0)
                                                <span class="px-2 py-0.5 rounded text-[10px] font-black bg-amber-400 text-black">Top Contributor (3.0×)</span>
                                            @endif
                                        </div>
                                        <div class="font-mono font-bold text-tenant-accent">
                                            {{ number_format($contrib->total_metric) }} {{ $activity->community_metric }}
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

            <!-- 4. COMMUNITY CHALLENGE (2-TEAM DUEL) -->
            @elseif($activity->type === 'community_challenge')
                <div class="va-card rounded-3xl p-6 border shadow-xl space-y-6"
                    style="background-color: var(--tenant-card-bg, #181D29); border-color: var(--tenant-input-border, rgba(255,255,255,0.1));">
                    <div>
                        <h3 class="text-base font-black uppercase tracking-wider flex items-center gap-2">
                            <span>⚔️</span> Team Battle Board
                        </h3>
                        <p class="text-xs opacity-70">
                            Two teams compete head-to-head. The team with the highest target completion percentage takes the victory bonus!
                        </p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        @foreach($activity->teams as $idx => $team)
                            @php
                                $isTeam1 = $idx === 0;
                                $teamPct = $team->actual_percentage;
                            @endphp

                            <div class="p-6 rounded-2xl border relative overflow-hidden space-y-4 {{ $isTeam1 ? 'border-blue-500/40 bg-blue-500/10' : 'border-red-500/40 bg-red-500/10' }}">
                                <div class="flex items-center justify-between">
                                    <h4 class="font-black text-lg {{ $isTeam1 ? 'text-blue-300' : 'text-red-300' }}">
                                        {{ $team->name }}
                                    </h4>
                                    <span class="text-xl font-black font-mono">
                                        {{ $teamPct }}%
                                    </span>
                                </div>

                                <div class="font-mono text-xs opacity-80">
                                    Progress: <strong>{{ number_format($team->current_value) }}</strong> / {{ number_format($team->target_value) }} {{ $team->count_type }}
                                </div>

                                <div class="w-full h-3 rounded-full bg-black/40 overflow-hidden">
                                    <div class="h-full rounded-full {{ $isTeam1 ? 'bg-blue-400' : 'bg-red-400' }}"
                                        style="width: {{ min(100, $teamPct) }}%;"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

            <!-- 5. STANDARD EVENT / FOCUS AIRPORT -->
            @else
                <div class="va-card rounded-3xl p-6 border shadow-xl space-y-4"
                    style="background-color: var(--tenant-card-bg, #181D29); border-color: var(--tenant-input-border, rgba(255,255,255,0.1));">
                    <h3 class="text-base font-black uppercase tracking-wider flex items-center gap-2">
                        <span>🎯</span> Qualifying Flights
                    </h3>
                    <p class="text-xs opacity-80">
                        Fly any flight matching the criteria during the activity window to receive automatic bonus points and badges!
                    </p>

                    @if($activity->type === 'focus_airport')
                        <div class="p-4 rounded-xl bg-black/20 border border-white/10 font-mono text-xs">
                            Hub Airport: <strong class="text-tenant-accent text-sm">{{ $activity->event_criteria['focus_airport'] ?? 'N/A' }}</strong> (All departures & arrivals)
                        </div>
                    @else
                        <div class="p-4 rounded-xl bg-black/20 border border-white/10 space-y-2 font-mono text-xs">
                            @if(!empty($activity->event_criteria['departure_airports']))
                                <div>Departures: <strong class="text-slate-200">{{ implode(', ', $activity->event_criteria['departure_airports']) }}</strong></div>
                            @endif
                            @if(!empty($activity->event_criteria['arrival_airports']))
                                <div>Arrivals: <strong class="text-slate-200">{{ implode(', ', $activity->event_criteria['arrival_airports']) }}</strong></div>
                            @endif
                            <div>Operator: <strong class="text-tenant-accent">{{ $activity->event_criteria['operator'] ?? 'AND' }}</strong></div>
                        </div>
                    @endif
                </div>
            @endif
        </div>

        <!-- Right: Sidebar Rules & Info -->
        <div class="space-y-6">
            @if($activity->sidebar_title || $activity->sidebar_content)
                <div class="va-card rounded-3xl p-6 border shadow-xl space-y-3"
                    style="background-color: var(--tenant-card-bg, #181D29); border-color: var(--tenant-input-border, rgba(255,255,255,0.1));">
                    @if($activity->sidebar_title)
                        <h4 class="font-bold text-sm">{{ $activity->sidebar_title }}</h4>
                    @endif
                    <div class="text-xs opacity-80 leading-relaxed whitespace-pre-line">
                        {{ $activity->sidebar_content }}
                    </div>
                </div>
            @endif

            <!-- Rules & Restrictions Panel -->
            <div class="va-card rounded-3xl p-6 border shadow-xl space-y-4"
                style="background-color: var(--tenant-card-bg, #181D29); border-color: var(--tenant-input-border, rgba(255,255,255,0.1));">
                <h4 class="font-black text-xs uppercase tracking-wider opacity-80">Rules & Flight Restrictions</h4>
                
                <ul class="space-y-2.5 text-xs opacity-85">
                    <li class="flex items-center justify-between">
                        <span class="opacity-70">Registration:</span>
                        <strong class="text-slate-200">{{ $activity->registration_required ? 'Mandatory' : 'Optional' }}</strong>
                    </li>
                    <li class="flex items-center justify-between">
                        <span class="opacity-70">Sequential Order:</span>
                        <strong class="text-slate-200">{{ $activity->isMultiLeg() ? 'Enforced' : 'No Order' }}</strong>
                    </li>
                    <li class="flex items-center justify-between">
                        <span class="opacity-70">Award Mode:</span>
                        <strong class="text-slate-200 capitalize">{{ str_replace('_', ' ', $activity->point_award_mode) }}</strong>
                    </li>
                    <li class="flex items-center justify-between">
                        <span class="opacity-70">Repeat Completions:</span>
                        <strong class="text-slate-200">{{ $activity->allow_repeat ? 'Allowed' : 'One-Time' }}</strong>
                    </li>

                    @if(!empty($activity->restrictions['networks']))
                        <li class="flex items-center justify-between">
                            <span class="opacity-70">Allowed Networks:</span>
                            <strong class="text-tenant-accent uppercase">{{ implode(', ', $activity->restrictions['networks']) }}</strong>
                        </li>
                    @endif

                    @if(isset($activity->restrictions['max_landing_rate']))
                        <li class="flex items-center justify-between">
                            <span class="opacity-70">Max Landing Rate:</span>
                            <strong class="text-amber-300 font-mono">{{ $activity->restrictions['max_landing_rate'] }} fpm</strong>
                        </li>
                    @endif
                </ul>
            </div>
        </div>
    </div>
</div>
