<?php

namespace App\Models;

use BeyondCode\Comments\Traits\HasComments;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderBudget extends Model
{
    use HasFactory, HasComments;

    protected $fillable = [
        'budget_id',
        'budget_wall_id',
        'description',
        'status',
        'layout_column_names_id',
    ];

    protected $casts = [
        'description' => 'string',
        'status' => 'string',
        'layout_column_names_id' => 'integer',
        'budget_wall_id' => 'integer',
    ];

    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }

    public function wall(): BelongsTo
    {
        return $this->belongsTo(BudgetWall::class, 'budget_wall_id');
    }

    public function layoutColumnName(): BelongsTo
    {
        return $this->belongsTo(LayoutColumnName::class, 'layout_column_names_id');
    }
}
