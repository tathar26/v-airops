<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    protected $fillable = [
        'name',
        'domain',
        'icao',
        'secondary_icaos',
        'accent_color',
        'bg_color',
        'panel_bg_color',
        'card_bg_color',
        'logo_path',
        'default_simbrief_ofp_format',
        'is_approved',
        'status',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'secondary_icaos' => 'array',
        'is_approved' => 'boolean',
        'approved_at' => 'datetime',
    ];

    /**
     * Get all airline ICAOs (primary default ICAO + all configured secondary ICAOs).
     *
     * @return array<int, string>
     */
    public function getAllIcaos(): array
    {
        $list = [];
        if (!empty($this->icao)) {
            $list[] = strtoupper(trim($this->icao));
        }

        if (!empty($this->secondary_icaos) && is_array($this->secondary_icaos)) {
            foreach ($this->secondary_icaos as $code) {
                $code = strtoupper(trim((string)$code));
                if (!empty($code) && !in_array($code, $list)) {
                    $list[] = $code;
                }
            }
        }

        return empty($list) ? ['VOPS'] : array_values($list);
    }

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

    public function scopeApproved($query)
    {
        return $query->where('is_approved', true)->where('status', 'active');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
