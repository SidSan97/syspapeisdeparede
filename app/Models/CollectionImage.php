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
        'collection_category_id',
        'name',
        'path_name',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(CollectionCategory::class, 'collection_category_id');
    }

    public function favoritedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'my_favorites_collection_images', 'collection_image_id', 'user_id')
            ->withTimestamps();
    }
}

