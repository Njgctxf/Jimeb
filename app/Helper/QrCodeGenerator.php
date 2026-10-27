<?php

namespace App\Helper;

use App\Support\QrStyle;
use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder;
use RuntimeException;

/**
 * Génère l'image PNG d'un QR code personnalisé : forme des points, coins (cadre + pupille, couleurs à part),
 * dégradé, logo central ou image fusionnée avec le code, texte et forme générale (carte, médaillon).
 *
 * Tout est dessiné avec GD en haute résolution puis réduit, pour des bords lisses. Le code reste toujours complet
 * (aucun point n'est supprimé) : la lisibilité ne dépend que du contraste et de la taille des éléments.
 */
class QrCodeGenerator
{
    /** Taille finale d'un module, en pixels. */
    private const MODULE_PX = 12;

    /** Facteur de sur-échantillonnage (lissage). */
    private const SS = 3;

    /** Zone calme (en modules) autour du code. */
    private const QUIET = 4;

    private const LABEL_FONT_SIZE = 20;

    private const LABEL_PADDING = 12;

    /** Rayons des coins arrondis, en modules (réglés pour rester lisibles par les lecteurs stricts). */
    public static float $radiusFrame = 0.85;

    public static float $radiusHole = 0.35;

    public static float $radiusPupil = 0.7;


    public static function savePng(string $data, string $filePath, ?string $label = null, ?array $style = null): void
    {
        $png = self::renderPng($data, $label, $style);

        if (file_put_contents($filePath, $png) === false) {
            throw new RuntimeException("Failed to write QR code to {$filePath}");
        }
    }

    /** Image PNG (binaire) du QR code. */
    public static function renderPng(string $data, ?string $label = null, ?array $style = null): string
    {
        $style = QrStyle::normalize($style);

        $qr = Encoder::encode($data, ErrorCorrectionLevel::H(), 'UTF-8');
        $matrix = $qr->getMatrix();
        $n = $matrix->getWidth();

        $m = self::MODULE_PX * self::SS;           // module, en pixels « haute résolution »
        $q = self::QUIET;
        $size = ($n + 2 * $q) * $m;

        $im = imagecreatetruecolor($size, $size);
        imagealphablending($im, true);
        imageantialias($im, true);

        $bg = self::alloc($im, $style['background']);
        imagefilledrectangle($im, 0, 0, $size, $size, $bg);

        $art = $style['art'] !== null;

        // Image fusionnée avec le code
        if ($art) {
            self::drawArt($im, QrStyle::filePath($style['art']), $q * $m, $n * $m, $style['artStrength']);
        }

        // Logo central (inutile quand une image est fusionnée)
        $logoBox = null;
        if ($style['logo'] && ! $art) {
            $side = max(3, (int) round($n * $style['logoSize'] / 100));
            if (($n - $side) % 2 !== 0) {
                $side++;
            }
            $start = intdiv($n - $side, 2);
            $logoBox = [$start, $start, $start + $side - 1, $start + $side - 1];
        }

        $inEye = static fn (int $x, int $y): bool => ($x < 8 && $y < 8) || ($x >= $n - 8 && $y < 8) || ($x < 8 && $y >= $n - 8);

        // Silhouette : quels modules sont à l'intérieur de la forme choisie ?
        $inShape = self::silhouetteGrid($style, $n);

        // Modules de données
        for ($y = 0; $y < $n; $y++) {
            for ($x = 0; $x < $n; $x++) {
                if ($inEye($x, $y)) {
                    continue;
                }

                $dark = $matrix->get($x, $y) === 1;
                $px = ($x + $q) * $m;
                $py = ($y + $q) * $m;

                if ($art) {
                    self::artModule($im, $style, $dark, $px, $py, $m, ($x + 0.5) / $n, ($y + 0.5) / $n);

                    continue;
                }

                if (! $dark) {
                    continue;
                }

                if ($logoBox && $x >= $logoBox[0] - 1 && $x <= $logoBox[2] + 1 && $y >= $logoBox[1] - 1 && $y <= $logoBox[3] + 1) {
                    continue;
                }

                $nx = ($x + 0.5) / $n;
                $ny = ($y + 0.5) / $n;
                $color = self::ink($im, $style, $nx, $ny);

                // Hors de la forme : le point reste là, mais très pâle (le code reste complet donc lisible)
                if ($inShape !== null && ! $inShape[$y][$x]) {
                    $color = self::ghostColor($im, $style, $nx, $ny);
                }

                self::module($im, $color, $style['shape'], $style['dotScale'], $px, $py, $m);
            }
        }

        // Les trois « yeux » (repères)
        foreach ([[0, 0], [$n - 7, 0], [0, $n - 7]] as [$ex, $ey]) {
            self::eye($im, $style, ($ex + $q) * $m, ($ey + $q) * $m, $m, ($ex + 3.5) / $n, ($ey + 3.5) / $n, $art);
        }

        // Logo central
        if ($logoBox && $style['logo']) {
            self::drawLogo($im, $bg, QrStyle::filePath($style['logo']), $logoBox, $q, $m);
        }

        // Réduction : bords lisses
        $out = self::MODULE_PX * ($n + 2 * $q);
        $final = imagecreatetruecolor($out, $out);
        imagecopyresampled($final, $im, 0, 0, 0, 0, $out, $out, $size, $size);
        imagedestroy($im);

        // Texte sous le code
        $text = match ($style['text']) {
            'none' => null,
            'custom' => $style['textCustom'] !== '' ? $style['textCustom'] : null,
            default => $label,
        };

        if ($text !== null && $text !== '') {
            $final = self::appendLabel($final, $text, $style);
        }

        // Forme générale (carte arrondie, médaillon)
        if ($style['badge'] !== 'none') {
            $final = self::applyBadge($final, $style);
        }

        ob_start();
        imagesavealpha($final, true);
        imagepng($final);
        $png = (string) ob_get_clean();
        imagedestroy($final);

        return $png;
    }

