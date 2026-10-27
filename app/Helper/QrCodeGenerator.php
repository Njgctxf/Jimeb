<?php

namespace App\Helper;

use App\Support\QrStyle;
use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder;
use RuntimeException;

/**
 * Génère l'image PNG d'un QR code personnalisé : forme des modules et des « yeux », couleurs, logo au centre,
 * libellé facultatif en dessous. Dessiné avec GD en haute résolution puis réduit (bords lisses).
 */
class QrCodeGenerator
{
    /** Taille finale d'un module, en pixels. */
    private const MODULE_PX = 12;

    /** Facteur de sur-échantillonnage (lissage). */
    private const SS = 3;

    /** Zone calme (en modules) autour du code. */
    private const QUIET = 4;

    /** Part de la largeur du code occupée par le logo. */
    private const LOGO_RATIO = 0.22;

    private const LABEL_FONT_SIZE = 20;

    private const LABEL_PADDING = 12;

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

        $fg = self::alloc($im, $style['color']);
        $bg = self::alloc($im, $style['background']);
        imagefilledrectangle($im, 0, 0, $size, $size, $bg);

        // Zone réservée au logo (en modules, centrée)
        $logoBox = null;
        if ($style['logo']) {
            $side = max(3, (int) round($n * self::LOGO_RATIO));
            if (($n - $side) % 2 !== 0) {
                $side++;
            }
            $start = intdiv($n - $side, 2);
            $logoBox = [$start, $start, $start + $side - 1, $start + $side - 1];
        }

        $inEye = static fn (int $x, int $y): bool => ($x < 7 && $y < 7) || ($x >= $n - 7 && $y < 7) || ($x < 7 && $y >= $n - 7);

        // Modules de données
        for ($y = 0; $y < $n; $y++) {
            for ($x = 0; $x < $n; $x++) {
                if ($matrix->get($x, $y) !== 1 || $inEye($x, $y)) {
                    continue;
                }
                if ($logoBox && $x >= $logoBox[0] - 1 && $x <= $logoBox[2] + 1 && $y >= $logoBox[1] - 1 && $y <= $logoBox[3] + 1) {
                    continue;
                }
                self::module($im, $fg, $style['shape'], ($x + $q) * $m, ($y + $q) * $m, $m);
            }
        }

        // Les trois « yeux »
        foreach ([[0, 0], [$n - 7, 0], [0, $n - 7]] as [$ex, $ey]) {
            self::eye($im, $fg, $bg, $style['eye'], ($ex + $q) * $m, ($ey + $q) * $m, $m);
        }

        // Logo central
        if ($logoBox && $style['logo']) {
            self::drawLogo($im, $bg, QrStyle::logoPath($style['logo']), $logoBox, $q, $m);
        }

        // Réduction : bords lisses
        $out = self::MODULE_PX * ($n + 2 * $q);
        $final = imagecreatetruecolor($out, $out);
        imagecopyresampled($final, $im, 0, 0, 0, 0, $out, $out, $size, $size);
        imagedestroy($im);

        if ($label !== null && $label !== '') {
            $final = self::appendLabel($final, $label, $style);
        }

        ob_start();
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

    private static function module($im, int $color, string $shape, int $x, int $y, int $m): void
    {
        switch ($shape) {
            case 'dots':
                $d = (int) round($m * 0.88);
                imagefilledellipse($im, $x + intdiv($m, 2), $y + intdiv($m, 2), $d, $d, $color);
                break;
            case 'rounded':
                self::roundRect($im, $color, $x, $y, $m, $m, (int) round($m * 0.32));
                break;
            case 'diamond':
                // Losange un peu plus grand que la case pour garder assez de matière (lecture fiable)
                $cx = $x + intdiv($m, 2);
                $cy = $y + intdiv($m, 2);
                $r = (int) round($m * 0.62);
                imagefilledpolygon($im, [$cx, $cy - $r, $cx + $r, $cy, $cx, $cy + $r, $cx - $r, $cy], $color);
                break;
            default:
                imagefilledrectangle($im, $x, $y, $x + $m, $y + $m, $color);
        }
    }

    private static function roundRect($im, int $color, int $x, int $y, int $w, int $h, int $r): void
    {
        $r = max(0, min($r, intdiv(min($w, $h), 2)));
        if ($r === 0) {
            imagefilledrectangle($im, $x, $y, $x + $w, $y + $h, $color);

            return;
        }
        imagefilledrectangle($im, $x + $r, $y, $x + $w - $r, $y + $h, $color);
        imagefilledrectangle($im, $x, $y + $r, $x + $w, $y + $h - $r, $color);
        $d = $r * 2;
        imagefilledellipse($im, $x + $r, $y + $r, $d, $d, $color);
        imagefilledellipse($im, $x + $w - $r, $y + $r, $d, $d, $color);
        imagefilledellipse($im, $x + $r, $y + $h - $r, $d, $d, $color);
        imagefilledellipse($im, $x + $w - $r, $y + $h - $r, $d, $d, $color);
    }

    /** Un « œil » (repère) de 7×7 modules : anneau extérieur, vide, centre 3×3. */
    private static function eye($im, int $fg, int $bg, string $style, int $x, int $y, int $m): void
    {
        $s7 = 7 * $m;
        $s5 = 5 * $m;
        $s3 = 3 * $m;

        switch ($style) {
            case 'circle':
                imagefilledellipse($im, $x + intdiv($s7, 2), $y + intdiv($s7, 2), $s7, $s7, $fg);
                imagefilledellipse($im, $x + intdiv($s7, 2), $y + intdiv($s7, 2), $s5, $s5, $bg);
                imagefilledellipse($im, $x + intdiv($s7, 2), $y + intdiv($s7, 2), $s3, $s3, $fg);
                break;
            case 'rounded':
                self::roundRect($im, $fg, $x, $y, $s7, $s7, (int) round($m * 2.2));
                self::roundRect($im, $bg, $x + $m, $y + $m, $s5, $s5, (int) round($m * 1.4));
                self::roundRect($im, $fg, $x + 2 * $m, $y + 2 * $m, $s3, $s3, (int) round($m * 0.9));
                break;
            default:
                imagefilledrectangle($im, $x, $y, $x + $s7 - 1, $y + $s7 - 1, $fg);
                imagefilledrectangle($im, $x + $m, $y + $m, $x + $s7 - $m - 1, $y + $s7 - $m - 1, $bg);
                imagefilledrectangle($im, $x + 2 * $m, $y + 2 * $m, $x + $s7 - 2 * $m - 1, $y + $s7 - 2 * $m - 1, $fg);
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
