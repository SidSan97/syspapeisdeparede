<?php

namespace App\Models;

use App\Traits\HasTenantScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderBudgetActivitySession extends Model
{
    use HasFactory, HasTenantScope;

    protected $fillable = [
        'order_budget_id',
        'tenant_id',
        'started_at',
        'ended_at',
        'duration_seconds',
    ];

    protected function casts(): array
    {
        return [
            'order_budget_id' => 'integer',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'duration_seconds' => 'integer',
        ];
    }

    public function orderBudget(): BelongsTo
    {
        return $this->belongsTo(OrderBudget::class);
    }
}