    private static function alloc($im, string $hex): int
    {
        [$r, $g, $b] = QrStyle::rgb($hex);

        return imagecolorallocate($im, $r, $g, $b);
    }

    /** Couleur d'encre d'un point, selon sa position (0..1) quand un dégradé est choisi. */
    private static function ink($im, array $style, float $nx, float $ny): int
    {
        if ($style['gradient'] === 'none' || ! $style['color2']) {
            return self::alloc($im, $style['color']);
        }

        $t = match ($style['gradient']) {
            'h' => $nx,
            'v' => $ny,
            default => ($nx + $ny) / 2,
        };
        $t = max(0.0, min(1.0, $t));

        [$r1, $g1, $b1] = QrStyle::rgb($style['color']);
        [$r2, $g2, $b2] = QrStyle::rgb($style['color2']);

        return imagecolorallocate(
            $im,
            (int) round($r1 + ($r2 - $r1) * $t),
            (int) round($g1 + ($g2 - $g1) * $t),
            (int) round($b1 + ($b2 - $b1) * $t),
        );
    }

    /** @return array<int, array<int, bool>>|null  [ligne][colonne] => à l'intérieur de la forme ; null = pas de silhouette */
    private static function silhouetteGrid(array $style, int $n): ?array
    {
        $kind = $style['silhouette'];

        if ($kind === 'none' || ($kind === 'image' && ! $style['silhouetteImage'])) {
            return null;
        }

        $grid = [];

        if ($kind === 'image') {
            $bytes = @file_get_contents(QrStyle::filePath($style['silhouetteImage']));
            $src = $bytes ? @imagecreatefromstring($bytes) : false;

            if ($src === false) {
                return null;
            }

            // Échantillon n×n de l'image, posée en entier au centre sur fond blanc
            $sample = imagecreatetruecolor($n, $n);
            imagefill($sample, 0, 0, imagecolorallocate($sample, 255, 255, 255));
            imagealphablending($sample, true);
            $w = imagesx($src);
            $h = imagesy($src);
            $scale = min($n / $w, $n / $h);
            $tw = max(1, (int) round($w * $scale));
            $th = max(1, (int) round($h * $scale));
            imagecopyresampled($sample, $src, intdiv($n - $tw, 2), intdiv($n - $th, 2), 0, 0, $tw, $th, $w, $h);
            imagedestroy($src);

            for ($y = 0; $y < $n; $y++) {
                for ($x = 0; $x < $n; $x++) {
                    $c = imagecolorat($sample, $x, $y);
                    $lum = 0.299 * (($c >> 16) & 255) + 0.587 * (($c >> 8) & 255) + 0.114 * ($c & 255);
                    $grid[$y][$x] = $lum < 225;
                }
            }
            imagedestroy($sample);

            return $grid;
        }

        for ($y = 0; $y < $n; $y++) {
            for ($x = 0; $x < $n; $x++) {
                $dx = (($x + 0.5) / $n) * 2 - 1;   // -1..1
                $dy = (($y + 0.5) / $n) * 2 - 1;

                $grid[$y][$x] = match ($kind) {
                    'circle' => $dx * $dx + $dy * $dy <= 1.0,
                    'squircle' => $dx ** 4 + $dy ** 4 <= 1.0,
                    'diamond' => abs($dx) + abs($dy) <= 1.12,
                    'heart' => self::inHeart($dx * 1.25, -$dy * 1.25 + 0.15),
                    default => true,
                };
            }
        }

        return $grid;
    }

