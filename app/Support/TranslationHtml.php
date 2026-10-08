<?php

namespace App\Support;

class TranslationHtml
{
    /**
     * Turn HTML entities such as &#039; back into characters so apostrophes
     * are not double-escaped when Blade/components encode the string again.
     */
    public static function decode(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map([self::class, 'decode'], $value);
        }

        if (! is_string($value) || ! str_contains($value, '&')) {
            return $value;
        }

        return html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
