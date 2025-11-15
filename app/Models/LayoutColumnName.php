<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LayoutColumnName extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
    ];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'layout_column_names';

    /**
     * Get the order budgets for this layout column name.
     */
    public function orderBudgets(): HasMany
    {
        return $this->hasMany(OrderBudget::class, 'layout_column_names_id');
    }
}

