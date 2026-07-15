<?php

namespace App\Models;

use App\Traits\HasTenantScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequestLayoutArt extends Model
{
    use HasFactory, HasTenantScope;

    protected $table = 'request_layouts_art';

    protected $fillable = [
        'dealer_id',
        'designer_id',
        'order_id',
        'order_budget_id',
        'tenant_id',
        'interactions_card_id',
        'comment',
        'path_file',
        'approval_status',
    ];

    protected $casts = [
        'dealer_id' => 'integer',
        'designer_id' => 'integer',
        'order_id' => 'integer',
        'order_budget_id' => 'integer',
        'interactions_card_id' => 'integer',
        'path_file' => 'string',
        'comment' => 'string',
        'approval_status' => 'string',
    ];

    /**
     * Get the dealer (revendedor) that owns the request.
     */
    public function dealer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dealer_id');
    }

    /**
     * Get the designer that owns the request.
     */
    public function designer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'designer_id');
    }

    /**
     * Get the order that owns the request.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /**
     * Get the order budget that owns the request.
     */
    public function orderBudget(): BelongsTo
    {
        return $this->belongsTo(OrderBudget::class, 'order_budget_id');
    }

    /**
     * Get the interaction that owns this request layout art.
     */
    public function interaction(): BelongsTo
    {
        return $this->belongsTo(RequestLayoutArtInteraction::class, 'interactions_card_id');
    }
}

