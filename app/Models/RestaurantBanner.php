<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Bannière affichée sur la page d'accueil client (grande bannière ou bannière du milieu).
 */
class RestaurantBanner extends Model
{
    public const POSITION_HERO = 'hero';
    public const POSITION_MIDDLE = 'middle';

    public const MAX_HERO = 5;
    public const MAX_MIDDLE = 1;

    public const TARGETS = ['menu', 'promo', 'call', 'cart', 'category', 'dish'];

    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? asset_url_local_s3($this->image) : null;
    }

    public function scopeForRestaurant($query, int $restaurantId)
    {
        return $query->where('restaurant_id', $restaurantId);
    }
}
