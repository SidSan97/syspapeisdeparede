<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Filter;

class BudgetSearchFilter implements Filter
{
    public function __invoke(
        Builder $query,
        mixed $value,
        string $property
    ): void {

        $query->search($value);

    }
}
