<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivitySlot extends Model
{
    protected $table = 'activity_slots';

    protected $fillable = [
        'wave_id',
        'activity_id',
        'network',
        'slot_time',
        'user_id',
        'callsign_suffix',
        'booked_at',
    ];

    protected $casts = [
        'slot_time' => 'datetime',
        'booked_at' => 'datetime',
    ];

    public function wave(): BelongsTo
    {
        return $this->belongsTo(ActivityWave::class, 'wave_id');
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class, 'activity_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function isBooked(): bool
    {
        return !is_null($this->user_id);
    }

    public function canDispatch(): bool
    {
        if (!$this->isBooked()) {
            return false;
        }

        $now = Carbon::now('UTC');
        $beforeMinutes = $this->activity->slotted_dispatch_window_before ?? 180;
        $afterMinutes = $this->activity->slotted_dispatch_window_after ?? 30;

        $windowStart = $this->slot_time->copy()->subMinutes($beforeMinutes);
        $windowEnd = $this->slot_time->copy()->addMinutes($afterMinutes);

        return $now->between($windowStart, $windowEnd);
    }
}
