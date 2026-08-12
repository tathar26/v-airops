<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use \App\Traits\BelongsToTenant;

    protected $fillable = [
        'user_id',
        'tenant_id',
        'route_id',
        'airframe_id',
        'status',
        'simbrief_data',
    ];

    protected $casts = [
        'simbrief_data' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
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
