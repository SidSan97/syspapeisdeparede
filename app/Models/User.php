<?php

namespace App\Models;

use App\Traits\HasUserScopes;
use BeyondCode\Comments\Contracts\Commentator;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements Commentator //implements MustVerifyEmail
{
    use HasApiTokens;
    use HasFactory;
    use HasUserScopes;
    use Notifiable;
    use HasRoles;

    protected static function booted(): void
    {
        static::created(function (User $user) {
            UserWallet::firstOrCreate(
                ['user_id' => $user->id],
                ['balance' => 0]
            );
        });
    }

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

    public function getAvatarUrlAttribute(): string
    {
        return $this->avatar
            ? Storage::url($this->avatar)
            : asset('images/avatar.svg');
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

    public function dropshippingData(): HasMany
    {
        return $this->hasMany(DropshippingData::class, 'dealer_id');
    }

    /**
     * Get the orders that belong to this user.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function wallet(): HasOne
    {
        return $this->hasOne(UserWallet::class);
    }

    /**
     * Get the orders where this user is the tenant.
     */
    public function tenantOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'tenant_id');
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(['admin', 'super admin']);
    }

    public function isReseller(): bool
    {
        return $this->hasRole('reseller');
    }

    public function isDesigner(): bool
    {
        return $this->hasRole('designer');
    }

    public function isProduction(): bool
    {
        return $this->hasRole('production');
    }

    public function isCommercial(): bool
    {
        return $this->hasRole('commercial');
    }

    public function isExpedition(): bool
    {
        return $this->hasRole('expedition');
    }

    public function isRepresentatives(): bool
    {
        return $this->hasRole('representatives');
    }

    public function isArchitects(): bool
    {
        return $this->hasRole('architects');
    }

    public function isTenant(): bool
    {
        return $this->hasRole('reseller');
    }
}
