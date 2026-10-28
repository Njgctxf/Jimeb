<?php

namespace App\Models;

use App\Traits\HasRestaurant;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    use HasRestaurant;

    protected $fillable = [
        'name',
        'guard_name',
        'restaurant_id',
        'display_name'
    ];

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }

    /**
     * Get the translated display name for the role
     */
    public function getTranslatedNameAttribute()
    {
        return self::translate($this->display_name);
    }

    /**
     * Traduit un nom de rôle par défaut (Admin, Waiter…) ; un rôle renommé par le restaurant reste tel quel.
     * Utilisable aussi sur les rôles Spatie (relation $user->roles), qui n'ont pas l'accesseur.
     */
    public static function translate(?string $displayName): string
    {
        if (!$displayName) {
            return '';
        }

        // Try to get translation from modules.staff namespace first
        $translationKey = 'modules.staff.' . $displayName;
        $translated = __($translationKey);

        // If translation key returns itself, it means no translation exists
        return $translated === $translationKey ? $displayName : $translated;
    }
}
