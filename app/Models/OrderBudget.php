<?php

namespace App\Models;

use App\Traits\HasTenantScope;
use BeyondCode\Comments\Traits\HasComments;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class OrderBudget extends Model
{
    use HasFactory, HasComments, HasTenantScope;

    protected $fillable = [
        'budget_id',
        'tenant_id',
        'budget_wall_id',
        'description',
        'status',
        'layout_column_names_id',
        'production_column_names_id',
    ];

    protected $casts = [
        'description' => 'string',
        'status' => 'string',
        'layout_column_names_id' => 'integer',
        'budget_wall_id' => 'integer',
        'production_column_names_id' => 'integer',
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

    public function productionColumnName(): BelongsTo
    {
        return $this->belongsTo(ProductionColumnName::class, 'production_column_names_id');
    }

    /**
     * Get the users that belong to this order budget card.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'layout_card_user', 'card_id', 'user_id')
            ->withTimestamps();
    }

    /**
     * Get the history entries for this order budget card.
     */
    public function history()
    {
        return $this->hasMany(LayoutCardHistory::class, 'card_id');
    }
}
