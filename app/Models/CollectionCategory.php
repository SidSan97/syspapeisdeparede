<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CollectionCategory extends Model
{
    use HasFactory;

    protected $table = 'collection_categories';

    protected $fillable = [
        'name',
        'image_cover',
        'parent_id',
    ];

    public function children(): HasMany
    {
        return $this->hasMany(CollectionCategory::class, 'parent_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(CollectionCategory::class, 'parent_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(CollectionImage::class, 'collection_category_id');
    }

    public function descendants(): HasMany
    {
        return $this->children()->with('descendants');
    }

    public function isRoot(): bool
    {
        return $this->parent_id === null;
    }

    public function getRoot(): self
    {
        $category = $this;
        while ($category->parent) {
            $category = $category->parent;
        }
        return $category;
    }

    public function getDepth(): int
    {
        $depth = 0;
        $category = $this;
        while ($category->parent) {
            $depth++;
            $category = $category->parent;
        }
        return $depth;
    }
}

