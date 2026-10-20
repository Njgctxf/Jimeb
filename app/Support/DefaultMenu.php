<?php

namespace App\Support;

use App\Models\Menu;

/**
 * Menu unique : le restaurateur ne crée pas de « menu », seulement des catégories et des plats.
 * Un menu reste obligatoire en base, on utilise donc le premier (ou on le crée).
 */
class DefaultMenu
{
    public static function id(): int
    {
        $menu = Menu::orderBy('id')->first();

        if (! $menu) {
            $menu = new Menu();
            $menu->setTranslations('menu_name', ['en' => 'Menu', 'fr' => 'Menu']);
            $menu->save();
        }

        return (int) $menu->id;
    }
}
