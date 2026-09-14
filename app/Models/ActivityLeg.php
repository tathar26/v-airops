<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ActivityLeg extends Model
{
    protected $table = 'activity_legs';

    protected $fillable = [
        'activity_id',
        'sequence',
        'dep_icao',
        'arr_icao',
        'route_id',
        'airframe_id',
        'aircraft_type_id',
        'show_from',
        'notes',
    ];

    protected $casts = [
        'sequence' => 'integer',
        'show_from' => 'datetime',
    ];

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class, 'activity_id');
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class, 'route_id');
    }

    public function airframe(): BelongsTo
    {
        return $this->belongsTo(Airframe::class, 'airframe_id');
    }

    public function aircraftType(): BelongsTo
    {
        return $this->belongsTo(AircraftType::class, 'aircraft_type_id');
    }

    public function progress(): HasMany
    {
        return $this->hasMany(ActivityLegProgress::class, 'activity_leg_id');
    }

    public function isVisible(): bool
    {
        if (!$this->show_from) {
            return true;
        }

        return Carbon::now('UTC')->gte($this->show_from);
    }
}
