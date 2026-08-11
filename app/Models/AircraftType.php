<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Traits\BelongsToTenant;

class AircraftType extends Model
{
    use BelongsToTenant;

    protected $fillable = ['code', 'name', 'tenant_id'];

    public function routes()
    {
        return $this->belongsToMany(Route::class);
    }
}
