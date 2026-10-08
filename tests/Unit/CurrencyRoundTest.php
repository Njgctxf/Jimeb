<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class CurrencyRoundTest extends TestCase
{
    public function test_three_decimal_currency_keeps_omani_baisa(): void
    {
        $this->assertSame(0.683, currency_round(0.683, null, 3));
        $this->assertSame(0.68, currency_round(0.683, null, 2));
    }

    public function test_inclusive_vat_split_rounds_to_three_decimals(): void
    {
        $inclusive = 0.683;
        $rate = 5.0;
        $net = $inclusive / (1 + $rate / 100);
        $vat = $inclusive - $net;

        $this->assertSame(0.650, currency_round($net, null, 3));
        $this->assertSame(0.033, currency_round($vat, null, 3));
        $this->assertSame(0.683, currency_round($inclusive, null, 3));
        $this->assertSame(
            0.683,
            currency_round(currency_round($net, null, 3) + currency_round($vat, null, 3), null, 3)
        );
    }

    public function test_two_decimal_currency_unchanged(): void
    {
        $this->assertSame(10.99, currency_round(10.987, null, 2));
        $this->assertSame(1.0, currency_round(0.995, null, 2));
    }
}
