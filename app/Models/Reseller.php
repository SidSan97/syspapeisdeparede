<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Reseller extends Model
{
    use HasFactory;

    protected $fillable = [
        'tiny_id',
        'tiny_code',
        'name',
        'fantasy_name',
        'cnpj',
        'site',
        'ie',
        'person_type',
        'email',
        'phone',
        'cep',
        'uf',
        'city',
        'neighborhood',
        'public_space',
        'number',
        'complement',
        'status',
        'last_sync_at',
    ];

    protected function casts(): array
    {
        return [
            'tiny_id' => 'integer',
            'last_sync_at' => 'datetime',
        ];
    }

    /**
     * Get the user account associated with this reseller.
     */
    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }
}
