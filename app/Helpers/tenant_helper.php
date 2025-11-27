<?php

if (!function_exists('is_tenant_user')) {
    /**
     * Check if the current authenticated user is a tenant (user_type_id = 3).
     *
     * @return bool
     */
    function is_tenant_user(): bool
    {
        return \App\Services\TenantService::isTenant();
    }
}

if (!function_exists('current_tenant_id')) {
    /**
     * Get the current tenant ID.
     *
     * @return int|null
     */
    function current_tenant_id(): ?int
    {
        return \App\Services\TenantService::getCurrentTenantId();
    }
}

if (!function_exists('current_tenant')) {
    /**
     * Get the current tenant user.
     *
     * @return \App\Models\User|null
     */
    function current_tenant(): ?\App\Models\User
    {
        return \App\Services\TenantService::getCurrentTenant();
    }
}

