<?php

namespace App\Models;

use App\Traits\HasTenantScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MyFavoriteCollectionImage extends Model
{
    use HasFactory, HasTenantScope;

    protected $table = 'my_favorites_collection_images';

    protected $fillable = [
        'user_id',
        'tenant_id',
        'collection_image_id',
    ];

    /**
     * Get the user that owns the favorite.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the collection image that is favorited.
     */
    public function collectionImage(): BelongsTo
    {
        return $this->belongsTo(CollectionImage::class, 'collection_image_id');
    }
}

