<?php

namespace Tests\Unit;

use App\Services\PaypalCurrency;
use Tests\TestCase;

class PaypalCurrencyTest extends TestCase
{
    public function test_supported_currency_is_passed_through(): void
    {
        $payload = PaypalCurrency::forPaypal(12.5, 'EUR');

        $this->assertFalse($payload['converted']);
        $this->assertSame('EUR', $payload['currency']);
        $this->assertSame(12.5, $payload['amount']);
    }

    public function test_sar_converts_to_usd_using_exchange_rate(): void
    {
        $currency = (object) ['exchange_rate' => 3.75, 'usd_price' => null];
        $payload = PaypalCurrency::forPaypal(375, 'SAR', $currency);

        $this->assertTrue($payload['converted']);
        $this->assertSame('USD', $payload['currency']);
        $this->assertSame(100.0, $payload['amount']);
        $this->assertSame(375.0, $payload['original_amount']);
        $this->assertSame('SAR', $payload['original_currency']);
    }

    public function test_usd_price_is_preferred_for_conversion(): void
    {
        $currency = (object) ['exchange_rate' => 3.75, 'usd_price' => 0.27];
        $payload = PaypalCurrency::forPaypal(100, 'SAR', $currency);

        $this->assertTrue($payload['converted']);
        $this->assertSame(27.0, $payload['amount']);
    }

    public function test_unknown_unsupported_currency_uses_one_to_one_fallback(): void
    {
        $payload = PaypalCurrency::forPaypal(50, 'ZZZ');

        $this->assertTrue($payload['converted']);
        $this->assertSame('USD', $payload['currency']);
        $this->assertSame(50.0, $payload['amount']);
    }

    public function test_js_sdk_falls_back_to_usd_for_unsupported_codes(): void
    {
        $this->assertSame('USD', PaypalCurrency::jsSdkCode('SAR'));
        $this->assertSame('EUR', PaypalCurrency::jsSdkCode('EUR'));
        $this->assertSame('USD', PaypalCurrency::jsSdkCode(null));
    }

    public function test_approval_url_rejects_null_express_checkout_token(): void
    {
        $this->assertFalse(PaypalCurrency::isValidApprovalUrl(
            'https://www.paypal.com/cgi-bin/webscr?cmd=_express-checkout&token=EC-null'
        ));
        $this->assertTrue(PaypalCurrency::isValidApprovalUrl(
            'https://www.paypal.com/cgi-bin/webscr?cmd=_express-checkout&token=EC-4S12345678901234X'
        ));
        $this->assertTrue(PaypalCurrency::isValidApprovalUrl(
            'https://www.sandbox.paypal.com/checkoutnow?token=5O190127TN364715T'
        ));
    }

    public function test_zero_decimal_amount_formatting(): void
    {
        $this->assertSame('13', PaypalCurrency::formatAmount(12.6, 'JPY'));
        $this->assertSame('12.60', PaypalCurrency::formatAmount(12.6, 'USD'));
    }

    public function test_api_base_url_follows_mode(): void
    {
        $this->assertSame('https://api-m.paypal.com', PaypalCurrency::apiBaseUrl('live'));
        $this->assertSame('https://api-m.sandbox.paypal.com', PaypalCurrency::apiBaseUrl('sandbox'));
        $this->assertSame('https://api-m.sandbox.paypal.com', PaypalCurrency::apiBaseUrl(null));
    }
}
