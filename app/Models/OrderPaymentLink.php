<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderPaymentLink extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'components',
        'payment_method',
        'installments',
        'amount_artes',
        'amount_produtos',
        'amount_frete',
        'amount_total',
        'external_payment_link_id',
        'external_order_id',
        'payment_url',
        'status',
        'expires_at',
        'paid_at',
        'provider_payload',
    ];

    protected $casts = [
        'components' => 'array',
        'installments' => 'integer',
        'amount_artes' => 'decimal:2',
        'amount_produtos' => 'decimal:2',
        'amount_frete' => 'decimal:2',
        'amount_total' => 'decimal:2',
        'expires_at' => 'datetime',
        'paid_at' => 'datetime',
        'provider_payload' => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}

