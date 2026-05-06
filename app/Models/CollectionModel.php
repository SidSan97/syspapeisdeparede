<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class CollectionModel extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'value',
        'deadline',
        'request_link',
        'request_comment',
        'request_file',
        'request_collection',
        'request_layout',
        'request_art_on_payment',
    ];

    protected $casts = [
        'name' => 'string',
        'value' => 'float',
        'deadline' => 'integer',
        'request_link' => 'boolean',
        'request_comment' => 'boolean',
        'request_file' => 'boolean',
        'request_collection' => 'boolean',
        'request_layout' => 'boolean',
        'request_art_on_payment' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($model) {
            foreach ($model->files as $file) {
                if ($file->file_path && Storage::disk('public')->exists($file->file_path)) {
                    Storage::disk('public')->delete($file->file_path);
                }

                $file->delete();
            }
        });
    }

    public function files(): HasMany
    {
        return $this->hasMany(CollectionModelFile::class);
    }
}
