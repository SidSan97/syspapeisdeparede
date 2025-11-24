<?php

namespace App\Models;

use App\Traits\HasTenantScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetWall extends Model
{
    use HasFactory, HasTenantScope;

    protected $fillable = [
        'budget_room_id',
        'tenant_id',
        'name',
        'position',
        'width',
        'height',
        'continue_same_art',
        'continuations',
        'collection_model_id',
        'total_area',
        'strip_height',
        'strip_count',
    ];

    protected $casts = [
        'position' => 'integer',
        'width' => 'decimal:2',
        'height' => 'decimal:2',
        'continue_same_art' => 'boolean',
        'continuations' => 'array',
        'collection_model_id' => 'integer',
        'total_area' => 'decimal:2',
        'strip_height' => 'decimal:2',
        'strip_count' => 'integer',
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(BudgetRoom::class, 'budget_room_id');
    }

    public function collectionModel(): BelongsTo
    {
        return $this->belongsTo(CollectionModel::class, 'collection_model_id');
    }
}
