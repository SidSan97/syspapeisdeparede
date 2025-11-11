<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\CollectionImage;

class CollectionArt extends Model
{
    use HasFactory;

    protected $table = 'collection_arts';

    protected $fillable = [
        'name',
    ];

    public function images(): HasMany
    {
        return $this->hasMany(CollectionImage::class, 'collection_arts_id');
    }
}

