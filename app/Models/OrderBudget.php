<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderBudget extends Model
{
    use HasFactory;

    protected $fillable = [
        'budget_id',
        'status',
        'layout_column_names_id',
    ];

    protected $casts = [
        'status' => 'string',
        'layout_column_names_id' => 'integer',
    ];

    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }

    public function layoutColumnName(): BelongsTo
    {
        return $this->belongsTo(LayoutColumnName::class, 'layout_column_names_id');
    }
}