    /** Cœur : (x² + y² − 1)³ − x²·y³ ≤ 0 */
    private static function inHeart(float $x, float $y): bool
    {
        return (($x * $x + $y * $y - 1) ** 3) - ($x * $x * $y ** 3) <= 0;
    }

    /** Couleur « fantôme » d'un point hors forme : un peu d'encre sur le fond. */
    private static function ghostColor($im, array $style, float $nx, float $ny): int
    {
        [$r1, $g1, $b1] = QrStyle::rgb($style['color']);
        if ($style['gradient'] !== 'none' && $style['color2']) {
            [$r2, $g2, $b2] = QrStyle::rgb($style['color2']);
            $t = max(0.0, min(1.0, match ($style['gradient']) {'h' => $nx, 'v' => $ny, default => ($nx + $ny) / 2}));
            $r1 = $r1 + ($r2 - $r1) * $t;
            $g1 = $g1 + ($g2 - $g1) * $t;
            $b1 = $b1 + ($b2 - $b1) * $t;
        }

        [$br, $bgc, $bb] = QrStyle::rgb($style['background']);
        $k = $style['ghost'] / 100;

        return imagecolorallocate(
            $im,
            (int) round($br + ($r1 - $br) * $k),
            (int) round($bgc + ($g1 - $bgc) * $k),
            (int) round($bb + ($b1 - $bb) * $k),
        );
    }

    private static function module($im, int $color, string $shape, int $scale, int $x, int $y, int $m): void
    {
        $s = max(2, (int) round($m * $scale / 100));
        $o = intdiv($m - $s, 2);
        $x += $o;
        $y += $o;

        switch ($shape) {
            case 'dots':
                $d = (int) round($s * 0.96);
                imagefilledellipse($im, $x + intdiv($s, 2), $y + intdiv($s, 2), $d, $d, $color);
                break;
            case 'rounded':
                self::roundRect($im, $color, $x, $y, $s, $s, (int) round($s * 0.32));
                break;
            case 'diamond':
                // Losange un peu plus grand que la case pour garder assez de matière (lecture fiable)
                $cx = $x + intdiv($s, 2);
                $cy = $y + intdiv($s, 2);
                $r = (int) round($s * 0.62);
                imagefilledpolygon($im, [$cx, $cy - $r, $cx + $r, $cy, $cx, $cy + $r, $cx - $r, $cy], $color);
                break;
            default:
                imagefilledrectangle($im, $x, $y, $x + $s, $y + $s, $color);
        }
    }

    private static function roundRect($im, int $color, int $x, int $y, int $w, int $h, int $r): void
    {
        $r = max(0, min($r, intdiv(min($w, $h), 2)));
        if ($r === 0) {
            imagefilledrectangle($im, $x, $y, $x + $w - 1, $y + $h - 1, $color);

            return;
        }
        imagefilledrectangle($im, $x + $r, $y, $x + $w - $r - 1, $y + $h - 1, $color);
        imagefilledrectangle($im, $x, $y + $r, $x + $w - 1, $y + $h - $r - 1, $color);
        $d = $r * 2;
        imagefilledellipse($im, $x + $r, $y + $r, $d, $d, $color);
        imagefilledellipse($im, $x + $w - $r - 1, $y + $r, $d, $d, $color);
        imagefilledellipse($im, $x + $r, $y + $h - $r - 1, $d, $d, $color);
        imagefilledellipse($im, $x + $w - $r - 1, $y + $h - $r - 1, $d, $d, $color);
    }

