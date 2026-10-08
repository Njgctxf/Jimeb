<?php

namespace App\Helper;

/**
 * Shape Arabic (and Persian) text for GD imagettftext.
 *
 * GD does not join Arabic letters or apply bidi unless it was built with
 * libraqm. This converts logical UTF-8 into presentation forms in visual
 * order so a Unicode TTF can render readable Arabic on QR labels.
 */
class ArabicGdText
{
    public static function containsRtl(string $text): bool
    {
        return (bool) preg_match('/\p{Arabic}/u', $text);
    }

    public static function forGd(string $text): string
    {
        if ($text === '' || ! self::containsRtl($text) || self::gdHasRaqm()) {
            return $text;
        }

        return self::shape($text);
    }

    public static function shape(string $text): string
    {
        if ($text === '' || ! self::containsRtl($text)) {
            return $text;
        }

        $tokens = self::tokenize($text);
        $rtl = self::startsRtl($tokens);

        foreach ($tokens as $i => $token) {
            if ($token['rtl']) {
                $tokens[$i]['text'] = self::shapeRun($token['text']);
            }
        }

        if ($rtl) {
            $tokens = array_reverse($tokens);
        }

        return implode('', array_column($tokens, 'text'));
    }

    /**
     * @return list<array{text: string, rtl: bool}>
     */
    private static function tokenize(string $text): array
    {
        $chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $tokens = [];
        $buffer = '';
        $kind = null;

        foreach ($chars as $char) {
            $charKind = self::tokenKind($char);

            if ($kind === null) {
                $buffer = $char;
                $kind = $charKind;
                continue;
            }

            if ($charKind === $kind) {
                $buffer .= $char;
                continue;
            }

            $tokens[] = ['text' => $buffer, 'rtl' => $kind === 'rtl'];
            $buffer = $char;
            $kind = $charKind;
        }

        if ($buffer !== '') {
            $tokens[] = ['text' => $buffer, 'rtl' => $kind === 'rtl'];
        }

        return $tokens;
    }

    private static function tokenKind(string $char): string
    {
        if (self::isArabicLetter($char)) {
            return 'rtl';
        }

        if (preg_match('/\s/u', $char)) {
            return 'space';
        }

        return 'ltr';
    }

    /**
     * @param  list<array{text: string, rtl: bool}>  $tokens
     */
    private static function startsRtl(array $tokens): bool
    {
        foreach ($tokens as $token) {
            if ($token['rtl']) {
                return true;
            }

            if (preg_match('/\p{L}/u', $token['text'])) {
                return false;
            }
        }

        return false;
    }

    private static function shapeRun(string $text): string
    {
        $chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $glyphs = self::glyphs();
        $output = [];
        $count = count($chars);

        for ($i = 0; $i < $count; $i++) {
            $char = $chars[$i];

            if (self::isTransparent($char) || $char === self::chr(0x0640)) {
                $output[] = $char;
                continue;
            }

            if (! isset($glyphs[$char])) {
                $output[] = $char;
                continue;
            }

            $next = self::nextBase($chars, $i + 1);
            $ligature = self::lamAlefLigature($char, $next['char'] ?? null, self::previousJoinsNext($chars, $i));

            if ($ligature !== null) {
                $output[] = $ligature;
                $i = $next['index'];
                continue;
            }

            $joinsFromPrev = self::previousJoinsNext($chars, $i);
            $thisJoinsNext = self::joinsNext($char);
            $nextJoinsPrev = $next !== null && self::joinsPrev($next['char']);
            $forms = $glyphs[$char];

            if ($joinsFromPrev && $thisJoinsNext && $nextJoinsPrev) {
                $form = $forms[3] ?? $forms[1] ?? $forms[0];
            } elseif ($joinsFromPrev) {
                $form = $forms[1] ?? $forms[0];
            } elseif ($thisJoinsNext && $nextJoinsPrev) {
                $form = $forms[2] ?? $forms[0];
            } else {
                $form = $forms[0];
            }

            $output[] = $form;
        }

        return implode('', array_reverse($output));
    }

