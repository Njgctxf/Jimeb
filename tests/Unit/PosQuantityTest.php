<?php

namespace Tests\Unit;

use App\Support\PosQuantity;
use PHPUnit\Framework\TestCase;

class PosQuantityTest extends TestCase
{
    public function test_normalize_rounds_to_three_decimals(): void
    {
        $this->assertSame(0.57, PosQuantity::normalize(0.5704));
        $this->assertSame(1.0, PosQuantity::normalize(1));
    }

    public function test_normalize_enforces_minimum(): void
    {
        $this->assertSame(0.001, PosQuantity::normalize(0));
        $this->assertSame(0.001, PosQuantity::normalize(-2));
    }

    public function test_grams_and_kg_conversion(): void
    {
        $this->assertSame(0.57, PosQuantity::gramsToKg(570));
        $this->assertSame(1000.0, PosQuantity::kgToGrams(1));
    }

    public function test_format_display_with_units(): void
    {
        $this->assertSame('100 g', PosQuantity::formatDisplay(0.1, true));
        $this->assertSame('570 g', PosQuantity::formatDisplay(0.57, true));
        $this->assertSame('2 kg', PosQuantity::formatDisplay(2, true));
        $this->assertSame('1.25 kg', PosQuantity::formatDisplay(1.25, true));
        $this->assertSame('3', PosQuantity::formatDisplay(3, false));
    }
}
