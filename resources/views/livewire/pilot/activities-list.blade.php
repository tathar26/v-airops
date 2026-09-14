<div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8 space-y-8">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs" style="color: var(--tenant-card-muted, #94a3b8);">
                <span>Operations</span>
                <span>/</span>
                <span class="font-semibold" style="color: var(--tenant-card-text, #ffffff);">Flight Activities</span>
            </div>
            <h1 class="text-3xl font-black uppercase tracking-wider flex items-center gap-3 mt-1"
                style="color: var(--tenant-card-text, #ffffff);">
                <span>🎯</span> Activities & Community Events
            </h1>
            <p class="text-sm mt-1" style="color: var(--tenant-card-muted, #94a3b8);">
                Tackle scheduled tours, join fly-in events, claim departure slots, and contribute to company-wide challenges.
            </p>
        </div>

        @if(auth()->user()->isSystemAdmin() || auth()->user()->hasAirlinePermission('manage_activities') || auth()->user()->hasAirlinePermission('view_activities'))
            <a href="{{ route('admin.activities') }}"
                class="px-4 py-2 rounded-xl text-xs font-bold border transition flex items-center gap-2 shadow-sm"
                style="background-color: var(--tenant-button-secondary-bg, #1f2937); color: var(--tenant-button-secondary-text, #f3f4f6); border-color: var(--tenant-input-border, rgba(255,255,255,0.15));">
                <span>⚙️</span> Staff Management
            </a>
        @endif
    </div>

    <!-- Alerts -->
    @if (session()->has('activity_success'))
        <div class="p-4 rounded-2xl bg-emerald-500/15 border border-emerald-500/30 text-emerald-200 flex items-center justify-between shadow-lg">
            <span class="text-sm font-medium">{{ session('activity_success') }}</span>
            <span class="text-emerald-400 text-lg">✓</span>
        </div>
    @endif
    @if (session()->has('activity_error'))
        <div class="p-4 rounded-2xl bg-red-500/15 border border-red-500/30 text-red-200 flex items-center justify-between shadow-lg">
            <span class="text-sm font-medium">{{ session('activity_error') }}</span>
            <span class="text-red-400 text-lg">✕</span>
        </div>
    @endif

    <!-- Navigation Tabs & Search -->
    <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4">
        <!-- Tabs -->
        <div class="flex items-center gap-1.5 p-1 rounded-2xl bg-black/20 border"
            style="border-color: var(--tenant-input-border, rgba(255,255,255,0.08));">
            @php
                $pilotTabs = [
                    'active'    => '🔥 Ongoing',
                    'my'        => '⭐ My Activities',
                    'events'    => 'Events & Slots',
                    'tours'     => 'Tours & Rosters',
                    'community' => 'Community Goals',
                ];
            @endphp
            @foreach($pilotTabs as $tabKey => $tabLabel)
                <button wire:click="setTab('{{ $tabKey }}')"
                    class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 {{ $tab === $tabKey ? 'shadow-md' : 'opacity-70 hover:opacity-100' }}"
                    style="{{ $tab === $tabKey ? 'background-color: var(--tenant-button-bg, var(--tenant-accent)); color: var(--tenant-button-text, #ffffff);' : '' }}">
                    {{ $tabLabel }}
                </button>
            @endforeach
        </div>

        <!-- Search -->
        <div class="relative min-w-[240px]">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search activities..."
                class="w-full pl-9 pr-4 py-2 rounded-xl text-xs bg-black/20 border transition focus:outline-none"
                style="border-color: var(--tenant-input-border, rgba(255,255,255,0.15)); color: var(--tenant-card-text, #ffffff);">
            <svg class="w-4 h-4 absolute left-3 top-2.5 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        </div>
    </div>

    <!-- Activities Cards Grid -->
    @if($activities->isEmpty())
        <div class="va-card rounded-3xl p-16 text-center border shadow-2xl space-y-4"
            style="background-color: var(--tenant-card-bg, #181D29); border-color: var(--tenant-input-border, rgba(255,255,255,0.1));">
            <div class="text-5xl">🧭</div>
            <h3 class="text-lg font-black uppercase tracking-wider">No Activities Available</h3>
            <p class="text-xs max-w-md mx-auto opacity-70">
                There are currently no active flight events or tours matching this filter. Check back soon for new community updates!
            </p>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($activities as $activity)
                @php
                    $reg = $userRegistrations->get($activity->id);
                    $isRegistered = !is_null($reg);
                    $isCompleted = $isRegistered && $reg->isCompleted();
                    $completedLegs = $isRegistered ? $reg->completed_legs_count : 0;
                    $totalLegs = $activity->legs_count;
                @endphp

                <div class="va-card rounded-3xl border shadow-xl flex flex-col overflow-hidden group hover:border-tenant-accent/50 transition duration-300"
                    style="background-color: var(--tenant-card-bg, #181D29); border-color: var(--tenant-input-border, rgba(255,255,255,0.1)); color: var(--tenant-card-text, #ffffff);">
                    
                    <!-- Banner or Graphic Header -->
                    <div class="h-40 relative bg-navy-950 overflow-hidden flex items-center justify-center">
                        @if($activity->image_url)
                            <img src="{{ $activity->image_url }}" alt="{{ $activity->name }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-500 opacity-80">
                        @else
                            <!-- Abstract Aviation Visual Background -->
                            <div class="absolute inset-0 bg-gradient-to-br from-cyan-950/40 via-navy-900 to-indigo-950/40 opacity-90"></div>
                            <div class="relative z-10 flex flex-col items-center justify-center text-center p-4">
                                <span class="text-3xl mb-1">
                                    @if($activity->type === 'tour') 🗺️
                                    @elseif($activity->type === 'slotted_event') ⏱️
                                    @elseif($activity->type === 'community_goal') 🏆
                                    @elseif($activity->type === 'community_challenge') ⚔️
                                    @else ✈️
                                    @endif
                                </span>
                                <span class="text-xs font-mono font-bold tracking-widest uppercase opacity-60">
                                    {{ $activity->type_label }}
                                </span>
                            </div>
                        @endif

                        <!-- Floating Badges Over Banner -->
                        <div class="absolute top-3 left-3 flex items-center gap-1.5 z-10">
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider backdrop-blur-md shadow"
                                style="background-color: rgba(0,0,0,0.6); color: var(--tenant-accent, #21A19D);">
                                {{ $activity->type_label }}
                            </span>
                            @if($isCompleted)
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-emerald-500 text-black shadow">
                                    ✓ Completed
                                </span>
                            @elseif($isRegistered)
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-cyan-500 text-black shadow">
                                    ⭐ Enrolled
                                </span>
                            @endif
                        </div>

                        <div class="absolute top-3 right-3 z-10">
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold font-mono bg-black/60 backdrop-blur-md text-amber-300 shadow">
                                +{{ $activity->points_value }} {{ $activity->points_mode === 'percentage' ? '%' : 'pts' }}
                            </span>
                        </div>

                        <!-- Time Remaining Ribbon -->
                        <div class="absolute bottom-2 left-3 right-3 flex items-center justify-between text-[10px] font-mono bg-black/60 backdrop-blur-md px-3 py-1 rounded-lg">
                            <span class="opacity-70">Window:</span>
                            <span class="font-bold text-slate-200">
                                {{ $activity->start_at->format('M d') }} – {{ $activity->end_at->format('M d, H:i') }}z
                            </span>
                        </div>
                    </div>

                    <!-- Card Body -->
                    <div class="p-5 flex-1 flex flex-col justify-between space-y-4">
                        <div>
                            <h3 class="font-bold text-base group-hover:text-tenant-accent transition line-clamp-1">
                                <a href="{{ route('activities.show', $activity->id) }}">
                                    {{ $activity->name }}
                                </a>
                            </h3>

                            @if($activity->description)
                                <p class="text-xs mt-1.5 opacity-70 line-clamp-2 leading-relaxed">
                                    {{ $activity->description }}
                                </p>
                            @endif

                            <!-- Meta Pills -->
                            <div class="flex flex-wrap items-center gap-2 mt-3 pt-3 border-t text-[11px]"
                                style="border-color: var(--tenant-input-border, rgba(255,255,255,0.06));">
                                <div class="flex items-center gap-1 opacity-80" title="Estimated flight duration">
                                    <span>⏱️</span>
                                    <span>{{ $activity->estimated_flight_time }}</span>
                                </div>

                                @if($activity->isMultiLeg())
                                    <div class="flex items-center gap-1 opacity-80" title="Total journey legs">
                                        <span>📍</span>
                                        <span>{{ $totalLegs }} Legs</span>
                                    </div>
                                @endif

                                <div class="flex items-center gap-1 ml-auto opacity-70 text-[10px] font-mono">
                                    <span>👥 {{ $activity->registrations_count }} Joined</span>
                                </div>
                            </div>

                            <!-- Progress Trackers -->
                            @if($activity->isMultiLeg() && $isRegistered)
                                <div class="mt-3 space-y-1.5">
                                    <div class="flex justify-between text-[10px] font-mono">
                                        <span class="opacity-70">Progress</span>
                                        <span class="font-bold text-tenant-accent">{{ $completedLegs }} / {{ $totalLegs }} Legs</span>
                                    </div>
                                    <div class="w-full h-1.5 rounded-full bg-black/40 overflow-hidden">
                                        <div class="h-full bg-tenant-accent rounded-full transition-all duration-300"
                                            style="width: {{ $reg->completion_percentage }}%;"></div>
                                    </div>
                                </div>
                            @elseif($activity->type === 'community_goal' && $activity->community_target > 0)
                                <div class="mt-3 space-y-1.5">
                                    <div class="flex justify-between text-[10px] font-mono">
                                        <span class="opacity-70">Goal Progress</span>
                                        <span class="font-bold text-tenant-accent">
                                            {{ number_format($activity->community_current) }} / {{ number_format($activity->community_target) }}
                                        </span>
                                    </div>
                                    @php
                                        $goalPct = min(100, round(($activity->community_current / $activity->community_target) * 100));
                                    @endphp
                                    <div class="w-full h-1.5 rounded-full bg-black/40 overflow-hidden">
                                        <div class="h-full bg-gradient-to-r from-cyan-500 to-emerald-400 rounded-full"
                                            style="width: {{ $goalPct }}%;"></div>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <!-- Footer Actions -->
                        <div class="pt-3 border-t flex items-center justify-between gap-3"
                            style="border-color: var(--tenant-input-border, rgba(255,255,255,0.08));">
                            
                            <a href="{{ route('activities.show', $activity->id) }}"
                                class="px-4 py-2 rounded-xl text-xs font-bold border transition hover:opacity-80 flex items-center gap-1.5"
                                style="background-color: var(--tenant-button-secondary-bg, #1f2937); color: var(--tenant-button-secondary-text, #f3f4f6); border-color: var(--tenant-input-border, rgba(255,255,255,0.15));">
                                View Details &rarr;
                            </a>

                            @if($isCompleted)
                                <span class="text-xs font-bold text-emerald-400 flex items-center gap-1">
                                    <span>🏅</span> Completed
                                </span>
                            @elseif($isRegistered)
                                <button wire:click="unregister({{ $activity->id }})"
                                    wire:confirm="Unregister from this activity? (Your flight progress will be retained if you rejoin)."
                                    class="text-[11px] text-red-400 hover:underline">
                                    Leave
                                </button>
                            @elseif($activity->isRegistrationOpen())
                                <button wire:click="register({{ $activity->id }})"
                                    class="px-4 py-2 rounded-xl text-xs font-bold shadow-md hover:opacity-90 transition flex items-center gap-1"
                                    style="background-color: var(--tenant-button-bg, var(--tenant-accent)); color: var(--tenant-button-text, #ffffff);">
                                    <span>⭐</span> Join Activity
                                </button>
                            @else
                                <span class="text-[11px] opacity-60">Registration Closed</span>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="pt-4">
            {{ $activities->links() }}
        </div>
    @endif
</div>