    /**
     * @param  list<string>  $chars
     * @return array{char: string, index: int}|null
     */
    private static function nextBase(array $chars, int $from): ?array
    {
        $count = count($chars);

        for ($i = $from; $i < $count; $i++) {
            if (self::isTransparent($chars[$i])) {
                continue;
            }

            return ['char' => $chars[$i], 'index' => $i];
        }

        return null;
    }

    /**
     * @param  list<string>  $chars
     */
    private static function previousJoinsNext(array $chars, int $index): bool
    {
        for ($i = $index - 1; $i >= 0; $i--) {
            if (self::isTransparent($chars[$i])) {
                continue;
            }

            return self::joinsNext($chars[$i]);
        }

        return false;
    }

    private static function joinsNext(string $char): bool
    {
        if ($char === self::chr(0x0640)) {
            return true;
        }

        $forms = self::glyphs()[$char] ?? null;

        return $forms !== null && isset($forms[2]);
    }

    private static function joinsPrev(string $char): bool
    {
        if ($char === self::chr(0x0640)) {
            return true;
        }

        if ($char === self::chr(0x0621)) {
            return false;
        }

        return isset(self::glyphs()[$char]);
    }

    private static function lamAlefLigature(string $lam, ?string $alef, bool $final): ?string
    {
        if ($lam !== self::chr(0x0644) || $alef === null) {
            return null;
        }

        $map = [
            0x0627 => [0xFEFB, 0xFEFC],
            0x0622 => [0xFEF5, 0xFEF6],
            0x0623 => [0xFEF7, 0xFEF8],
            0x0625 => [0xFEF9, 0xFEFA],
        ];

        $ord = mb_ord($alef, 'UTF-8');

        if ($ord === false || ! isset($map[$ord])) {
            return null;
        }

        return self::chr($map[$ord][$final ? 1 : 0]);
    }

    private static function isArabicLetter(string $char): bool
    {
        $ord = mb_ord($char, 'UTF-8');

        if ($ord === false) {
            return false;
        }

        if (($ord >= 0x0660 && $ord <= 0x0669) || ($ord >= 0x06F0 && $ord <= 0x06F9)) {
            return false;
        }

        return ($ord >= 0x0600 && $ord <= 0x06FF)
            || ($ord >= 0x0750 && $ord <= 0x077F)
            || ($ord >= 0x08A0 && $ord <= 0x08FF)
            || ($ord >= 0xFB50 && $ord <= 0xFDFF)
            || ($ord >= 0xFE70 && $ord <= 0xFEFF);
    }

    private static function isTransparent(string $char): bool
    {
        $ord = mb_ord($char, 'UTF-8');

        if ($ord === false) {
            return false;
        }

        return ($ord >= 0x064B && $ord <= 0x065F)
            || $ord === 0x0670
            || ($ord >= 0x06D6 && $ord <= 0x06ED)
            || ($ord >= 0x08D3 && $ord <= 0x08FF);
    }

    private static function gdHasRaqm(): bool
    {
        static $cached = null;

        if ($cached !== null) {
            return $cached;
        }

        $info = function_exists('gd_info') ? gd_info() : [];

        if (! empty($info['RAQM Support'])) {
            return $cached = true;
        }

        if (! function_exists('phpinfo')) {
            return $cached = false;
        }

        ob_start();
        phpinfo(INFO_MODULES);
        $output = (string) ob_get_clean();

        return $cached = stripos($output, 'Raqm') !== false;
    }

    private static function chr(int $codepoint): string
    {
        return mb_chr($codepoint, 'UTF-8');
    }

