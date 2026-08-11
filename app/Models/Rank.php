<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class Rank extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'min_hours',
        'min_points',
        'image_url',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
