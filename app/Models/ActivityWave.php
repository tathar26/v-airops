<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ActivityWave extends Model
{
    protected $table = 'activity_waves';

    protected $fillable = [
        'activity_id',
        'name',
        'position',
        'start_time',
        'end_time',
        'interval_minutes',
        'interval_type',
        'custom_minutes',
        'unlock_rule',
        'scheduled_unlock_at',
    ];

    protected $casts = [
        'position' => 'integer',
        'interval_minutes' => 'integer',
        'custom_minutes' => 'array',
        'scheduled_unlock_at' => 'datetime',
    ];

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class, 'activity_id');
    }

    public function slots(): HasMany
    {
        return $this->hasMany(ActivitySlot::class, 'wave_id')->orderBy('slot_time', 'asc');
    }

    public function isUnlocked(string $network = 'offline'): bool
    {
        if ($this->unlock_rule === 'always') {
            return true;
        }

        if ($this->unlock_rule === 'scheduled') {
            return $this->scheduled_unlock_at && Carbon::now('UTC')->gte($this->scheduled_unlock_at);
        }

        if ($this->unlock_rule === 'previous_wave_full') {
            // Find previous wave by position
            $prevWave = ActivityWave::where('activity_id', $this->activity_id)
                ->where('position', '<', $this->position)
                ->orderBy('position', 'desc')
                ->first();

            if (!$prevWave) {
                return true;
            }

            // Check if all slots in previous wave for this network are booked
            $totalSlots = $prevWave->slots()->where('network', $network)->count();
            if ($totalSlots === 0) {
                return true;
            }

            $bookedSlots = $prevWave->slots()->where('network', $network)->whereNotNull('user_id')->count();
            return $bookedSlots >= $totalSlots;
        }

        return true;
    }
}
