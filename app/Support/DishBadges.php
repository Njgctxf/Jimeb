<?php

namespace App\Support;

/**
 * Badges affichés sur un plat côté client (ex. « Populaire », « Pimenté »).
 * Stockés dans menu_items.badges (tableau JSON de clés).
 */
final class DishBadges
{
    /** @var array<string, array{emoji: string, tone: string}> */
    private const BADGES = [
        'popular' => ['emoji' => '🔥', 'tone' => 'popular'],
        'spicy' => ['emoji' => '🌶️', 'tone' => 'spicy'],
        'new' => ['emoji' => '✨', 'tone' => 'new'],
        'chef' => ['emoji' => '👨‍🍳', 'tone' => 'chef'],
        'veggie' => ['emoji' => '🌿', 'tone' => 'veggie'],
        'share' => ['emoji' => '👥', 'tone' => 'share'],
    ];

    /** @return list<string> */
    public static function keys(): array
    {
        return array_keys(self::BADGES);
    }

    public static function emoji(string $key): string
    {
        return self::BADGES[$key]['emoji'] ?? '';
    }

    public static function langKey(string $key): string
    {
        return 'modules.menu.badge_' . $key;
    }

    /**
     * @param  array<int, string>|null  $stored
     * @return list<string>
     */
    public static function normalize(?array $stored): array
    {
        $stored = array_filter($stored ?? [], 'is_string');

        // conserve l'ordre défini ci-dessus
        return array_values(array_intersect(self::keys(), $stored));
    }

    /**
     * Données prêtes pour la page client.
     *
     * @param  array<int, string>|null  $stored
     * @return list<array{key: string, emoji: string, label: string, tone: string}>
     */
    public static function display(?array $stored): array
    {
        return array_map(fn (string $k) => [
            'key' => $k,
            'emoji' => self::emoji($k),
            'label' => __(self::langKey($k)),
            'tone' => self::BADGES[$k]['tone'],
        ], self::normalize($stored));
    }
}
