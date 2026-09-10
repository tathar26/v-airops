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
use App\Models\Concerns\HasAirlineRolesAndPermissions;
use App\Models\Concerns\HasAirlineNotams;
use App\Models\Concerns\HasPilotRanks;
use App\Models\Concerns\HasTenantContext;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens;

    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use HasProfilePhoto;
    use Notifiable;
    use TwoFactorAuthenticatable;
    use HasRoles;
    use HasAirlineRolesAndPermissions;
    use HasAirlineNotams;
    use HasPilotRanks;
    use HasTenantContext;

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
