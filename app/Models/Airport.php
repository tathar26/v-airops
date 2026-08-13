<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Airport extends Model
{
    protected $fillable = ['icao', 'name', 'lat', 'lon', 'elevation', 'metadata'];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function tenantHubs()
    {
        return $this->hasMany(TenantHub::class);
    }
}
