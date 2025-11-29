<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

class TenantService
{
    /**
     * Get the current tenant ID.
     *
     * @return int|null
     */
    public static function getCurrentTenantId(): ?int
    {
        if (Auth::check()) {
            $user = Auth::user();
            if ($user->isTenant()) {
                return $user->id;
            }
        }

        return null;
    }

    /**
     * Check if the current user is a tenant (reseller).
     *
     * @return bool
     */
    public static function isTenant(): bool
    {
        if (Auth::check()) {
            return Auth::user()->isTenant();
        }

        return false;
    }

    /**
     * Get the current tenant user.
     *
     * @return User|null
     */
    public static function getCurrentTenant(): ?User
    {
        $tenantId = self::getCurrentTenantId();

        if ($tenantId) {
            return User::find($tenantId);
        }

        return null;
    }
}

