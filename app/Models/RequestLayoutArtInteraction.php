<?php

namespace App\Models;

use App\Traits\HasTenantScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RequestLayoutArtInteraction extends Model
{
    use HasFactory, HasTenantScope;

    protected $table = 'request_layouts_art_interactions';

    protected $fillable = [
        'card_id',
        'tenant_id',
    ];

    protected $casts = [
        'card_id' => 'integer',
    ];

    /**
     * Get the order budget (card) that owns this interaction.
     */
    public function card(): BelongsTo
    {
        return $this->belongsTo(OrderBudget::class, 'card_id');
    }

    /**
     * Get the request layout arts for this interaction.
     */
    public function requestLayoutArts(): HasMany
    {
        return $this->hasMany(RequestLayoutArt::class, 'interactions_card_id');
    }
}

