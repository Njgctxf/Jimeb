<?php

namespace App\Support;

use App\Models\ItemCategory;

/**
 * Section « Général » : utilisée quand le formulaire de plat ne demande plus de section.
 * Une section reste obligatoire en base, on en fournit donc une par défaut.
 */
class DefaultSection
{
    public static function id(): int
    {
        $existing = ItemCategory::all()->first(function (ItemCategory $category) {
            $names = $category->getTranslations('category_name');

            return in_array(strtolower(trim($names['en'] ?? $names['fr'] ?? '')), ['general', 'général'], true);
        });

        if ($existing) {
            return (int) $existing->id;
        }

        $category = new ItemCategory();
        $category->setTranslations('category_name', ['en' => 'General', 'fr' => 'Général']);
        $category->save();

        return (int) $category->id;
    }
}