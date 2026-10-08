<?php

namespace Tests\Unit;

use App\Helper\ArabicGdText;
use PHPUnit\Framework\TestCase;

class ArabicGdTextTest extends TestCase
{
    public function test_english_label_is_unchanged(): void
    {
        $this->assertSame('Table 1', ArabicGdText::shape('Table 1'));
        $this->assertFalse(ArabicGdText::containsRtl('Table 1'));
    }

    public function test_detects_arabic_in_label(): void
    {
        $this->assertTrue(ArabicGdText::containsRtl('طاولة'));
        $this->assertTrue(ArabicGdText::containsRtl('Table طاولة'));
    }

    public function test_isolated_beh_uses_presentation_form(): void
    {
        $this->assertSame(mb_chr(0xFE8F, 'UTF-8'), ArabicGdText::shape('ب'));
    }

    public function test_joins_beh_and_alef_and_reverses_for_ltr_drawing(): void
    {
        $shaped = ArabicGdText::shape('با');

        $this->assertSame(
            mb_chr(0xFE8E, 'UTF-8') . mb_chr(0xFE91, 'UTF-8'),
            $shaped
        );
    }

    public function test_mixed_english_prefix_keeps_latin_on_the_left(): void
    {
        $shaped = ArabicGdText::shape('Table با');

        $this->assertStringStartsWith('Table ', $shaped);
        $this->assertStringEndsWith(mb_chr(0xFE8E, 'UTF-8') . mb_chr(0xFE91, 'UTF-8'), $shaped);
    }

    public function test_arabic_name_with_number_puts_number_on_the_left(): void
    {
        $shaped = ArabicGdText::shape('طاولة 5');

        $this->assertStringStartsWith('5 ', $shaped);
        $this->assertNotSame('طاولة 5', $shaped);
    }

    public function test_lam_alef_becomes_ligature(): void
    {
        $this->assertSame(mb_chr(0xFEFB, 'UTF-8'), ArabicGdText::shape('لا'));
    }
}
