<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemGlobalAirframe extends Model
{
    use HasFactory;

    protected $fillable = [
        'registration',
        'icao_code',
        'operator',
        'name',
    ];
}
