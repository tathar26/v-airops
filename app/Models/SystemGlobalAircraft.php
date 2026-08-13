<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemGlobalAircraft extends Model
{
    protected $table = 'system_global_aircraft';

    protected $fillable = [
        'original_tenant_id',
        'code',
        'name',
    ];
}
