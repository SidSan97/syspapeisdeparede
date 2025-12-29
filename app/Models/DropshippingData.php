<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DropshippingData extends Model
{
    use HasFactory;

    protected $table = 'dropshipping_data';

    protected $fillable = [
        'name',
        'person_type',
        'cpf_cnpj',
        'IE',
        'email',
        'phone',
        'cep',
        'uf',
        'state',
        'city',
        'neighborhood',
        'public_space',
        'number',
        'complement',
        'dealer_id',
        'budget_id',
        'order_id',
    ];

    protected $casts = [
        'dealer_id' => 'integer',
        'budget_id' => 'integer',
        'order_id' => 'integer',
    ];

    /**
     * Get the dealer (user) that owns the dropshipping data.
     */
    public function dealer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dealer_id');
    }

    /**
     * Get the budget that owns the dropshipping data.
     */
    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class, 'budget_id');
    }

    /**
     * Get the order that owns the dropshipping data.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}

