<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CollectionImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'collection_arts_id',
        'path_name',
    ];

    public function collectionArt(): BelongsTo
    {
        return $this->belongsTo(CollectionArt::class, 'collection_arts_id');
    }
}

