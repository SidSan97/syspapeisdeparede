<?php

namespace App\Models;

use App\Traits\HasTenantScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LayoutCardHistory extends Model
{
    use HasFactory, HasTenantScope;

    protected $table = 'layout_card_history';

    protected $fillable = [
        'card_id',
        'tenant_id',
        'description',
        'type_page',
    ];

    protected $casts = [
        'description' => 'string',
        'card_id' => 'integer',
        'type_page' => 'string',
    ];

    /**
     * Get the order budget card that owns this history entry.
     */
    public function orderBudget(): BelongsTo
    {
        return $this->belongsTo(OrderBudget::class, 'card_id');
    }
}

