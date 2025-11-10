<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CollectionModelFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'collection_model_id',
        'file_name',
        'file_path',
    ];
}

