<?php

namespace App\Traits;

use App\Models\Budget;
use Illuminate\Database\Eloquent\Builder;

trait HasBudgetScopes
{
    /**
     * Scope para filtrar orçamentos por permissões do usuário
     * Aplica filtro apenas se o usuário não for admin ou comercial
     */
    public function scopeForUser($query, $user)
    {
        if (! $user->isAdmin() && ! $user->isCommercial()) {
            return $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)->orWhere('tenant_id', $user->id);
            });
        }

        return $query;
    }

    /**
     * @param  Builder<Budget>  $query
     * @return Builder<Budget>
     */
    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (blank($search)) {
            return $query;
        }

        return $query->where(
            function ($q) use ($search) {
                $q
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('id', $search);
            }
        );
    }
}
