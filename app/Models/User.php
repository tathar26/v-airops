<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens;

    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use HasProfilePhoto;
    use Notifiable;
    use TwoFactorAuthenticatable;
    use HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'first_name',
        'last_name',
        'email',
        'callsign',
        'password',
        'acars_password_hash',
        'tenant_id',
        'vatsim_id',
        'ivao_id',
        'poscon_id',
        'apoc_cid',
        'twitch_username',
        'youtube_username',
        'verification_token',
        'verification_token_expires_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'verification_token_expires_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function userAirlines()
    {
        return $this->hasMany(UserAirline::class);
    }

    public function airlines()
    {
        return $this->belongsToMany(Tenant::class, 'user_airlines', 'user_id', 'tenant_id')
                    ->withPivot(['callsign', 'join_date', 'rank', 'is_active'])
                    ->withTimestamps();
    }

    public function getTenantIdAttribute($value)
    {
        if ($value) {
            return (int) $value;
        }

        $sessionActive = session('active_airline_id');
        if ($sessionActive) {
            return (int) $sessionActive;
        }

        $firstEnrolled = $this->userAirlines()->first();
        return $firstEnrolled ? (int) $firstEnrolled->tenant_id : null;
    }

    public function getActiveTenantId(): ?int
    {
        return $this->tenant_id ? (int) $this->tenant_id : null;
    }

    public function activeUserAirline()
    {
        $activeTenantId = session('active_airline_id') ?? $this->tenant_id;
        if (!$activeTenantId) {
            return $this->userAirlines()->first();
        }

        return $this->userAirlines()->where('tenant_id', $activeTenantId)->first();
    }

    /**
     * Get user's formatted full name (e.g. Glenn Satory).
     */
    public function getFullNameAttribute(): string
    {
        $first = trim($this->first_name ?? '');
        $last = trim($this->last_name ?? '');

        if ($first !== '' || $last !== '') {
            return trim("{$first} {$last}");
        }

        return $this->name ?? 'Pilot';
    }

    /**
     * Get the active pilot callsign for the current virtual airline.
     */
    public function activeCallsign(): ?string
    {
        $activeRecord = $this->activeUserAirline();
        if ($activeRecord && $activeRecord->callsign) {
            return $activeRecord->callsign;
        }

        return $this->callsign ?: 'No Callsign';
    }

    /**
     * Get the pilot's active rank model for the current virtual airline.
     * If unassigned, defaults to the rank with the lowest required hours.
     */
    public function getActiveRankAttribute(): ?Rank
    {
        $tenantId = $this->getActiveTenantId() ?? $this->tenant_id;
        if (!$tenantId) {
            return null;
        }

        // 1. Check pilot profile rank_id
        $profile = $this->pilotProfiles()->where('tenant_id', $tenantId)->first();
        if ($profile && $profile->rank_id) {
            $rank = Rank::where('tenant_id', $tenantId)->find($profile->rank_id);
            if ($rank) {
                return $rank;
            }
        }

        // 2. Check user_airlines rank name
        $userAirline = $this->activeUserAirline();
        if ($userAirline && $userAirline->rank) {
            $rank = Rank::where('tenant_id', $tenantId)->where('name', $userAirline->rank)->first();
            if ($rank) {
                return $rank;
            }
        }

        // 3. Fallback: Rank with the lowest minimum hours required for this airline
        return Rank::where('tenant_id', $tenantId)
            ->orderBy('min_hours', 'asc')
            ->orderBy('min_points', 'asc')
            ->first();
    }

    /**
     * Get the pilot's active rank name string.
     */
    public function getActiveRankNameAttribute(): string
    {
        $rank = $this->active_rank;
        if ($rank) {
            return $rank->name;
        }

        $userAirline = $this->activeUserAirline();
        if ($userAirline && $userAirline->rank) {
            return $userAirline->rank;
        }

        return 'Cadet';
    }

    /**
     * Get the pilot's current location ICAO airport code for the active airline.
     */
    public function getCurrentLocationIcaoAttribute(): string
    {
        $tenantId = $this->getActiveTenantId() ?? $this->tenant_id;
        if ($tenantId) {
            $profile = $this->pilotProfiles()->where('tenant_id', $tenantId)->first();
            if ($profile) {
                return $profile->current_location_icao;
            }

            $baseHub = TenantHub::with('airport')->where('tenant_id', $tenantId)->where('is_base', true)->first();
            if ($baseHub && $baseHub->airport) {
                return $baseHub->airport->icao;
            }
        }

        return 'EGLL';
    }

    public function pilotProfiles()
    {
        return $this->hasMany(PilotProfile::class);
    }

    public function pireps()
    {
        return $this->hasMany(Pirep::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function acarsFlights()
    {
        return $this->hasMany(AcarsActiveFlight::class);
    }

    public function acarsPireps()
    {
        return $this->hasMany(AcarsPirep::class);
    }
}
