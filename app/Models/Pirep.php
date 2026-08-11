<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Traits\BelongsToTenant;

class Pirep extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'user_id', 'tenant_id', 'route_id', 'airframe_id',
        'block_fuel', 'zfw', 'cost_index', 'touchdown_rate_fpm',
        'status', 'flight_log'
    ];

    protected $casts = [
        'flight_log' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function route()
    {
        return $this->belongsTo(Route::class);
    }

    public function airframe()
    {
        return $this->belongsTo(Airframe::class);
    }
}
