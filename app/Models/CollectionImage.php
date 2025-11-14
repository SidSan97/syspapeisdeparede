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
        'name',
        'path_name',
    ];

    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(CollectionArtSubcategory::class, 'collection_arts_id');
    }
}

