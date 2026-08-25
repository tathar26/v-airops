<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AirlinePermission extends Model
{
    protected $table = 'airline_permissions';

    protected $fillable = [
        'name',
        'slug',
        'group',
        'description',
    ];

    /**
     * Roles holding this permission.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            AirlineRole::class,
            'airline_role_permissions',
            'permission_id',
            'role_id'
        );
    }

    /**
     * Standard granular permissions catalog grouped by category.
     */
    public static function defaultPermissions(): array
    {
        return [
            // Routes
            ['name' => 'View Routes', 'slug' => 'view_routes', 'group' => 'Routes', 'description' => 'View airline route network and schedules'],
            ['name' => 'Create Routes', 'slug' => 'create_routes', 'group' => 'Routes', 'description' => 'Create new flight routes and import schedules'],
            ['name' => 'Edit Routes', 'slug' => 'edit_routes', 'group' => 'Routes', 'description' => 'Modify existing flight routes and assignments'],
            ['name' => 'Delete Routes', 'slug' => 'delete_routes', 'group' => 'Routes', 'description' => 'Delete routes from airline network'],

            // Fleet
            ['name' => 'View Fleet', 'slug' => 'view_fleet', 'group' => 'Fleet', 'description' => 'View airline aircraft types and airframe fleet'],
            ['name' => 'Manage Fleet', 'slug' => 'manage_fleet', 'group' => 'Fleet', 'description' => 'Add, edit, or remove airframes from airline fleet'],
            ['name' => 'Manage Aircraft Types', 'slug' => 'manage_aircraft_types', 'group' => 'Fleet', 'description' => 'Create and configure aircraft types and specs'],

            // Airports
            ['name' => 'View Airports', 'slug' => 'view_airports', 'group' => 'Airports', 'description' => 'View airline airport network and base hubs'],
            ['name' => 'Manage Airports', 'slug' => 'manage_airports', 'group' => 'Airports', 'description' => 'Configure airline airport hubs and bases'],

            // PIREPs
            ['name' => 'View PIREPs', 'slug' => 'view_pireps', 'group' => 'PIREPs', 'description' => 'View all submitted pilot reports'],
            ['name' => 'Review PIREPs', 'slug' => 'review_pireps', 'group' => 'PIREPs', 'description' => 'Accept, reject, or adjust pilot reports'],
            ['name' => 'Delete PIREPs', 'slug' => 'delete_pireps', 'group' => 'PIREPs', 'description' => 'Delete flight reports from the database'],

            // Operations & Settings
            ['name' => 'View VA Settings', 'slug' => 'view_settings', 'group' => 'Settings', 'description' => 'View virtual airline configuration and details'],
            ['name' => 'Manage Airline Settings', 'slug' => 'manage_airline_settings', 'group' => 'Settings', 'description' => 'Update branding, theme colors, and SimBrief defaults'],
            ['name' => 'Manage Ranks', 'slug' => 'manage_ranks', 'group' => 'Ranks & Roles', 'description' => 'Configure flight-hour rank criteria and points'],
            ['name' => 'Manage Roles & Staff', 'slug' => 'manage_roles', 'group' => 'Ranks & Roles', 'description' => 'Create custom roles, assign permissions and honorary ranks'],
            ['name' => 'Manage Pilots', 'slug' => 'manage_pilots', 'group' => 'Pilots', 'description' => 'Manage enrolled pilots and callsign assignments'],
            ['name' => 'View Finance & Stats', 'slug' => 'view_finance', 'group' => 'Analytics', 'description' => 'View operational financial breakdown and pilot stats'],
        ];
    }

    /**
     * Ensure all default permissions are seeded in the database.
     */
    public static function ensureDefaults(): void
    {
        foreach (static::defaultPermissions() as $item) {
            static::firstOrCreate(
                ['slug' => $item['slug']],
                [
                    'name'        => $item['name'],
                    'group'       => $item['group'],
                    'description' => $item['description'],
                ]
            );
        }
    }
}
