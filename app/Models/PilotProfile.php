<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class PilotProfile extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'user_id',
        'tenant_id',
        'rank_id',
        'honorary_rank_id',
        'flight_time',
        'points',
        'bonus_points',
        'use_imperial_units',
        'prefer_honorary_rank',
        'preferred_network',
        'simbrief_ofp_format',
        'simbrief_username',
        'current_airport_id',
    ];

    public function currentAirport()
    {
        return $this->belongsTo(Airport::class, 'current_airport_id');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'flight_time' => 'integer',
            'points' => 'integer',
            'bonus_points' => 'integer',
            'use_imperial_units' => 'boolean',
            'prefer_honorary_rank' => 'boolean',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function rank()
    {
        return $this->belongsTo(Rank::class);
    }

    public function honoraryRank()
    {
        return $this->belongsTo(Rank::class, 'honorary_rank_id');
    }

    /**
     * Get the active display rank model (Honorary if preferred & assigned, else Regular rank).
     */
    public function getDisplayRank(): ?Rank
    {
        if ($this->prefer_honorary_rank && $this->honorary_rank_id) {
            $honorary = $this->honoraryRank;
            if ($honorary) {
                return $honorary;
            }
        }

        return $this->rank;
    }

    public function getDisplayRankName(): string
    {
        return $this->getDisplayRank()?->name ?? 'Cadet';
    }

    public function getDisplayRankAbbreviation(): string
    {
        return $this->getDisplayRank()?->abbreviation ?? 'Cdt';
    }

    public function getDisplayRankImageUrl(): string
    {
        return $this->getDisplayRank()?->image_url ?? asset('images/epaulettes/epaulette-01.png');
    }

    /**
     * Resolve the pilot's current airport location ICAO code.
     */
    public function getCurrentLocationIcaoAttribute(): string
    {
        // 1. If current_airport_id is assigned
        if ($this->current_airport_id && $this->currentAirport) {
            return $this->currentAirport->icao;
        }

        // 2. Try to find the latest completed/accepted PIREP arrival airport
        $lastPirep = Pirep::where('user_id', $this->user_id)
            ->where('tenant_id', $this->tenant_id)
            ->whereIn('status', ['Accepted', 'Complete', 'filed'])
            ->latest('created_at')
            ->first();

        if ($lastPirep && $lastPirep->route && $lastPirep->route->arrival_icao) {
            return $lastPirep->route->arrival_icao;
        }

        // 3. Fallback to Tenant Base Hub
        $baseHub = TenantHub::with('airport')
            ->where('tenant_id', $this->tenant_id)
            ->where('is_base', true)
            ->first();

        if ($baseHub && $baseHub->airport) {
            return $baseHub->airport->icao;
        }

        // 4. Any hub for this tenant
        $anyHub = TenantHub::with('airport')
            ->where('tenant_id', $this->tenant_id)
            ->first();

        if ($anyHub && $anyHub->airport) {
            return $anyHub->airport->icao;
        }

        // 5. Fallback to first route departure airport for this tenant
        $firstRoute = Route::where('tenant_id', $this->tenant_id)->first();
        if ($firstRoute && $firstRoute->departure_icao) {
            return $firstRoute->departure_icao;
        }

        return 'EGLL';
    }
}
