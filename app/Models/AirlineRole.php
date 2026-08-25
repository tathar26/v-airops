<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AirlineRole extends Model
{
    protected $table = 'airline_roles';

    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'description',
        'honorary_rank_string',
        'is_staff',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'is_staff'   => 'boolean',
            'is_default' => 'boolean',
        ];
    }

    /**
     * The Virtual Airline (Tenant) that owns this role.
     */
    public function airline(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    /**
     * Alias for airline relationship.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    /**
     * Granular permissions granted to this role.
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            AirlinePermission::class,
            'airline_role_permissions',
            'role_id',
            'permission_id'
        );
    }

    /**
     * Users assigned this role within this specific airline.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'user_airline_roles',
            'role_id',
            'user_id'
        )->withPivot('tenant_id')->withTimestamps();
    }

    /**
     * Check if this role possesses a specific permission slug.
     */
    public function hasPermission(string $permissionSlug): bool
    {
        if ($this->relationLoaded('permissions')) {
            return $this->permissions->contains('slug', $permissionSlug);
        }

        return $this->permissions()->where('slug', $permissionSlug)->exists();
    }

    /**
     * Sync permissions by slug array or ID array.
     */
    public function syncPermissions(array $permissionSlugsOrIds): void
    {
        $ids = [];
        foreach ($permissionSlugsOrIds as $item) {
            if (is_numeric($item)) {
                $ids[] = (int) $item;
            } else {
                $perm = AirlinePermission::where('slug', $item)->first();
                if ($perm) {
                    $ids[] = $perm->id;
                }
            }
        }

        $this->permissions()->sync($ids);
    }
}
