<?php

namespace App\Models;

use App\Traits\HasTenantScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Budget extends Model
{
    use HasFactory, HasTenantScope;

    protected $fillable = [
        'user_id',
        'tenant_id',
        'name',
        'total_area',
        'total_amount',
        'total_amount_installments',
        'delivery_time',
        'payment_method',
        'installment_limit',
        'installments',
        'cep',
        'selected_carrier_name',
        'selected_carrier_price',
        'selected_carrier_delivery_time',
        'carriers_snapshot',
        'primary_budget_room_id',
        'status',
        'payment_file',
        'comment_referring_model',
        'link_referring_model',
        'files_referring_model',
        'collection_referring_model',
    ];

    protected $casts = [
        'total_area' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'total_amount_installments' => 'decimal:2',
        'delivery_time' => 'integer',
        'installment_limit' => 'integer',
        'installments' => 'integer',
        'selected_carrier_price' => 'decimal:2',
        'selected_carrier_delivery_time' => 'integer',
        'carriers_snapshot' => 'array',
        'primary_budget_room_id' => 'integer',
        'status' => 'string',
        'files_referring_model' => 'array',
        'collection_referring_model' => 'string',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(BudgetRoom::class)->orderBy('position');
    }

    public function primaryRoom(): BelongsTo
    {
        return $this->belongsTo(BudgetRoom::class, 'primary_budget_room_id');
    }

    public function orderBudgets(): HasMany
    {
        return $this->hasMany(OrderBudget::class);
    }
}
