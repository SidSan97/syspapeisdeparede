<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CollectionArt extends Model
{
    use HasFactory;

    protected $table = 'collection_arts';

    protected $fillable = [
        'name',
    ];
}

