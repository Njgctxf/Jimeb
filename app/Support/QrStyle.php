<?php

namespace App\Support;

use App\Models\Restaurant;

/**
 * Style des QR codes d'un restaurant (stocké dans restaurants.qr_settings, JSON).
 * Un seul style, défini sur le « QR général » puis appliqué à tous les QR du restaurant.
 */
final class QrStyle
{
    /** Forme des points. */
    public const SHAPES = [
        'square' => 'Carrés',
        'rounded' => 'Arrondis',
        'dots' => 'Ronds',
        'diamond' => 'Losanges',
    ];

    /** Cadre des trois coins. */
    public const EYE_FRAMES = [
        'square' => 'Carré',
        'rounded' => 'Arrondi',
    ];

    /** Centre (pupille) des trois coins. */
    public const EYE_PUPILS = [
        'square' => 'Carré',
        'rounded' => 'Arrondi',
        'circle' => 'Rond',
    ];

    /** Forme générale du QR code imprimé. */
    public const BADGES = [
        'none' => 'Carré simple',
        'rounded' => 'Carte arrondie',
        'circle' => 'Médaillon rond',
    ];

    /** Silhouette du code : les points hors de la forme restent présents mais très pâles (le code reste lisible). */
    public const SILHOUETTES = [
        'none' => 'Aucune',
        'circle' => 'Rond',
        'squircle' => 'Arrondi',
        'diamond' => 'Losange',
        'heart' => 'Cœur',
        'image' => 'Ma forme',
    ];

    public const GRADIENTS = [
        'none' => 'Une seule couleur',
        'h' => 'Dégradé horizontal',
        'v' => 'Dégradé vertical',
        'd' => 'Dégradé en diagonale',
    ];

    public const TEXTS = [
        'auto' => 'Nom de la table',
        'custom' => 'Texte personnalisé',
        'none' => 'Aucun texte',
    ];

    public const DEFAULTS = [
        'shape' => 'square',
        'dotScale' => 100,
        'eyeFrame' => 'square',
        'eyePupil' => 'square',
        'color' => '#000000',
        'color2' => null,
        'gradient' => 'none',
        'eyeColor' => null,
        'pupilColor' => null,
        'background' => '#ffffff',
        'badge' => 'none',
        'badgeColor' => null,
        'logo' => null,
        'logoSize' => 22,
        'silhouette' => 'none',
        'silhouetteImage' => null,
        'ghost' => 30,
        'art' => null,
        'artStrength' => 65,
        'text' => 'auto',
        'textCustom' => '',
        'version' => 0,
    ];

    /** Style complet (valeurs par défaut comblées, valeurs inconnues écartées). */
    public static function normalize(?array $raw): array
    {
        $raw = $raw ?? [];

        // Ancien format (une seule « forme des coins »)
        if (isset($raw['eye']) && ! isset($raw['eyeFrame'])) {
            $raw['eyeFrame'] = $raw['eye'] === 'square' ? 'square' : 'rounded';
            $raw['eyePupil'] = $raw['eye'] === 'circle' ? 'circle' : ($raw['eye'] === 'rounded' ? 'rounded' : 'square');
        }

        $pick = fn (string $key, array $allowed) => array_key_exists($raw[$key] ?? '', $allowed) ? $raw[$key] : self::DEFAULTS[$key];
        $int = fn (string $key, int $min, int $max) => max($min, min($max, (int) ($raw[$key] ?? self::DEFAULTS[$key])));
        $file = fn (string $key) => ! empty($raw[$key]) && is_file(self::filePath((string) $raw[$key])) ? basename((string) $raw[$key]) : null;

        $color = self::hex($raw['color'] ?? null) ?? self::DEFAULTS['color'];

        return [
            'shape' => $pick('shape', self::SHAPES),
            'dotScale' => $int('dotScale', 60, 100),
            'eyeFrame' => $pick('eyeFrame', self::EYE_FRAMES),
            'eyePupil' => $pick('eyePupil', self::EYE_PUPILS),
            'color' => $color,
            'color2' => self::hex($raw['color2'] ?? null),
            'gradient' => $pick('gradient', self::GRADIENTS),
            'eyeColor' => self::hex($raw['eyeColor'] ?? null),
            'pupilColor' => self::hex($raw['pupilColor'] ?? null),
            'background' => self::hex($raw['background'] ?? null) ?? self::DEFAULTS['background'],
            'badge' => $pick('badge', self::BADGES),
            'badgeColor' => self::hex($raw['badgeColor'] ?? null),
            'logo' => $file('logo'),
            'logoSize' => $int('logoSize', 14, 26),
            'silhouette' => $pick('silhouette', self::SILHOUETTES),
            'silhouetteImage' => $file('silhouetteImage'),
            'ghost' => $int('ghost', 26, 45),
            'art' => $file('art'),
            'artStrength' => $int('artStrength', 40, 85),
            'text' => $pick('text', self::TEXTS),
            'textCustom' => mb_substr(trim((string) ($raw['textCustom'] ?? '')), 0, 40),
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

    /** Fichiers (logo, image) : dossier public/user-uploads/qr-logos. */
    public static function filePath(string $filename): string
    {
        return public_path('user-uploads/qr-logos/' . basename($filename));
    }

    public static function logoPath(string $filename): string
    {
        return self::filePath($filename);
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

    /** Toutes les couleurs « encre » du style (points, dégradé, coins). */
    public static function inkColors(array $style): array
    {
        return array_values(array_unique(array_filter([
            $style['color'],
            $style['gradient'] !== 'none' ? $style['color2'] : null,
            $style['eyeColor'],
            $style['pupilColor'],
        ])));
    }

    /**
     * Message d'erreur si le style rend le QR illisible, sinon null.
     * L'encre doit être plus sombre que le fond (les scanners lisent mal l'inverse) et assez contrastée.
     */
    public static function problem(array $style): ?string
    {
        foreach (self::inkColors($style) as $ink) {
            if (self::luminance($ink) >= self::luminance($style['background'])) {
                return 'Les couleurs du QR code doivent être plus foncées que son fond, sinon il ne sera pas lisible.';
            }

            if (self::contrast($ink, $style['background']) < 3.5) {
                return 'Le contraste entre le QR code et son fond est trop faible : choisissez une couleur plus foncée ou un fond plus clair.';
            }
        }

        return null;
    }

    /** Compatibilité avec l'ancien écran. */
    public static function colorProblem(string $color, string $background): ?string
    {
        return self::problem(self::normalize(['color' => $color, 'background' => $background]));
    }

    /** Conseils (non bloquants) quand un réglage peut gêner la lecture. */
    public static function warnings(array $style): array
    {
        $w = [];

        if ($style['dotScale'] < 78) {
            $w[] = 'Des points très petits se lisent moins bien, surtout de loin ou sur un petit support.';
        }

        if ($style['logo'] && $style['logoSize'] > 22) {
            $w[] = 'Un logo grand cache une partie du code : testez bien avec plusieurs téléphones.';
        }

        if ($style['silhouette'] === 'image' && ! $style['silhouetteImage']) {
            $w[] = 'Importez une image pour dessiner votre forme (de préférence pleine, sur fond blanc ou transparent).';
        }

        if ($style['art']) {
            $w[] = 'Une image fusionnée rend le code plus délicat à lire : testez avec plusieurs téléphones avant d\'imprimer.';
        }

        if ($style['eyePupil'] === 'circle' && $style['eyeFrame'] === 'rounded') {
            $w[] = 'Des coins entièrement arrondis sont bien lus par les téléphones récents, un peu moins par les anciens.';
        }

        return $w;
    }
}
