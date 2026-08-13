<?php

namespace App\Models;

use App\Traits\HasOrderScopes;
use App\Traits\HasTenantScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    use HasFactory, HasTenantScope, HasOrderScopes;

    protected $fillable = [
        'user_id',
        'tenant_id',
        'name',
        'total_area',
        'total_amount',
        'total_amount_installments',
        'total_amount_markup',
        'total_amount_installments_markup',
        'delivery_time',
        'payment_method',
        'installments',
        'cep',
        'selected_carrier_name',
        'selected_carrier_price',
        'selected_carrier_delivery_time',
        'carriers_snapshot',
        'primary_budget_room_id',
        'status',
        'payment_file',
        'dropshipping_budget',
        'link_payment',
        'payment_expiration_date',
        'paid',
        'payment_status',
        'nf_sent',
        'nf_id',
        'flags',
        'observation',
    ];

    protected $casts = [
        'total_area' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'total_amount_installments' => 'decimal:2',
        'total_amount_markup' => 'decimal:2',
        'total_amount_installments_markup' => 'decimal:2',
        'delivery_time' => 'integer',
        'installments' => 'integer',
        'selected_carrier_price' => 'decimal:2',
        'selected_carrier_delivery_time' => 'integer',
        'carriers_snapshot' => 'array',
        'primary_budget_room_id' => 'integer',
        'status' => 'string',
        'flags' => 'string',
        'dropshipping_budget' => 'integer',
        'paid' => 'integer:0,1',
        'payment_status' => 'string',
        'nf_sent' => 'integer:0,1',
        'nf_id' => 'string',
        'link_payment' => 'string',
        'payment_expiration_date' => 'string',
        'observation' => 'string',
    ];

    /**
     * Get the user that owns the order.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the tenant (user) that owns the order.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tenant_id');
    }

    /**
     * Get the primary budget room for this order.
     */
    public function primaryRoom(): BelongsTo
    {
        return $this->belongsTo(BudgetRoom::class, 'primary_budget_room_id');
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(BudgetRoom::class)->orderBy('position');
    }

    public function dropshippingData(): HasOne
    {
        return $this->hasOne(DropshippingData::class);
    }

    public function orderBudgets(): HasMany
    {
        return $this->hasMany(OrderBudget::class);
    }

    public function paymentLinks(): HasMany
    {
        return $this->hasMany(OrderPaymentLink::class);
    }

    public function changeHistories(): HasMany
    {
        return $this->hasMany(OrderChangeHistory::class)->latest();
    }
}

