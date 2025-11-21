<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CollectionImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'collection_arts_id',
        'name',
        'path_name',
    ];

    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(CollectionArtSubcategory::class, 'collection_arts_id');
    }

    /**
     * Get the users who favorited this collection image.
     */
    public function favoritedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'my_favorites_collection_images', 'collection_image_id', 'user_id')
            ->withTimestamps();
    }
}