    /** Un « œil » (repère) de 7×7 modules : cadre, vide, pupille 3×3 — chaque partie a sa forme et sa couleur. */
    private static function eye($im, array $style, int $x, int $y, int $m, float $nx, float $ny, bool $art): void
    {
        $frameHex = $style['eyeColor'] ?? null;
        $frame = $frameHex ? self::alloc($im, $frameHex) : self::ink($im, $style, $nx, $ny);
        $pupilHex = $style['pupilColor'] ?? $frameHex;
        $pupil = $pupilHex ? self::alloc($im, $pupilHex) : $frame;
        $hole = self::alloc($im, $art ? '#ffffff' : $style['background']);

        $rounded = $style['eyeFrame'] === 'rounded';
        $rf = $rounded ? (int) round($m * self::$radiusFrame) : 0;
        $rh = $rounded ? (int) round($m * self::$radiusHole) : 0;
        $rp = match ($style['eyePupil']) {
            'circle' => (int) round($m * 1.5),
            'rounded' => (int) round($m * self::$radiusPupil),
            default => 0,
        };

        if ($art) {
            // Repère bien net : liseré de fond autour de l'œil (le « séparateur » du QR code)
            self::roundRect($im, $hole, $x - $m, $y - $m, 9 * $m, 9 * $m, 0);
        }

        self::roundRect($im, $frame, $x, $y, 7 * $m, 7 * $m, $rf);
        self::roundRect($im, $hole, $x + $m, $y + $m, 5 * $m, 5 * $m, $rh);
        self::roundRect($im, $pupil, $x + 2 * $m, $y + 2 * $m, 3 * $m, 3 * $m, $rp);
    }

    /** Point d'un code fusionné avec une image : rond foncé sur les modules sombres, rond clair sur les modules clairs. */
    private static function artModule($im, array $style, bool $dark, int $x, int $y, int $m, float $nx, float $ny): void
    {
        $cx = $x + intdiv($m, 2);
        $cy = $y + intdiv($m, 2);

        if ($dark) {
            $d = (int) round($m * 0.72);
            // Léger halo clair : le point reste net sur les zones sombres de l'image
            imagefilledellipse($im, $cx, $cy, $d + (int) round($m * 0.16), $d + (int) round($m * 0.16), imagecolorallocate($im, 255, 255, 255));
            imagefilledellipse($im, $cx, $cy, $d, $d, self::ink($im, $style, $nx, $ny));
        } else {
            $d = (int) round($m * 0.5);
            imagefilledellipse($im, $cx, $cy, $d, $d, imagecolorallocate($im, 255, 255, 255));
        }
    }

    /** Image de fond, cadrée sur la zone du code et éclaircie pour laisser lire les points. */
    private static function drawArt($im, string $path, int $offset, int $area, int $strength): void
    {
        $bytes = @file_get_contents($path);
        $art = $bytes ? @imagecreatefromstring($bytes) : false;

        if ($art === false) {
            return;
        }

        $w = imagesx($art);
        $h = imagesy($art);
        $side = min($w, $h);
        $sx = intdiv($w - $side, 2);
        $sy = intdiv($h - $side, 2);

        imagealphablending($im, true);
        imagecopyresampled($im, $art, $offset, $offset, $sx, $sy, $area, $area, $side, $side);
        imagedestroy($art);

        if ($strength > 0) {
            $white = imagecreatetruecolor($area, $area);
            imagefill($white, 0, 0, imagecolorallocate($white, 255, 255, 255));
            imagecopymerge($im, $white, $offset, $offset, 0, 0, $area, $area, $strength);
            imagedestroy($white);
        }
    }

    private static function drawLogo($im, int $bg, string $path, array $box, int $q, int $m): void
    {
        $bytes = @file_get_contents($path);
        $logo = $bytes ? @imagecreatefromstring($bytes) : false;

        if ($logo === false) {
            return;
        }

        $x0 = ($box[0] + $q) * $m;
        $y0 = ($box[1] + $q) * $m;
        $side = ($box[2] - $box[0] + 1) * $m;

        // Fond du logo : carré arrondi de la couleur du fond, légèrement plus grand
        $pad = (int) round($m * 0.5);
        self::roundRect($im, $bg, $x0 - $pad, $y0 - $pad, $side + 2 * $pad, $side + 2 * $pad, (int) round($m * 1.2));

        // Logo ajusté dans la zone, proportions conservées
        $lw = imagesx($logo);
        $lh = imagesy($logo);
        $scale = min($side / $lw, $side / $lh);
        $tw = max(1, (int) round($lw * $scale));
        $th = max(1, (int) round($lh * $scale));
        $tx = $x0 + intdiv($side - $tw, 2);
        $ty = $y0 + intdiv($side - $th, 2);

        imagealphablending($im, true);
        imagecopyresampled($im, $logo, $tx, $ty, 0, 0, $tw, $th, $lw, $lh);
        imagedestroy($logo);
    }

