<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PirepComment extends Model
{
    protected $fillable = [
        'pirep_id',
        'user_id',
        'comment',
    ];

    public function pirep(): BelongsTo
    {
        return $this->belongsTo(Pirep::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
