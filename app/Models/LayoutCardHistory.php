<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LayoutCardHistory extends Model
{
    use HasFactory;

    protected $table = 'layout_card_history';

    protected $fillable = [
        'card_id',
        'description',
    ];

    protected $casts = [
        'description' => 'string',
        'card_id' => 'integer',
    ];

    /**
     * Get the order budget card that owns this history entry.
     */
    public function orderBudget(): BelongsTo
    {
        return $this->belongsTo(OrderBudget::class, 'card_id');
    }
}

