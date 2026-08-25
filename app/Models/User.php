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
        'is_system_admin',
        'prefer_honorary_rank',
        'vatsim_id',
        'ivao_id',
        'poscon_id',
        'apoc_cid',
        'twitch_username',
        'youtube_username',
        'verification_token',
        'verification_token_expires_at',
        'verification_email_sent_at',
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
            'verification_email_sent_at' => 'datetime',
            'is_system_admin' => 'boolean',
            'prefer_honorary_rank' => 'boolean',
            'password' => 'hashed',
        ];
    }

    /**
     * Check if user can resend a verification email (5-minute cooldown).
     */
    public function canResendVerificationEmail(): bool
    {
        if (!$this->verification_email_sent_at) {
            return true;
        }

        return $this->verification_email_sent_at->copy()->addMinutes(5)->isPast();
    }

    /**
     * Get remaining cooldown seconds before next verification email can be sent.
     */
    public function verificationResendCooldownSeconds(): int
    {
        if (!$this->verification_email_sent_at) {
            return 0;
        }

        $availableAt = $this->verification_email_sent_at->copy()->addMinutes(5);
        if ($availableAt->isPast()) {
            return 0;
        }

        return max(0, (int) now()->diffInSeconds($availableAt, false));
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

    /**
     * User's assigned roles across all virtual airlines.
     */
    public function airlineRoles(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(UserAirlineRole::class, 'user_id');
    }

    /* =========================================================================
     | Global System Administrator Helpers
     |======================================================================== */

    /**
     * Check if the user is a Global System Administrator.
     */
    public function isSystemAdmin(): bool
    {
        return (bool) $this->is_system_admin || $this->hasRole('Master Admin');
    }

    /* =========================================================================
     | Multi-Tenant Permission & Role Methods
     |======================================================================== */

    /**
     * Check if the user has a specific permission within an airline context.
     * GLOBAL SYSTEM ADMINISTRATORS AUTOMATICALLY BYPASS THIS CHECK.
     */
    public function hasAirlinePermission(string $permission, int|Tenant|null $airline = null): bool
    {
        // 1. Global System Administrator Override: Always allow
        if ($this->isSystemAdmin()) {
            return true;
        }

        $airlineId = $this->resolveAirlineId($airline);
        if (!$airlineId) {
            return false;
        }

        // 2. VA Creator / Owner automatic full access for their airline
        $tenant = Tenant::find($airlineId);
        if ($tenant && ($tenant->created_by === $this->id || $this->hasRole('VA Owner'))) {
            return true;
        }

        // 3. Evaluate permissions across user's assigned roles for this airline
        $roles = $this->getRolesForAirline($airlineId);
        foreach ($roles as $role) {
            if ($role->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if the user holds a specific role in an airline context.
     */
    public function hasAirlineRole(string|array $roleSlug, int|Tenant|null $airline = null): bool
    {
        if ($this->isSystemAdmin()) {
            return true;
        }

        $airlineId = $this->resolveAirlineId($airline);
        if (!$airlineId) {
            return false;
        }

        $slugs = is_array($roleSlug) ? $roleSlug : [$roleSlug];
        $userRoles = $this->getRolesForAirline($airlineId);

        return $userRoles->whereIn('slug', $slugs)->isNotEmpty();
    }

    /**
     * Retrieve all active roles assigned to this user within a specific airline.
     */
    public function getRolesForAirline(int|Tenant|null $airline = null): \Illuminate\Support\Collection
    {
        $airlineId = $this->resolveAirlineId($airline);
        if (!$airlineId) {
            return collect();
        }

        return AirlineRole::with('permissions')
            ->where('tenant_id', $airlineId)
            ->whereIn('id', function ($query) use ($airlineId) {
                $query->select('role_id')
                    ->from('user_airline_roles')
                    ->where('user_id', $this->id)
                    ->where('tenant_id', $airlineId);
            })
            ->get();
    }

    /**
     * Assign an airline-scoped role to this user.
     */
    public function assignAirlineRole(AirlineRole|int $role, int|Tenant|null $airline = null): void
    {
        $roleModel = is_numeric($role) ? AirlineRole::findOrFail($role) : $role;
        $airlineId = $this->resolveAirlineId($airline) ?? $roleModel->tenant_id;

        UserAirlineRole::firstOrCreate([
            'user_id'   => $this->id,
            'tenant_id' => $airlineId,
            'role_id'   => $roleModel->id,
        ]);
    }

    /**
     * Remove an airline-scoped role from this user.
     */
    public function removeAirlineRole(AirlineRole|int $role, int|Tenant|null $airline = null): void
    {
        $roleId = is_object($role) ? $role->id : (int) $role;
        $airlineId = $this->resolveAirlineId($airline) ?? ($role instanceof AirlineRole ? $role->tenant_id : null);

        $query = UserAirlineRole::where('user_id', $this->id)->where('role_id', $roleId);
        if ($airlineId) {
            $query->where('tenant_id', $airlineId);
        }
        $query->delete();
    }

    /* =========================================================================
     | The Pilot Rank vs. Honorary Staff Rank System
     |======================================================================== */

    /**
     * Auto-calculate the pilot's standard rank based on total flight hours for this airline.
     */
    public function getAutoCalculatedRank(int|Tenant|null $airline = null): ?Rank
    {
        $airlineId = $this->resolveAirlineId($airline);
        if (!$airlineId) {
            return null;
        }

        // Get total flight time for this airline in minutes -> hours
        $profile = $this->pilotProfiles()->where('tenant_id', $airlineId)->first();
        $totalHours = $profile ? floor($profile->flight_time / 60) : 0;
        $totalPoints = $profile ? $profile->points : 0;

        // 1. Check if profile has an explicitly locked rank_id
        if ($profile && $profile->rank_id) {
            $explicitRank = Rank::where('tenant_id', $airlineId)->find($profile->rank_id);
            if ($explicitRank) {
                return $explicitRank;
            }
        }

        // 2. Highest matching rank meeting min_hours & min_points
        $calculatedRank = Rank::where('tenant_id', $airlineId)
            ->where('min_hours', '<=', $totalHours)
            ->where('min_points', '<=', $totalPoints)
            ->orderBy('min_hours', 'desc')
            ->orderBy('min_points', 'desc')
            ->first();

        if ($calculatedRank) {
            return $calculatedRank;
        }

        // 3. Fallback: Base rank with the lowest hours
        return Rank::where('tenant_id', $airlineId)
            ->orderBy('min_hours', 'asc')
            ->orderBy('min_points', 'asc')
            ->first();
    }

    /**
     * Get the honorary staff rank string if user holds a staff role with one in this airline.
     */
    public function getHonoraryRankString(int|Tenant|null $airline = null): ?string
    {
        $roles = $this->getRolesForAirline($airline);

        foreach ($roles as $role) {
            if ($role->is_staff && !empty($role->honorary_rank_string)) {
                return $role->honorary_rank_string;
            }
        }

        return null;
    }

    /**
     * Master Display Rank Logic:
     * - Returns the Honorary Staff Rank if prefer_honorary_rank is TRUE and the user holds a staff role with an honorary rank in this airline.
     * - Otherwise, returns the standard flight-hour auto-calculated rank name.
     */
    public function getDisplayRank(int|Tenant|null $airline = null): string
    {
        $airlineId = $this->resolveAirlineId($airline);

        // 1. If user prefers honorary rank and has an assigned staff role with an honorary rank in this airline
        if ($this->prefer_honorary_rank) {
            $honorary = $this->getHonoraryRankString($airlineId);
            if (!empty($honorary)) {
                return $honorary;
            }
        }

        // 2. Fallback to auto-calculated rank based on flight hours
        $autoRank = $this->getAutoCalculatedRank($airlineId);
        if ($autoRank) {
            return $autoRank->name;
        }

        // 3. Final default
        $userAirline = $this->activeUserAirline();
        if ($userAirline && $userAirline->rank) {
            return $userAirline->rank;
        }

        return 'Cadet';
    }

    /**
     * Get the pilot's active rank name string (uses display rank logic).
     */
    public function getActiveRankNameAttribute(): string
    {
        return $this->getDisplayRank();
    }

    /**
     * Dynamic display rank accessor.
     */
    public function getDisplayRankAttribute(): string
    {
        return $this->getDisplayRank();
    }

    /**
     * Helper to resolve active tenant/airline ID.
     */
    public function resolveAirlineId(int|Tenant|null $airline = null): ?int
    {
        if ($airline instanceof Tenant) {
            return (int) $airline->id;
        }

        if (is_numeric($airline)) {
            return (int) $airline;
        }

        return session('active_airline_id') ?? $this->tenant_id;
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
     */
    public function getActiveRankAttribute(): ?Rank
    {
        return $this->getAutoCalculatedRank();
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
