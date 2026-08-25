<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AirlinePermission extends Model
{
    protected $table = 'airline_permissions';

    protected $fillable = [
        'name',
        'slug',
        'group',
        'description',
    ];

    /**
     * Roles holding this permission.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            AirlineRole::class,
            'airline_role_permissions',
            'permission_id',
            'role_id'
        );
    }
}
