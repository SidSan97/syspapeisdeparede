<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_budget_id',
        'user_id',
        'action_type',
        'column_name',
        'action_date',
        'layout_summary',
        'model_id',
        'model_name',
        'card_description',
        'additional_data',
    ];

    protected $casts = [
        'action_date' => 'datetime',
        'layout_summary' => 'array',
        'additional_data' => 'array',
    ];

    /**
     * Get the order budget that this report belongs to.
     */
    public function orderBudget(): BelongsTo
    {
        return $this->belongsTo(OrderBudget::class);
    }

    /**
     * Get the user who performed the action.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

