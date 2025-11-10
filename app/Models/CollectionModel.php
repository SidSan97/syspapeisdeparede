<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CollectionModel extends Model
{
    use HasFactory;

    protected $fillable = [
        'value',
        'deadline',
        'request_link',
        'request_comment',
        'request_file',
    ];

    protected $casts = [
        'value' => 'float',
        'deadline' => 'integer',
        'request_link' => 'boolean',
        'request_comment' => 'boolean',
        'request_file' => 'boolean',
    ];

    public function files(): HasMany
    {
        return $this->hasMany(CollectionModelFile::class);
    }
}

