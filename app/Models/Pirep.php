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
        'status', 'flight_log', 'flight_time', 'points_awarded', 'created_at',
        'network',
        'simulator',
        'is_day_takeoff',
        'is_day_landing',
        'is_event',
        'passengers',
        'freight'
    ];

    protected $casts = [
        'flight_log' => 'array',
        'is_day_takeoff' => 'boolean',
        'is_day_landing' => 'boolean',
        'is_event' => 'boolean',
        'flight_time' => 'integer',
        'points_awarded' => 'integer',
        'touchdown_rate_fpm' => 'integer',
        'passengers' => 'integer',
        'freight' => 'integer',
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

    public function comments()
    {
        return $this->hasMany(PirepComment::class);
    }
}
