<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\HasCallsignMappings;
use App\Models\Concerns\HasTenantScoring;

class Tenant extends Model
{
    use HasCallsignMappings;
    use HasTenantScoring;

    protected $fillable = [
        'name',
        'domain',
        'icao',
        'secondary_icaos',
        'callsign_mappings',
        'accent_color',
        'bg_color',
        'panel_bg_color',
        'panel_text_color',
        'card_bg_color',
        'card_text_color',
        'card_muted_text_color',
        'button_bg_color',
        'button_text_color',
        'button_secondary_bg_color',
        'button_secondary_text_color',
        'input_bg_color',
        'input_text_color',
        'input_border_color',
        'logo_path',
        'default_simbrief_ofp_format',
        'scoring_settings',
        'is_approved',
        'status',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'secondary_icaos' => 'array',
        'callsign_mappings' => 'array',
        'scoring_settings' => 'array',
        'is_approved' => 'boolean',
        'approved_at' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function userAirlines()
    {
        return $this->hasMany(UserAirline::class, 'tenant_id');
    }

    public function enrolledUsers()
    {
        return $this->belongsToMany(User::class, 'user_airlines', 'tenant_id', 'user_id')
                    ->withPivot(['callsign', 'join_date', 'rank', 'is_active'])
                    ->withTimestamps();
    }

    public function hubs()
    {
        return $this->hasMany(TenantHub::class);
    }

    /**
     * Custom roles defined for this airline.
     */
    public function roles()
    {
        return $this->hasMany(AirlineRole::class, 'tenant_id');
    }

    /**
     * NOTAMs issued for this airline.
     */
    public function notams()
    {
        return $this->hasMany(Notam::class, 'tenant_id');
    }

    public function scopeApproved($query)
    {
        return $query->where('is_approved', true)->where('status', 'active');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Custom permissions created by this virtual airline.
     */
    public function customPermissions()
    {
        return $this->hasMany(AirlinePermission::class, 'tenant_id');
    }
}
