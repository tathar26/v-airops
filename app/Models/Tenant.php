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
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function hubs()
    {
        return $this->hasMany(TenantHub::class);
    }
}