    private static function appendLabel($qr, string $label, array $style)
    {
        $fontPath = self::labelFontPath($label);

        if (! is_file($fontPath)) {
            return $qr;
        }

        $drawnLabel = ArabicGdText::forGd($label);

        $qrWidth = imagesx($qr);
        $qrHeight = imagesy($qr);
        $bbox = imagettfbbox(self::LABEL_FONT_SIZE, 0, $fontPath, $drawnLabel);
        $textWidth = abs($bbox[2] - $bbox[0]);
        $textHeight = abs($bbox[7] - $bbox[1]);
        $labelAreaHeight = $textHeight + (self::LABEL_PADDING * 2);
        $canvasWidth = max($qrWidth, $textWidth + (self::LABEL_PADDING * 2));
        $canvasHeight = $qrHeight + $labelAreaHeight;

        $canvas = imagecreatetruecolor($canvasWidth, $canvasHeight);
        $bg = self::alloc($canvas, $style['background']);
        imagefilledrectangle($canvas, 0, 0, $canvasWidth, $canvasHeight, $bg);

        imagecopy($canvas, $qr, (int) (($canvasWidth - $qrWidth) / 2), 0, 0, 0, $qrWidth, $qrHeight);
        imagedestroy($qr);

        $ink = self::alloc($canvas, $style['color']);
        $textX = (int) (($canvasWidth - $textWidth) / 2);
        $textY = $qrHeight + self::LABEL_PADDING + $textHeight;
        imagettftext($canvas, self::LABEL_FONT_SIZE, 0, $textX, $textY, $ink, $fontPath, $drawnLabel);

        return $canvas;
    }

    /** Pose le code sur une forme générale : carte arrondie ou médaillon rond, avec un liseré. */
    private static function applyBadge($content, array $style)
    {
        $w = imagesx($content);
        $h = imagesy($content);
        $ss = 3;
        $ring = 8;      // épaisseur du liseré (px finaux)
        $pad = 22;

        if ($style['badge'] === 'circle') {
            $bw = $bh = (int) ceil(sqrt($w * $w + $h * $h)) + 2 * $pad;
        } else {
            $bw = $w + 2 * $pad + 2 * $ring;
            $bh = $h + 2 * $pad + 2 * $ring;
        }

        $big = imagecreatetruecolor($bw * $ss, $bh * $ss);
        imagealphablending($big, false);
        imagesavealpha($big, true);
        imagefill($big, 0, 0, imagecolorallocatealpha($big, 0, 0, 0, 127));
        imagealphablending($big, true);

        $ringColor = self::alloc($big, $style['badgeColor'] ?? $style['color']);
        $fill = self::alloc($big, $style['background']);

        if ($style['badge'] === 'circle') {
            imagefilledellipse($big, intdiv($bw * $ss, 2), intdiv($bh * $ss, 2), $bw * $ss, $bh * $ss, $ringColor);
            imagefilledellipse($big, intdiv($bw * $ss, 2), intdiv($bh * $ss, 2), ($bw - 2 * $ring) * $ss, ($bh - 2 * $ring) * $ss, $fill);
        } else {
            self::roundRect($big, $ringColor, 0, 0, $bw * $ss, $bh * $ss, 44 * $ss);
            self::roundRect($big, $fill, $ring * $ss, $ring * $ss, ($bw - 2 * $ring) * $ss, ($bh - 2 * $ring) * $ss, (44 - $ring) * $ss);
        }

        $out = imagecreatetruecolor($bw, $bh);
        imagealphablending($out, false);
        imagesavealpha($out, true);
        imagefill($out, 0, 0, imagecolorallocatealpha($out, 0, 0, 0, 127));
        imagecopyresampled($out, $big, 0, 0, 0, 0, $bw, $bh, $bw * $ss, $bh * $ss);
        imagedestroy($big);

        imagealphablending($out, true);
        imagecopy($out, $content, intdiv($bw - $w, 2), intdiv($bh - $h, 2), 0, 0, $w, $h);
        imagedestroy($content);

        return $out;
    }

    private static function labelFontPath(string $label): string
    {
        $latinFont = resource_path('fonts/qr-label.ttf');

        if (ArabicGdText::containsRtl($label)) {
            $unicodeFont = resource_path('fonts/qr-label-unicode.ttf');

            if (is_file($unicodeFont)) {
                return $unicodeFont;
            }
        }

        return $latinFont;
    }
}
