<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TenantHub extends Model
{
    protected $fillable = ['tenant_id', 'airport_id', 'is_base'];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function airport()
    {
        return $this->belongsTo(Airport::class);
    }
}
