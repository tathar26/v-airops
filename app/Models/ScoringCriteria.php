<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class ScoringCriteria extends Model
{
    use HasFactory, BelongsToTenant;

    protected $table = 'scoring_criteria';

    protected $fillable = [
        'tenant_id',
        'name',
        'condition',
        'points_awarded',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
