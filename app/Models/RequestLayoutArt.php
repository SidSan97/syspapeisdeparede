<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequestLayoutArt extends Model
{
    use HasFactory;

    protected $table = 'request_layouts_art';

    protected $fillable = [
        'dealer_id',
        'designer_id',
        'budget_id',
        'order_budget_id',
        'comment',
        'path_file',
    ];

    protected $casts = [
        'dealer_id' => 'integer',
        'designer_id' => 'integer',
        'budget_id' => 'integer',
        'order_budget_id' => 'integer',
        'path_file' => 'string',
        'comment' => 'string',
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
     * Get the budget that owns the request.
     */
    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class, 'budget_id');
    }

    /**
     * Get the order budget that owns the request.
     */
    public function orderBudget(): BelongsTo
    {
        return $this->belongsTo(OrderBudget::class, 'order_budget_id');
    }
}

