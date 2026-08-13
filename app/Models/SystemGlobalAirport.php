<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemGlobalAirport extends Model
{
    protected $fillable = [
        'name',
        'city',
        'country',
        'iata',
        'icao',
        'latitude',
        'longitude',
        'elevation',
    ];
}
