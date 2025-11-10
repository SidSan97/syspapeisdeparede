<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CollectionModel extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'value',
        'deadline',
        'type_model_id',
        'request_link',
        'request_comment',
        'request_file',
    ];

    protected $casts = [
        'value' => 'float',
        'deadline' => 'integer',
        'type_model_id' => 'integer',
        'request_link' => 'boolean',
        'request_comment' => 'boolean',
        'request_file' => 'boolean',
    ];

    public function files(): HasMany
    {
        return $this->hasMany(CollectionModelFile::class);
    }

    public function modelType(): BelongsTo
    {
        return $this->belongsTo(ModelType::class, 'type_model_id');
    }
}

