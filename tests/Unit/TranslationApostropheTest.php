<?php

namespace Tests\Unit;

use App\Support\TranslationHtml;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Lang;
use Tests\TestCase;

class TranslationApostropheTest extends TestCase
{
    public function test_html_entities_in_translations_are_decoded(): void
    {
        $this->overrideTranslation('Nome dell&#039;area');

        $this->assertSame("Nome dell'area", __('modules.table.areaName'));
    }

    public function test_php_escaped_apostrophe_stays_an_apostrophe(): void
    {
        $this->overrideTranslation("Nome dell'area");

        $this->assertSame("Nome dell'area", __('modules.table.areaName'));
    }

    public function test_label_component_does_not_double_encode_apostrophes(): void
    {
        $html = Blade::render(
            '<x-label for="areaName" value="{{ $text }}" />',
            ['text' => "Nome dell'area"]
        );

        $this->assertStringNotContainsString('&amp;#039;', $html);
        $this->assertStringNotContainsString('&amp;#39;', $html);
        $this->assertSame(
            "Nome dell'area",
            trim(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8'))
        );
    }

    public function test_label_component_decodes_already_encoded_apostrophes(): void
    {
        $html = Blade::render(
            '<x-label for="areaName" value="{{ $text }}" />',
            ['text' => 'Nome dell&#039;area']
        );

        $this->assertStringNotContainsString('&amp;#039;', $html);
        $this->assertSame(
            "Nome dell'area",
            trim(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8'))
        );
    }

    public function test_blade_echo_does_not_double_encode_translation_entities(): void
    {
        $this->overrideTranslation('Nome dell&#039;area');

        $html = Blade::render("{{ __('modules.table.areaName') }}");

        $this->assertStringNotContainsString('&amp;#039;', $html);
        $this->assertSame(
            "Nome dell'area",
            trim(html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8'))
        );
    }

    public function test_translation_html_decode_leaves_safe_markup_as_text_after_blade_escape(): void
    {
        $this->assertSame("Nome dell'area", TranslationHtml::decode('Nome dell&#039;area'));
        $this->assertSame("Nome dell'area", TranslationHtml::decode("Nome dell'area"));
        $this->assertSame('<b>Hi</b>', TranslationHtml::decode('&lt;b&gt;Hi&lt;/b&gt;'));
    }

    private function overrideTranslation(string $value): void
    {
        __('modules.table.areaName');

        Lang::addLines([
            'modules.table.areaName' => $value,
        ], app()->getLocale());
    }
}
