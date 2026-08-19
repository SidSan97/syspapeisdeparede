<?php

namespace App\Repositories;

use App\Models\Reseller;
use App\Models\User;
use Exception;

class ResellerRepository
{
    public function findResellerByTenantId(int $tenantId): Reseller
    {
        $tenant = User::query()
            ->with('reseller')
            ->find($tenantId);

        $reseller = $tenant?->reseller;

        if (! $reseller) {
            throw new Exception('Revendedor não encontrado.');
        }

        return $reseller;
    }
}
