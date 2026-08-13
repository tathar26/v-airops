<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemGlobalAirline extends Model
{
    protected $table = 'system_global_airlines';

    protected $fillable = [
        'name',
        'iata',
        'icao',
        'callsign',
        'country',
        'active',
        'hash',
    ];
}
