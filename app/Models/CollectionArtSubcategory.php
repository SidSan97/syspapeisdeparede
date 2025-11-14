<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CollectionArtSubcategory extends Model
{
    use HasFactory;

    protected $table = 'collection_arts_subcategories';

    protected $fillable = [
        'name',
        'collection_art_id',
    ];

    public function collectionArt(): BelongsTo
    {
        return $this->belongsTo(CollectionArt::class, 'collection_art_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(CollectionImage::class, 'collection_arts_id');
    }
}

