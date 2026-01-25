<?php

namespace App\Models;

use App\Traits\HasTenantScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
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
        'dropshipping_budget',
        'link_payment',
        'payment_expiration_date',
        'paid',
        'nf_sent',
        'nf_id',
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
        'dropshipping_budget' => 'integer',
        'paid' => 'integer:0,1',
        'nf_sent' => 'integer:0,1',
        'nf_id' => 'string',
        'link_payment' => 'string',
        'payment_expiration_date' => 'string',
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

    /**
     * Scope para filtrar pedidos por permissões do usuário
     * Aplica filtro apenas se o usuário não for admin ou comercial
     */
    public function scopeForUser($query, $user)
    {
        if (!$user->isAdmin() && !$user->isCommercial()) {
            return $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhere('tenant_id', $user->id);
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
            $q->where('name', 'like', "%{$search}%")
                ->orWhere('id', 'like', "%{$search}%");
        });
    }

    /**
     * Scope para filtrar por status
     */
    public function scopeByStatus($query, ?string $status)
    {
        if (!empty($status) && $status !== 'all') {
            return $query->where('status', $status);
        }

        return $query;
    }

    /**
     * Scope para filtrar por período (data de criação)
     */
    public function scopeByDateRange($query, ?string $dateFrom = null, ?string $dateTo = null)
    {
        if (!empty($dateFrom)) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if (!empty($dateTo)) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        return $query;
    }

    /**
     * Scope para filtrar por usuário/revendedor
     */
    public function scopeByUserId($query, ?int $userId)
    {
        if (!empty($userId)) {
            return $query->where('user_id', $userId);
        }

        return $query;
    }
}

