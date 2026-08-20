<?php

namespace App\Traits;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant()
    {
        // When checking for tenant, we rely on the authenticated user's tenant_id or session/subdomain setup.
        // For now, if a user is authenticated and has a tenant_id, we scope by it.
        // Master admins (tenant_id = null) will bypass this scope or need special handling.
        
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (auth()->check()) {
                $tenantId = auth()->user()->getActiveTenantId() ?? auth()->user()->tenant_id;
                if ($tenantId) {
                    $builder->where($builder->getModel()->getTable() . '.tenant_id', $tenantId);
                }
            }
        });

        static::creating(function ($model) {
            if (empty($model->tenant_id) && auth()->check()) {
                $tenantId = auth()->user()->getActiveTenantId() ?? auth()->user()->tenant_id;
                if ($tenantId) {
                    $model->tenant_id = $tenantId;
                }
            }
        });
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
