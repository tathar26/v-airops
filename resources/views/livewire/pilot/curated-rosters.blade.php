<div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8 space-y-6">
    <!-- Breadcrumb -->
    <div>
        <div class="flex items-center gap-2 text-xs" style="color: var(--tenant-card-muted, #94a3b8);">
            <a href="{{ route('flight-centre.index') }}" class="hover:text-tenant-accent transition">Flight Centre</a>
            <span>/</span>
            <span class="font-semibold" style="color: var(--tenant-card-text, #ffffff);">Curated Rosters</span>
        </div>
        <h1 class="text-2xl font-black uppercase tracking-wider flex items-center gap-2 mt-1"
            style="color: var(--tenant-card-text, #ffffff);">
            <span>📋</span> Curated Airline Rotations
        </h1>
        <p class="text-xs mt-1" style="color: var(--tenant-card-muted, #94a3b8);">
            Step into the cockpit for an authentic "day-in-the-life" rotation. Fly real airline pairings with assigned aircraft.
        </p>
    </div>

    @if (session()->has('roster_message'))
        <div class="p-4 rounded-2xl bg-emerald-500/15 border border-emerald-500/30 text-emerald-200 flex items-center justify-between shadow-lg">
            <span class="text-sm font-medium">{{ session('roster_message') }}</span>
            <span class="text-emerald-400 text-lg">✓</span>
        </div>
    @endif

    @if($rosters->isEmpty())
        <div class="va-card rounded-3xl p-16 text-center border shadow-2xl space-y-4"
            style="background-color: var(--tenant-card-bg, #181D29); border-color: var(--tenant-input-border, rgba(255,255,255,0.1));">
            <div class="text-5xl">🛫</div>
            <h3 class="text-lg font-black uppercase tracking-wider">No Curated Rosters Available</h3>
            <p class="text-xs max-w-md mx-auto opacity-70">
                Your airline operations team hasn't published any pilot rotations yet. Check the main Flight Centre or Activities hub for available flights!
            </p>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            @foreach($rosters as $roster)
                @php
                    $reg = $userRegistrations->get($roster->id);
                    $isEnrolled = !is_null($reg);
                    $isCompleted = $isEnrolled && $reg->isCompleted();
                    $completedCount = $isEnrolled ? $reg->completed_legs_count : 0;
                    $totalCount = $roster->legs_count;
                    $nextLeg = $isEnrolled ? $reg->getNextIncompleteLeg() : null;

                    // Unique aircraft types in this roster
                    $types = $roster->legs->map(fn($l) => $l->aircraftType ? $l->aircraftType->icao : ($l->airframe ? $l->airframe->aircraftType->icao ?? null : null))->filter()->unique();
                @endphp

                <div class="va-card rounded-3xl border shadow-xl p-6 flex flex-col justify-between space-y-5"
                    style="background-color: var(--tenant-card-bg, #181D29); border-color: var(--tenant-input-border, rgba(255,255,255,0.1)); color: var(--tenant-card-text, #ffffff);">
                    
                    <div class="space-y-4">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider font-mono"
                                    style="background-color: rgba(255,255,255,0.08); color: var(--tenant-accent, #21A19D);">
                                    {{ $totalCount }} Legs Rotation
                                </span>
                                <h3 class="text-base font-bold mt-1">
                                    {{ $roster->name }}
                                </h3>
                            </div>

                            @if($isCompleted)
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                                    ✓ Completed
                                </span>
                            @elseif($isEnrolled)
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-cyan-500/20 text-cyan-300 border border-cyan-500/30">
                                    Active: Leg {{ $completedCount + 1 }}
                                </span>
                            @endif
                        </div>

                        <!-- Route Sequence Pills -->
                        <div class="p-3.5 rounded-2xl bg-black/30 border border-white/5 space-y-2">
                            <span class="text-[10px] uppercase font-bold tracking-wider opacity-60 block">Route Sequence:</span>
                            <div class="flex flex-wrap items-center gap-2 font-mono font-bold text-xs">
                                @foreach($roster->legs as $idx => $leg)
                                    <span class="px-2 py-1 rounded-lg bg-black/40 border border-white/10 text-white">
                                        {{ $leg->dep_icao }} &rarr; {{ $leg->arr_icao }}
                                    </span>
                                    @if(!$loop->last)
                                        <span class="text-tenant-accent">&bull;</span>
                                    @endif
                                @endforeach
                            </div>
                        </div>

                        <!-- Aircraft & Duration Info -->
                        <div class="flex flex-wrap items-center gap-4 text-xs font-mono opacity-80 pt-1">
                            @if($types->isNotEmpty())
                                <div>Fleet: <strong class="text-slate-200">{{ $types->implode(', ') }}</strong></div>
                            @endif
                            <div>Estimated Time: <strong class="text-slate-200">{{ $roster->estimated_flight_time }}</strong></div>
                        </div>

                        <!-- Progress Bar if Enrolled -->
                        @if($isEnrolled)
                            <div class="space-y-1.5 pt-2">
                                <div class="flex justify-between text-[10px] font-mono">
                                    <span class="opacity-70">Progress</span>
                                    <span class="font-bold text-tenant-accent">{{ $completedCount }} / {{ $totalCount }} Legs</span>
                                </div>
                                <div class="w-full h-1.5 rounded-full bg-black/40 overflow-hidden">
                                    <div class="h-full bg-tenant-accent rounded-full transition-all duration-300"
                                        style="width: {{ $reg->completion_percentage }}%;"></div>
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Footer Actions -->
                    <div class="pt-4 border-t flex items-center justify-between gap-3"
                        style="border-color: var(--tenant-input-border, rgba(255,255,255,0.08));">
                        
                        <a href="{{ route('activities.show', $roster->id) }}"
                            class="px-4 py-2 rounded-xl text-xs font-bold border transition hover:opacity-80 flex items-center gap-1.5"
                            style="background-color: var(--tenant-button-secondary-bg, #1f2937); color: var(--tenant-button-secondary-text, #f3f4f6); border-color: var(--tenant-input-border, rgba(255,255,255,0.15));">
                            View Full Rotation &rarr;
                        </a>

                        @if($isCompleted)
                            <span class="text-xs font-bold text-emerald-400">Rotation Complete</span>
                        @elseif($isEnrolled && $nextLeg)
                            <a href="{{ route('flight-centre.book') }}?dep={{ $nextLeg->dep_icao }}&arr={{ $nextLeg->arr_icao }}"
                                class="px-5 py-2 rounded-xl text-xs font-black shadow-md hover:opacity-90 transition flex items-center gap-1.5"
                                style="background-color: var(--tenant-button-bg, var(--tenant-accent)); color: var(--tenant-button-text, #ffffff);">
                                <span>✈</span> Fly Leg {{ $completedCount + 1 }}
                            </a>
                        @elseif($isEnrolled)
                            <button wire:click="unregister({{ $roster->id }})" class="text-[11px] text-red-400 hover:underline">
                                Drop Rotation
                            </button>
                        @else
                            <button wire:click="register({{ $roster->id }})"
                                class="px-5 py-2 rounded-xl text-xs font-bold shadow-md hover:opacity-90 transition flex items-center gap-1"
                                style="background-color: var(--tenant-button-bg, var(--tenant-accent)); color: var(--tenant-button-text, #ffffff);">
                                <span>+</span> Add to My Roster
                            </button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <div class="pt-4">
            {{ $rosters->links() }}
        </div>
    @endif
</div>
