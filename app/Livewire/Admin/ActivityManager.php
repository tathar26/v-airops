<?php

namespace App\Livewire\Admin;

use App\Models\Activity;
use App\Models\ActivityLeg;
use App\Models\ActivityWave;
use App\Models\AircraftType;
use App\Models\Airframe;
use App\Models\Airport;
use App\Models\Route;
use App\Services\ActivityService;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class ActivityManager extends Component
{
    use WithPagination;

    public string $search = '';
    public string $selectedTypeTab = 'all';

    // Modal State
    public bool $showActivityModal = false;
    public ?int $editingActivityId = null;
    public string $activeModalTab = 'general'; // general, criteria, legs, waves, teams, restrictions

    // Core Activity Fields
    public string $name = '';
    public string $type = 'event';
    public string $subtype = 'airport_based';
    public string $description = '';
    public string $sidebarTitle = '';
    public string $sidebarContent = '';
    public string $imageUrl = '';
    public string $tagsInput = '';
    public string $startAt = '';
    public string $endAt = '';
    public ?string $showFrom = null;
    public int $timeLeewayMinutes = 0;
    public string $timeAwardScale = 'any_part';
    public bool $registrationRequired = false;
    public ?string $registrationStartAt = null;
    public ?string $registrationEndAt = null;
    public string $pointsMode = 'fixed';
    public int $pointsValue = 150;
    public string $pointAwardMode = 'per_leg';
    public bool $allowRepeat = false;
    public bool $isActive = true;

    // Event & Focus Criteria
    public string $eventDepartureAirports = '';
    public string $eventArrivalAirports = '';
    public string $eventOperator = 'AND';
    public string $focusAirport = '';

    // Slotted Event
    public string $slottedDepartureIcao = '';
    public string $slottedCallsignSystem = 'username_a';
    public string $slottedGeneratorPattern = 'FLIGHT-###';
    public int $slottedDispatchWindowBefore = 180;
    public int $slottedDispatchWindowAfter = 30;
    public array $slottedNetworks = ['vatsim', 'ivao', 'offline'];

    // Community Goal
    public string $communityMetric = 'flights';
    public int $communityTarget = 1000;
    public int $communityCompletionPoints = 500;
    public bool $communityTieredMultipliers = true;

    // Community Challenge (2 Teams)
    public string $team1Name = 'Blue Squadron';
    public string $team1CountType = 'flights';
    public int $team1Target = 500;
    public string $team2Name = 'Red Squadron';
    public string $team2CountType = 'flights';
    public int $team2Target = 500;

    // Restrictions
    public array $restrictedNetworks = [];
    public array $restrictedFleets = [];
    public string $restrictedCallsignPrefixes = '';
    public ?int $maxLandingRate = null;

    // Multi-Legs Builder
    public array $legs = [];

    // Waves Builder
    public array $waves = [];

    protected $rules = [
        'name'        => 'required|string|max:255',
        'type'        => 'required|in:event,slotted_event,focus_airport,tour,roster,curated_roster,community_goal,community_challenge',
        'startAt'     => 'required|date',
        'endAt'       => 'required|date|after:startAt',
        'pointsValue' => 'required|integer|min:0',
    ];

    public function mount()
    {
        $user = auth()->user();
        $tenantId = $user->getActiveTenantId() ?? $user->tenant_id;

        if (!$user->isSystemAdmin() && !$user->hasAirlinePermission('view_activities', $tenantId) && !$user->hasAirlinePermission('manage_activities', $tenantId)) {
            abort(403, 'Unauthorized. You do not have permission to manage activities for this airline.');
        }

        $now = Carbon::now('UTC');
        $this->startAt = $now->format('Y-m-d\TH:i');
        $this->endAt = $now->copy()->addDays(7)->format('Y-m-d\TH:i');
    }

    public function setTypeTab(string $tab)
    {
        $this->selectedTypeTab = $tab;
        $this->resetPage();
    }

    public function openCreateModal()
    {
        $user = auth()->user();
        $tenantId = $user->getActiveTenantId() ?? $user->tenant_id;
        if (!$user->isSystemAdmin() && !$user->hasAirlinePermission('manage_activities', $tenantId)) {
            abort(403, 'Forbidden. You need [manage_activities] permission.');
        }

        $this->resetActivityForm();
        $this->showActivityModal = true;
    }

    public function editActivity(int $id)
    {
        $user = auth()->user();
        $tenantId = $user->getActiveTenantId() ?? $user->tenant_id;
        if (!$user->isSystemAdmin() && !$user->hasAirlinePermission('manage_activities', $tenantId)) {
            abort(403, 'Forbidden. You need [manage_activities] permission.');
        }

        $activity = Activity::where('tenant_id', $tenantId)->findOrFail($id);
        $this->editingActivityId = $activity->id;
        $this->name = $activity->name;
        $this->type = $activity->type;
        $this->subtype = $activity->subtype ?? 'airport_based';
        $this->description = $activity->description ?? '';
        $this->sidebarTitle = $activity->sidebar_title ?? '';
        $this->sidebarContent = $activity->sidebar_content ?? '';
        $this->imageUrl = $activity->image_url ?? '';
        $this->tagsInput = is_array($activity->tags) ? implode(', ', $activity->tags) : '';
        $this->startAt = $activity->start_at ? $activity->start_at->format('Y-m-d\TH:i') : '';
        $this->endAt = $activity->end_at ? $activity->end_at->format('Y-m-d\TH:i') : '';
        $this->showFrom = $activity->show_from ? $activity->show_from->format('Y-m-d\TH:i') : null;
        $this->timeLeewayMinutes = (int)($activity->time_leeway_minutes ?? 0);
        $this->timeAwardScale = $activity->time_award_scale ?? 'any_part';
        $this->registrationRequired = (bool)$activity->registration_required;
        $this->registrationStartAt = $activity->registration_start_at ? $activity->registration_start_at->format('Y-m-d\TH:i') : null;
        $this->registrationEndAt = $activity->registration_end_at ? $activity->registration_end_at->format('Y-m-d\TH:i') : null;
        $this->pointsMode = $activity->points_mode ?? 'fixed';
        $this->pointsValue = (int)$activity->points_value;
        $this->pointAwardMode = $activity->point_award_mode ?? 'per_leg';
        $this->allowRepeat = (bool)$activity->allow_repeat;
        $this->isActive = (bool)$activity->is_active;

        // Criteria
        $crit = $activity->event_criteria ?? [];
        $this->eventDepartureAirports = implode(', ', $crit['departure_airports'] ?? []);
        $this->eventArrivalAirports = implode(', ', $crit['arrival_airports'] ?? []);
        $this->eventOperator = $crit['operator'] ?? 'AND';
        $this->focusAirport = $crit['focus_airport'] ?? '';

        // Slotted
        $this->slottedDepartureIcao = $activity->slotted_departure_icao ?? '';
        $this->slottedCallsignSystem = $activity->slotted_callsign_system ?? 'username_a';
        $this->slottedGeneratorPattern = $activity->slotted_generator_pattern ?? 'FLIGHT-###';
        $this->slottedDispatchWindowBefore = (int)($activity->slotted_dispatch_window_before ?? 180);
        $this->slottedDispatchWindowAfter = (int)($activity->slotted_dispatch_window_after ?? 30);
        $this->slottedNetworks = $activity->slotted_networks ?? ['vatsim', 'ivao', 'offline'];

        // Community
        $this->communityMetric = $activity->community_metric ?? 'flights';
        $this->communityTarget = (int)($activity->community_target ?? 1000);
        $this->communityCompletionPoints = (int)($activity->community_completion_points ?? 500);
        $this->communityTieredMultipliers = (bool)$activity->community_tiered_multipliers;

        // Teams
        $teams = $activity->teams;
        if ($teams->count() >= 2) {
            $this->team1Name = $teams[0]->name;
            $this->team1CountType = $teams[0]->count_type;
            $this->team1Target = $teams[0]->target_value;
            $this->team2Name = $teams[1]->name;
            $this->team2CountType = $teams[1]->count_type;
            $this->team2Target = $teams[1]->target_value;
        }

        // Restrictions
        $restr = $activity->restrictions ?? [];
        $this->restrictedNetworks = $restr['networks'] ?? [];
        $this->restrictedFleets = $restr['fleets'] ?? [];
        $this->restrictedCallsignPrefixes = implode(', ', $restr['callsign_prefixes'] ?? []);
        $this->maxLandingRate = $restr['max_landing_rate'] ?? null;

        // Legs
        $this->legs = [];
        foreach ($activity->legs as $leg) {
            $this->legs[] = [
                'id'               => $leg->id,
                'sequence'         => $leg->sequence,
                'dep_icao'         => $leg->dep_icao,
                'arr_icao'         => $leg->arr_icao,
                'route_id'         => $leg->route_id,
                'airframe_id'      => $leg->airframe_id,
                'aircraft_type_id' => $leg->aircraft_type_id,
                'show_from'        => $leg->show_from ? $leg->show_from->format('Y-m-d\TH:i') : '',
                'notes'            => $leg->notes ?? '',
            ];
        }

        // Waves
        $this->waves = [];
        foreach ($activity->waves as $wave) {
            $this->waves[] = [
                'id'               => $wave->id,
                'name'             => $wave->name,
                'position'         => $wave->position,
                'start_time'       => $wave->start_time,
                'end_time'         => $wave->end_time,
                'interval_minutes' => $wave->interval_minutes,
                'unlock_rule'      => $wave->unlock_rule,
            ];
        }

        $this->activeModalTab = 'general';
        $this->showActivityModal = true;
    }

    public function saveActivity()
    {
        $this->validate();

        $user = auth()->user();
        $tenantId = $user->getActiveTenantId() ?? $user->tenant_id;

        if (!$user->isSystemAdmin() && !$user->hasAirlinePermission('manage_activities', $tenantId)) {
            abort(403, 'Forbidden. You need [manage_activities] permission.');
        }

        // Parse tags
        $tags = array_filter(array_map('trim', explode(',', $this->tagsInput)));

        // Parse criteria
        $criteria = [];
        if ($this->type === 'event' || $this->type === 'slotted_event') {
            $criteria['departure_airports'] = array_filter(array_map('strtoupper', array_map('trim', explode(',', $this->eventDepartureAirports))));
            $criteria['arrival_airports'] = array_filter(array_map('strtoupper', array_map('trim', explode(',', $this->eventArrivalAirports))));
            $criteria['operator'] = $this->eventOperator;
        } elseif ($this->type === 'focus_airport') {
            $criteria['focus_airport'] = strtoupper(trim($this->focusAirport));
        }

        // Parse restrictions
        $restrictions = [];
        if (!empty($this->restrictedNetworks)) {
            $restrictions['networks'] = $this->restrictedNetworks;
        }
        if (!empty($this->restrictedFleets)) {
            $restrictions['fleets'] = array_map('intval', $this->restrictedFleets);
        }
        $prefixes = array_filter(array_map('strtoupper', array_map('trim', explode(',', $this->restrictedCallsignPrefixes))));
        if (!empty($prefixes)) {
            $restrictions['callsign_prefixes'] = $prefixes;
        }
        if ($this->maxLandingRate !== null && $this->maxLandingRate !== '') {
            $restrictions['max_landing_rate'] = (int)$this->maxLandingRate;
        }

        $data = [
            'tenant_id'                      => $tenantId,
            'name'                           => $this->name,
            'slug'                           => Str::slug($this->name),
            'type'                           => $this->type,
            'subtype'                        => $this->subtype,
            'description'                    => $this->description,
            'sidebar_title'                  => $this->sidebarTitle,
            'sidebar_content'                => $this->sidebarContent,
            'image_url'                      => $this->imageUrl,
            'tags'                           => $tags,
            'start_at'                       => Carbon::parse($this->startAt),
            'end_at'                         => Carbon::parse($this->endAt),
            'show_from'                      => $this->showFrom ? Carbon::parse($this->showFrom) : null,
            'time_leeway_minutes'            => $this->timeLeewayMinutes,
            'time_award_scale'               => $this->timeAwardScale,
            'restrictions'                   => $restrictions,
            'event_criteria'                 => $criteria,
            'registration_required'          => in_array($this->type, ['tour', 'roster', 'curated_roster', 'slotted_event']) ? true : $this->registrationRequired,
            'registration_start_at'          => $this->registrationStartAt ? Carbon::parse($this->registrationStartAt) : null,
            'registration_end_at'            => $this->registrationEndAt ? Carbon::parse($this->registrationEndAt) : null,
            'points_mode'                    => $this->pointsMode,
            'points_value'                   => $this->pointsValue,
            'point_award_mode'               => $this->pointAwardMode,
            'allow_repeat'                   => $this->type === 'slotted_event' ? false : $this->allowRepeat,
            'is_active'                      => $this->isActive,
            'community_metric'               => $this->communityMetric,
            'community_target'               => $this->communityTarget,
            'community_completion_points'    => $this->communityCompletionPoints,
            'community_tiered_multipliers'   => $this->communityTieredMultipliers,
            'slotted_departure_icao'         => strtoupper(trim($this->slottedDepartureIcao)),
            'slotted_callsign_system'        => $this->slottedCallsignSystem,
            'slotted_generator_pattern'      => $this->slottedGeneratorPattern,
            'slotted_dispatch_window_before' => $this->slottedDispatchWindowBefore,
            'slotted_dispatch_window_after'  => $this->slottedDispatchWindowAfter,
            'slotted_networks'               => $this->slottedNetworks,
        ];

        if ($this->editingActivityId) {
            $activity = Activity::where('tenant_id', $tenantId)->findOrFail($this->editingActivityId);
            $activity->update($data);
            $message = "Activity [{$activity->name}] updated successfully.";
        } else {
            $data['author_id'] = $user->id;
            $activity = Activity::create($data);
            $message = "Activity [{$activity->name}] created successfully.";
        }

        // Save Multi-Legs
        if (in_array($this->type, ['tour', 'roster', 'curated_roster'])) {
            $existingLegIds = [];
            foreach ($this->legs as $index => $legData) {
                $seq = $index + 1;
                $leg = ActivityLeg::updateOrCreate(
                    [
                        'id'          => $legData['id'] ?? null,
                        'activity_id' => $activity->id,
                    ],
                    [
                        'sequence'         => $seq,
                        'dep_icao'         => strtoupper(trim($legData['dep_icao'] ?? '')),
                        'arr_icao'         => strtoupper(trim($legData['arr_icao'] ?? '')),
                        'route_id'         => !empty($legData['route_id']) ? (int)$legData['route_id'] : null,
                        'airframe_id'      => !empty($legData['airframe_id']) ? (int)$legData['airframe_id'] : null,
                        'aircraft_type_id' => !empty($legData['aircraft_type_id']) ? (int)$legData['aircraft_type_id'] : null,
                        'show_from'        => !empty($legData['show_from']) ? Carbon::parse($legData['show_from']) : null,
                        'notes'            => $legData['notes'] ?? '',
                    ]
                );
                $existingLegIds[] = $leg->id;
            }

            // Remove deleted legs
            ActivityLeg::where('activity_id', $activity->id)->whereNotIn('id', $existingLegIds)->delete();
        }

        // Save Waves for Slotted Events
        if ($this->type === 'slotted_event') {
            $activityService = app(ActivityService::class);
            $existingWaveIds = [];
            foreach ($this->waves as $index => $waveData) {
                $pos = $index + 1;
                $wave = ActivityWave::updateOrCreate(
                    [
                        'id'          => $waveData['id'] ?? null,
                        'activity_id' => $activity->id,
                    ],
                    [
                        'name'             => $waveData['name'] ?? "Wave {$pos}",
                        'position'         => $pos,
                        'start_time'       => $waveData['start_time'] ?? '12:00',
                        'end_time'         => $waveData['end_time'] ?? '16:00',
                        'interval_minutes' => (int)($waveData['interval_minutes'] ?? 15),
                        'unlock_rule'      => $waveData['unlock_rule'] ?? 'always',
                    ]
                );
                $existingWaveIds[] = $wave->id;

                // Auto-generate slots for this wave
                $activityService->generateSlotsForWave($wave, $this->slottedNetworks);
            }

            ActivityWave::where('activity_id', $activity->id)->whereNotIn('id', $existingWaveIds)->delete();
        }

        // Save 2 Teams for Community Challenges
        if ($this->type === 'community_challenge') {
            $activity->teams()->delete();
            $activity->teams()->create([
                'name'         => $this->team1Name ?: 'Team Alpha',
                'count_type'   => $this->team1CountType,
                'target_value' => max(1, $this->team1Target),
            ]);
            $activity->teams()->create([
                'name'         => $this->team2Name ?: 'Team Bravo',
                'count_type'   => $this->team2CountType,
                'target_value' => max(1, $this->team2Target),
            ]);
        }

        $this->showActivityModal = false;
        session()->flash('activity_manager_message', $message);
    }

    public function deleteActivity(int $id)
    {
        $user = auth()->user();
        $tenantId = $user->getActiveTenantId() ?? $user->tenant_id;

        if (!$user->isSystemAdmin() && !$user->hasAirlinePermission('manage_activities', $tenantId)) {
            abort(403, 'Forbidden. You need [manage_activities] permission.');
        }

        $activity = Activity::where('tenant_id', $tenantId)->findOrFail($id);
        $name = $activity->name;
        $activity->delete();

        session()->flash('activity_manager_message', "Activity [{$name}] deleted successfully.");
    }

    public function copyActivity(int $id)
    {
        $user = auth()->user();
        $tenantId = $user->getActiveTenantId() ?? $user->tenant_id;

        if (!$user->isSystemAdmin() && !$user->hasAirlinePermission('manage_activities', $tenantId)) {
            abort(403, 'Forbidden. You need [manage_activities] permission.');
        }

        $orig = Activity::where('tenant_id', $tenantId)->with(['legs', 'waves'])->findOrFail($id);
        $copy = $orig->replicate();
        $copy->name = "Copy of {$orig->name}";
        $copy->slug = Str::slug($copy->name);
        $copy->is_active = false;
        $copy->created_at = Carbon::now();
        $copy->updated_at = Carbon::now();
        $copy->save();

        foreach ($orig->legs as $leg) {
            $newLeg = $leg->replicate();
            $newLeg->activity_id = $copy->id;
            $newLeg->save();
        }

        session()->flash('activity_manager_message', "Activity duplicated as [{$copy->name}].");
    }

    public function reprocessActivity(int $id)
    {
        $user = auth()->user();
        $tenantId = $user->getActiveTenantId() ?? $user->tenant_id;

        if (!$user->isSystemAdmin() && !$user->hasAirlinePermission('manage_activities', $tenantId)) {
            abort(403, 'Forbidden. You need [manage_activities] permission.');
        }

        $activity = Activity::where('tenant_id', $tenantId)->findOrFail($id);
        app(ActivityService::class)->reprocessActivity($activity);

        session()->flash('activity_manager_message', "Reprocessing initiated for [{$activity->name}]. Pilot logbooks and scoring updated.");
    }

    // ── Leg Operations ───────────────────────────────────────────────────────

    public function addLeg()
    {
        $seq = count($this->legs) + 1;
        $prevArr = count($this->legs) > 0 ? ($this->legs[count($this->legs) - 1]['arr_icao'] ?? '') : '';
        $this->legs[] = [
            'sequence'         => $seq,
            'dep_icao'         => $prevArr,
            'arr_icao'         => '',
            'route_id'         => null,
            'airframe_id'      => null,
            'aircraft_type_id' => null,
            'show_from'        => '',
            'notes'            => '',
        ];
    }

    public function removeLeg(int $index)
    {
        unset($this->legs[$index]);
        $this->legs = array_values($this->legs);
    }

    // ── Wave Operations ──────────────────────────────────────────────────────

    public function addWave()
    {
        $pos = count($this->waves) + 1;
        $this->waves[] = [
            'name'             => "Wave {$pos}",
            'position'         => $pos,
            'start_time'       => '12:00',
            'end_time'         => '16:00',
            'interval_minutes' => 15,
            'unlock_rule'      => 'always',
        ];
    }

    public function removeWave(int $index)
    {
        unset($this->waves[$index]);
        $this->waves = array_values($this->waves);
    }

    protected function resetActivityForm()
    {
        $this->reset([
            'editingActivityId',
            'name',
            'description',
            'sidebarTitle',
            'sidebarContent',
            'imageUrl',
            'tagsInput',
            'showFrom',
            'registrationStartAt',
            'registrationEndAt',
            'eventDepartureAirports',
            'eventArrivalAirports',
            'focusAirport',
            'slottedDepartureIcao',
            'restrictedNetworks',
            'restrictedFleets',
            'restrictedCallsignPrefixes',
            'maxLandingRate',
            'legs',
            'waves',
        ]);
        $this->type = 'event';
        $this->subtype = 'airport_based';
        $this->pointsValue = 150;
        $this->timeLeewayMinutes = 0;
        $this->timeAwardScale = 'any_part';
        $this->pointsMode = 'fixed';
        $this->pointAwardMode = 'per_leg';
        $this->allowRepeat = false;
        $this->isActive = true;
        $this->communityMetric = 'flights';
        $this->communityTarget = 1000;
        $this->communityCompletionPoints = 500;
        $this->communityTieredMultipliers = true;
        $this->slottedCallsignSystem = 'username_a';
        $this->slottedDispatchWindowBefore = 180;
        $this->slottedDispatchWindowAfter = 30;
        $this->slottedNetworks = ['vatsim', 'ivao', 'offline'];
        $this->activeModalTab = 'general';

        $now = Carbon::now('UTC');
        $this->startAt = $now->format('Y-m-d\TH:i');
        $this->endAt = $now->copy()->addDays(7)->format('Y-m-d\TH:i');
    }

    public function render()
    {
        $user = auth()->user();
        $tenantId = $user->getActiveTenantId() ?? $user->tenant_id;

        $query = Activity::where('tenant_id', $tenantId)->withCount(['registrations', 'legs']);

        if ($this->search) {
            $query->where('name', 'like', "%{$this->search}%");
        }

        if ($this->selectedTypeTab !== 'all') {
            if ($this->selectedTypeTab === 'current') {
                $now = Carbon::now('UTC');
                $query->where('is_active', true)->where('start_at', '<=', $now)->where('end_at', '>=', $now);
            } else {
                $query->where('type', $this->selectedTypeTab);
            }
        }

        $activities = $query->orderBy('created_at', 'desc')->paginate(12);

        // Fetch helper data for the modal
        $airports = Airport::select('id', 'icao', 'name')->orderBy('icao')->take(100)->get();
        $fleetTypes = AircraftType::where('tenant_id', $tenantId)->orderBy('name')->get();
        $airframes = Airframe::where('tenant_id', $tenantId)->orderBy('registration')->get();
        $routes = Route::where('tenant_id', $tenantId)->orderBy('flight_number')->take(100)->get();

        return view('livewire.admin.activity-manager', [
            'activities' => $activities,
            'airports'   => $airports,
            'fleetTypes' => $fleetTypes,
            'airframes'  => $airframes,
            'routes'     => $routes,
        ])->layout('layouts.app');
    }
}
