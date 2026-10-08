<?php

namespace App\Translation;

use App\Support\TranslationHtml;
use Illuminate\Translation\Translator as BaseTranslator;

class Translator extends BaseTranslator
{
    /**
     * @param  array<string, mixed>  $replace
     */
    public function get($key, array $replace = [], $locale = null, $fallback = true)
    {
        return TranslationHtml::decode(parent::get($key, $replace, $locale, $fallback));
    }
}
