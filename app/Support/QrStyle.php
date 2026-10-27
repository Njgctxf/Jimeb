<?php

namespace App\Support;

use App\Models\Restaurant;

/**
 * Style des QR codes d'un restaurant : forme des modules, forme des « yeux », couleurs et logo central.
 * Stocké dans restaurants.qr_settings (JSON).
 */
final class QrStyle
{
    public const SHAPES = [
        'square' => 'Carrés',
        'rounded' => 'Arrondis',
        'dots' => 'Points',
        'diamond' => 'Losanges',
    ];

    public const EYES = [
        'square' => 'Carrés',
        'rounded' => 'Arrondis',
        'circle' => 'Ronds',
    ];

    public const DEFAULTS = [
        'shape' => 'square',
        'eye' => 'square',
        'color' => '#000000',
        'background' => '#ffffff',
        'logo' => null,
        'version' => 0,
    ];

    /** Style complet (valeurs par défaut comblées) à partir de données brutes. */
    public static function normalize(?array $raw): array
    {
        $raw = $raw ?? [];

        return [
            'shape' => array_key_exists($raw['shape'] ?? '', self::SHAPES) ? $raw['shape'] : self::DEFAULTS['shape'],
            'eye' => array_key_exists($raw['eye'] ?? '', self::EYES) ? $raw['eye'] : self::DEFAULTS['eye'],
            'color' => self::hex($raw['color'] ?? null) ?? self::DEFAULTS['color'],
            'background' => self::hex($raw['background'] ?? null) ?? self::DEFAULTS['background'],
            'logo' => ! empty($raw['logo']) && is_file(self::logoPath((string) $raw['logo'])) ? (string) $raw['logo'] : null,
            'version' => (int) ($raw['version'] ?? 0),
        ];
    }

    public static function forRestaurant(int|Restaurant|null $restaurant): array
    {
        if (! $restaurant instanceof Restaurant && $restaurant !== null) {
            $restaurant = Restaurant::withoutGlobalScopes()->find($restaurant);
        }

        return self::normalize($restaurant?->qr_settings);
    }

    public static function logoPath(string $filename): string
    {
        return public_path('user-uploads/qr-logos/' . basename($filename));
    }

    public static function hex(?string $value): ?string
    {
        $value = strtolower(trim((string) $value));

        return preg_match('/^#[0-9a-f]{6}$/', $value) ? $value : null;
    }

    /** @return array{0:int,1:int,2:int} */
    public static function rgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

        return [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
    }

    public static function luminance(string $hex): float
    {
        [$r, $g, $b] = array_map(function ($c) {
            $c /= 255;

            return $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        }, self::rgb($hex));

        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }

    /** Rapport de contraste (1 à 21) entre deux couleurs. */
    public static function contrast(string $a, string $b): float
    {
        $la = self::luminance($a);
        $lb = self::luminance($b);

        return (max($la, $lb) + 0.05) / (min($la, $lb) + 0.05);
    }

    /**
     * Message d'erreur si le couple de couleurs rend le QR illisible, sinon null.
     * Le QR doit être plus sombre que son fond (les scanners lisent mal l'inverse) et assez contrasté.
     */
    public static function colorProblem(string $color, string $background): ?string
    {
        if (self::luminance($color) >= self::luminance($background)) {
            return 'La couleur du QR code doit être plus foncée que son fond, sinon il ne sera pas lisible.';
        }

        if (self::contrast($color, $background) < 3.5) {
            return 'Le contraste entre le QR code et son fond est trop faible : choisissez une couleur plus foncée ou un fond plus clair.';
        }

        return null;
    }
}
