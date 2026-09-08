<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use App\Traits\BelongsToTenant;

class Rank extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'abbreviation',
        'position',
        'min_hours',
        'min_points',
        'min_bonus_points',
        'min_pireps',
        'is_honorary',
        'is_default',
        'image_path',
        'image_url',
    ];

    protected $casts = [
        'position' => 'integer',
        'min_hours' => 'integer',
        'min_points' => 'integer',
        'min_bonus_points' => 'integer',
        'min_pireps' => 'integer',
        'is_honorary' => 'boolean',
        'is_default' => 'boolean',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function pilotProfiles()
    {
        return $this->hasMany(PilotProfile::class);
    }

    public function honoraryPilotProfiles()
    {
        return $this->hasMany(PilotProfile::class, 'honorary_rank_id');
    }

    public function scopeRegular(Builder $query): Builder
    {
        return $query->where('is_honorary', false);
    }

    public function scopeHonorary(Builder $query): Builder
    {
        return $query->where('is_honorary', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('position', 'asc')
            ->orderBy('min_hours', 'asc')
            ->orderBy('min_points', 'asc');
    }

    /**
     * Get the resolved URL for this rank's epaulette image.
     */
    public function getImageUrlAttribute(): string
    {
        $path = $this->image_path ?: $this->attributes['image_url'] ?? null;

        if (!$path) {
            // Default based on type
            return $this->is_honorary 
                ? asset('images/epaulettes/epaulette-staff.png') 
                : asset('images/epaulettes/epaulette-01.png');
        }

        // Full URL
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        // Pre-packaged epaulette asset
        if (str_starts_with($path, 'epaulettes/')) {
            return asset('images/' . $path);
        }

        if (str_starts_with($path, 'images/')) {
            return asset($path);
        }

        // Uploaded to storage disk
        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->url($path);
        }

        return asset($path);
    }

    /**
     * List of built-in epaulette options available for virtual airline branding.
     */
    public static function availableBuiltinEpaulettes(): array
    {
        return [
            [
                'id' => 'epaulettes/epaulette-01.png',
                'name' => '1 Gold Stripe (Cadet / Second Officer)',
                'preview' => asset('images/epaulettes/epaulette-01.png'),
            ],
            [
                'id' => 'epaulettes/epaulette-02.png',
                'name' => '2 Gold Stripes (Junior First Officer)',
                'preview' => asset('images/epaulettes/epaulette-02.png'),
            ],
            [
                'id' => 'epaulettes/epaulette-03.png',
                'name' => '3 Gold Stripes (First Officer / Senior FO)',
                'preview' => asset('images/epaulettes/epaulette-03.png'),
            ],
            [
                'id' => 'epaulettes/epaulette-04.png',
                'name' => '4 Gold Stripes (Captain)',
                'preview' => asset('images/epaulettes/epaulette-04.png'),
            ],
            [
                'id' => 'epaulettes/epaulette-tri.png',
                'name' => '3 Stripes + Star (Type Rating Instructor)',
                'preview' => asset('images/epaulettes/epaulette-tri.png'),
            ],
            [
                'id' => 'epaulettes/epaulette-tre.png',
                'name' => '4 Stripes + Star (Type Rating Examiner)',
                'preview' => asset('images/epaulettes/epaulette-tre.png'),
            ],
            [
                'id' => 'epaulettes/epaulette-staff.png',
                'name' => 'Gold Winged Crest (Staff Team / Honorary)',
                'preview' => asset('images/epaulettes/epaulette-staff.png'),
            ],
        ];
    }
}
