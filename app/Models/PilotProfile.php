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
    ];

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
