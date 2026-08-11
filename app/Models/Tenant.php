<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    protected $fillable = [
        'name',
        'domain',
        'accent_color',
        'bg_color',
        'logo_path',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
