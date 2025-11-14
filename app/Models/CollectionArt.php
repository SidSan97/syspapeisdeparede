<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\CollectionArtSubcategory;

class CollectionArt extends Model
{
    use HasFactory;

    protected $table = 'collection_arts';

    protected $fillable = [
        'name',
    ];

    public function subcategories(): HasMany
    {
        return $this->hasMany(CollectionArtSubcategory::class, 'collection_art_id');
    }
}

