<?php

namespace App\Models;

use BeyondCode\Comments\Contracts\Commentator;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements Commentator //implements MustVerifyEmail
{
    use HasApiTokens;
    use HasFactory;
    use Notifiable;
    use HasRoles;

    /**
     * @see https://spatie.be/docs/laravel-permission/v6/basic-usage/multiple-guards
     */
    protected string $guard_name = 'web';

    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'user_type_id',
        'is_dropshipping',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_dropshipping' => 'integer',
        ];
    }

    /**
     * Check if a comment for a specific model needs to be approved.
     * @param mixed $model
     * @return bool
     */
    public function needsCommentApproval($model): bool
    {
        return false; // Auto-approve comments
    }

    /**
     * Get the type user that owns the user.
     */
    public function userType()
    {
        return $this->belongsTo(TypeUser::class, 'user_type_id');
    }

    /**
     * Get the order budget cards that belong to this user.
     */
    public function orderBudgetCards(): BelongsToMany
    {
        return $this->belongsToMany(OrderBudget::class, 'layout_card_user', 'user_id', 'card_id')
            ->withTimestamps();
    }

    /**
     * Get the favorite collection images for this user.
     */
    public function favoriteCollectionImages(): BelongsToMany
    {
        return $this->belongsToMany(CollectionImage::class, 'my_favorites_collection_images', 'user_id', 'collection_image_id')
            ->withTimestamps();
    }
}
