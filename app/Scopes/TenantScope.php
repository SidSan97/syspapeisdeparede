<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class TenantScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function apply(Builder $builder, Model $model)
    {
        if (Auth::check()) {
            $user = Auth::user();

            // Only apply tenant scope for users with user_type_id = 4
            if ($user->user_type_id == 4) {
                $builder->where(function ($query) use ($user) {
                    $query->where('tenant_id', $user->id)
                          ->orWhereNull('tenant_id'); // Include records without tenant (global data)
                });
            }
        }
    }

    /**
     * Extend the query builder with needed macros.
     */
    public function extend(Builder $builder)
    {
        $builder->macro('withoutTenant', function (Builder $builder) {
            return $builder->withoutGlobalScope($this);
        });
    }
}

