<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BudgetRoom extends Model
{
    use HasFactory;

    protected $fillable = [
        'budget_id',
        'name',
        'position',
        'raw_payload',
    ];

    protected $casts = [
        'position' => 'integer',
        'raw_payload' => 'array',
    ];

    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }

    public function walls(): HasMany
    {
        return $this->hasMany(BudgetWall::class)->orderBy('position');
    }
}
