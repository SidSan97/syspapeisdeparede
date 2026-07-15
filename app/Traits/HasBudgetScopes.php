<?php

namespace App\Traits;

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
     * Scope para busca por nome ou ID
     */
    public function scopeSearch($query, ?string $search)
    {
        if (empty($search)) {
            return $query;
        }

        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")->orWhere('id', 'like', "%{$search}%");
        });
    }

    /**
     * Scope para filtrar por status
     */
    public function scopeByStatus($query, ?string $status)
    {
        if (! empty($status) && $status !== 'all') {
            return $query->where('status', $status);
        }

        return $query;
    }

    /**
     * Scope para filtrar por período (data de criação)
     */
    public function scopeByDateRange($query, ?string $dateFrom = null, ?string $dateTo = null)
    {
        if (! empty($dateFrom)) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if (! empty($dateTo)) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        return $query;
    }

    /**
     * Scope para filtrar por usuário/revendedor
     */
    public function scopeByUserId($query, ?int $userId)
    {
        if (! empty($userId)) {
            return $query->where('user_id', $userId);
        }

        return $query;
    }
}
