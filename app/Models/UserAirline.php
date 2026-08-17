<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserAirline extends Model
{
    use HasFactory;

    protected $table = 'user_airlines';

    protected $fillable = [
        'user_id',
        'tenant_id',
        'callsign',
        'join_date',
        'rank',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'join_date' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    // Alias for clarity
    public function airline()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }
}
