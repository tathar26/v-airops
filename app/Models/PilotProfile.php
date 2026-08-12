<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class PilotProfile extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'user_id',
        'tenant_id',
        'rank_id',
        'flight_time',
        'points',
        'use_imperial_units',
        'prefer_honorary_rank',
        'preferred_network',
        'simbrief_ofp_format',
        'current_airport_id',
    ];

    public function currentAirport()
    {
        return $this->belongsTo(Airport::class, 'current_airport_id');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'use_imperial_units' => 'boolean',
            'prefer_honorary_rank' => 'boolean',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function rank()
    {
        return $this->belongsTo(Rank::class);
    }
}