    /**
     * Isolated, final, and optionally initial + medial presentation forms.
     *
     * @return array<string, list<string>>
     */
    private static function glyphs(): array
    {
        static $glyphs = null;

        if ($glyphs !== null) {
            return $glyphs;
        }

        $glyphs = [];
        $add = static function (int $letter, array $forms) use (&$glyphs): void {
            $glyphs[mb_chr($letter, 'UTF-8')] = array_map(
                static fn (int $cp): string => mb_chr($cp, 'UTF-8'),
                $forms
            );
        };

        $add(0x0621, [0xFE80]);
        $add(0x0622, [0xFE81, 0xFE82]);
        $add(0x0623, [0xFE83, 0xFE84]);
        $add(0x0624, [0xFE85, 0xFE86]);
        $add(0x0625, [0xFE87, 0xFE88]);
        $add(0x0626, [0xFE89, 0xFE8A, 0xFE8B, 0xFE8C]);
        $add(0x0627, [0xFE8D, 0xFE8E]);
        $add(0x0628, [0xFE8F, 0xFE90, 0xFE91, 0xFE92]);
        $add(0x0629, [0xFE93, 0xFE94]);
        $add(0x062A, [0xFE95, 0xFE96, 0xFE97, 0xFE98]);
        $add(0x062B, [0xFE99, 0xFE9A, 0xFE9B, 0xFE9C]);
        $add(0x062C, [0xFE9D, 0xFE9E, 0xFE9F, 0xFEA0]);
        $add(0x062D, [0xFEA1, 0xFEA2, 0xFEA3, 0xFEA4]);
        $add(0x062E, [0xFEA5, 0xFEA6, 0xFEA7, 0xFEA8]);
        $add(0x062F, [0xFEA9, 0xFEAA]);
        $add(0x0630, [0xFEAB, 0xFEAC]);
        $add(0x0631, [0xFEAD, 0xFEAE]);
        $add(0x0632, [0xFEAF, 0xFEB0]);
        $add(0x0633, [0xFEB1, 0xFEB2, 0xFEB3, 0xFEB4]);
        $add(0x0634, [0xFEB5, 0xFEB6, 0xFEB7, 0xFEB8]);
        $add(0x0635, [0xFEB9, 0xFEBA, 0xFEBB, 0xFEBC]);
        $add(0x0636, [0xFEBD, 0xFEBE, 0xFEBF, 0xFEC0]);
        $add(0x0637, [0xFEC1, 0xFEC2, 0xFEC3, 0xFEC4]);
        $add(0x0638, [0xFEC5, 0xFEC6, 0xFEC7, 0xFEC8]);
        $add(0x0639, [0xFEC9, 0xFECA, 0xFECB, 0xFECC]);
        $add(0x063A, [0xFECD, 0xFECE, 0xFECF, 0xFED0]);
        $add(0x0641, [0xFED1, 0xFED2, 0xFED3, 0xFED4]);
        $add(0x0642, [0xFED5, 0xFED6, 0xFED7, 0xFED8]);
        $add(0x0643, [0xFED9, 0xFEDA, 0xFEDB, 0xFEDC]);
        $add(0x0644, [0xFEDD, 0xFEDE, 0xFEDF, 0xFEE0]);
        $add(0x0645, [0xFEE1, 0xFEE2, 0xFEE3, 0xFEE4]);
        $add(0x0646, [0xFEE5, 0xFEE6, 0xFEE7, 0xFEE8]);
        $add(0x0647, [0xFEE9, 0xFEEA, 0xFEEB, 0xFEEC]);
        $add(0x0648, [0xFEED, 0xFEEE]);
        $add(0x0649, [0xFEEF, 0xFEF0]);
        $add(0x064A, [0xFEF1, 0xFEF2, 0xFEF3, 0xFEF4]);
        $add(0x067E, [0xFB56, 0xFB57, 0xFB58, 0xFB59]);
        $add(0x0686, [0xFB7A, 0xFB7B, 0xFB7C, 0xFB7D]);
        $add(0x0698, [0xFB8A, 0xFB8B]);
        $add(0x06A9, [0xFB8E, 0xFB8F, 0xFB90, 0xFB91]);
        $add(0x06AF, [0xFB92, 0xFB93, 0xFB94, 0xFB95]);
        $add(0x06BE, [0xFBAA, 0xFBAB, 0xFBAC, 0xFBAD]);
        $add(0x06C0, [0xFBA4, 0xFBA5]);
        $add(0x06C1, [0xFBA6, 0xFBA7, 0xFBA8, 0xFBA9]);
        $add(0x06CC, [0xFBFC, 0xFBFD, 0xFBFE, 0xFBFF]);
        $add(0x06D5, [0xFEE9, 0xFEEA]);

        return $glyphs;
    }
}
