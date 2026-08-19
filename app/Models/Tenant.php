<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    protected $fillable = [
        'name',
        'domain',
        'icao',
        'accent_color',
        'bg_color',
        'logo_path',
        'default_simbrief_ofp_format',
        'is_approved',
        'status',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
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

    public function scopeApproved($query)
    {
        return $query->where('is_approved', true)->where('status', 'active');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
