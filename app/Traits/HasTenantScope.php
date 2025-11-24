<?php

namespace App\Traits;

use App\Models\User;
use App\Scopes\TenantScope;
use Illuminate\Support\Facades\Auth;

trait HasTenantScope
{
    /**
     * Boot the trait and apply the tenant scope.
     */
    protected static function bootHasTenantScope()
    {
        static::addGlobalScope(new TenantScope);
    }

    /**
     * Get the tenant owner of this model.
     */
    public function tenant()
    {
        return $this->belongsTo(User::class, 'tenant_id');
    }

    /**
     * Scope a query to only include records for a specific tenant.
     */
    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope a query to include all records (bypass tenant scope).
     */
    public function scopeWithoutTenant($query)
    {
        return $query->withoutGlobalScope(TenantScope::class);
    }
}

