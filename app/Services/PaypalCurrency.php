<?php

namespace App\Services;

use App\Models\Currency;
use App\Models\GlobalCurrency;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaypalCurrency
{
    public const FALLBACK = 'USD';

    /**
     * Currencies accepted by PayPal Checkout and Billing Plans.
     *
     * @see https://developer.paypal.com/docs/reports/reference/paypal-supported-currencies/
     */
    public const SUPPORTED = [
        'AUD', 'BRL', 'CAD', 'CNY', 'CZK', 'DKK', 'EUR', 'HKD', 'HUF', 'ILS',
        'JPY', 'MYR', 'MXN', 'TWD', 'NZD', 'NOK', 'PHP', 'PLN', 'GBP', 'SGD',
        'SEK', 'CHF', 'THB', 'USD',
    ];

    public const ZERO_DECIMAL = ['HUF', 'JPY', 'TWD'];

    /**
     * Units of the local currency per 1 USD. Used only when the store has no
     * exchange_rate/usd_price and the live FX lookup is unavailable.
     */
    private const FALLBACK_PER_USD = [
        'AED' => 3.67,
        'ARS' => 1200.0,
        'BDT' => 110.0,
        'BHD' => 0.376,
        'CLP' => 950.0,
        'COP' => 4100.0,
        'EGP' => 49.0,
        'ETB' => 120.0,
        'GHS' => 15.5,
        'IDR' => 16200.0,
        'INR' => 83.0,
        'IQD' => 1310.0,
        'JOD' => 0.71,
        'KES' => 129.0,
        'KRW' => 1380.0,
        'KWD' => 0.307,
        'LBP' => 89500.0,
        'LKR' => 300.0,
        'MAD' => 10.0,
        'NGN' => 1600.0,
        'OMR' => 0.385,
        'PEN' => 3.7,
        'PKR' => 278.0,
        'QAR' => 3.64,
        'RUB' => 90.0,
        'SAR' => 3.75,
        'TRY' => 34.0,
        'TZS' => 2650.0,
        'UAH' => 41.0,
        'UGX' => 3700.0,
        'VND' => 25400.0,
        'ZAR' => 18.5,
    ];

    public static function isSupported(?string $code): bool
    {
        return in_array(strtoupper((string) $code), self::SUPPORTED, true);
    }

    public static function jsSdkCode(?string $code): string
    {
        $code = strtoupper((string) $code);

        return self::isSupported($code) ? $code : self::FALLBACK;
    }

    public static function apiBaseUrl(?string $mode): string
    {
        return $mode === 'live' ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com';
    }

    /**
     * Convert an amount into a PayPal-supported currency (USD fallback).
     *
     * @return array{amount: float, currency: string, converted: bool, original_amount: float, original_currency: string}
     */
    public static function forPaypal(float|int|string $amount, ?string $currencyCode, mixed $currencyModel = null): array
    {
        $originalAmount = (float) $amount;
        $originalCurrency = strtoupper((string) ($currencyCode ?: self::FALLBACK));

        if ($originalCurrency === '' || self::isSupported($originalCurrency)) {
            $currency = $originalCurrency !== '' ? $originalCurrency : self::FALLBACK;

            return [
                'amount' => $originalAmount,
                'currency' => $currency,
                'converted' => false,
                'original_amount' => $originalAmount,
                'original_currency' => $currency,
            ];
        }

        $usdAmount = self::toUsd($originalAmount, $originalCurrency, $currencyModel);

        Log::info('PayPal amount converted to supported currency', [
            'from' => $originalCurrency,
            'from_amount' => $originalAmount,
            'to' => self::FALLBACK,
            'to_amount' => $usdAmount,
        ]);

        return [
            'amount' => $usdAmount,
            'currency' => self::FALLBACK,
            'converted' => true,
            'original_amount' => $originalAmount,
            'original_currency' => $originalCurrency,
        ];
    }

    public static function formatAmount(float $amount, string $currency): string
    {
        $currency = strtoupper($currency);

        if (in_array($currency, self::ZERO_DECIMAL, true)) {
            return (string) (int) round($amount);
        }

        return number_format($amount, 2, '.', '');
    }

    public static function isValidApprovalUrl(?string $url): bool
    {
        if (! is_string($url) || $url === '') {
            return false;
        }

        if (preg_match('/token=(EC-null|null)(&|$)/i', $url)) {
            return false;
        }

        if (preg_match('/[?&]token=([^&]+)/i', $url, $matches)) {
            return $matches[1] !== '';
        }

        return str_contains($url, 'paypal.com');
    }

    private static function toUsd(float $amount, string $code, mixed $currencyModel): float
    {
        $usdPrice = self::numericProperty($currencyModel, 'usd_price');
        $exchangeRate = self::numericProperty($currencyModel, 'exchange_rate');

        if ($usdPrice === null || $exchangeRate === null) {
            $stored = self::storedCurrency($code);
            $usdPrice = $usdPrice ?? self::numericProperty($stored, 'usd_price');
            $exchangeRate = $exchangeRate ?? self::numericProperty($stored, 'exchange_rate');
        }

        if ($usdPrice !== null && $usdPrice > 0) {
            return round($amount * $usdPrice, 2);
        }

        if ($exchangeRate !== null && $exchangeRate > 0) {
            return round($amount / $exchangeRate, 2);
        }

        $liveRate = self::unitsPerUsdFromLiveApi($code);
        if ($liveRate !== null && $liveRate > 0) {
            return round($amount / $liveRate, 2);
        }

        $fallback = self::FALLBACK_PER_USD[$code] ?? 1.0;

        return round($amount / $fallback, 2);
    }

    private static function storedCurrency(string $code): mixed
    {
        try {
            $global = GlobalCurrency::query()->where('currency_code', $code)->first();
            if ($global) {
                return $global;
            }

            return Currency::query()->withoutGlobalScopes()->where('currency_code', $code)->first();
        } catch (\Throwable $e) {
            return null;
        }
    }

    private static function numericProperty(mixed $model, string $key): ?float
    {
        if (! is_object($model) || ! isset($model->{$key}) || $model->{$key} === '' || $model->{$key} === null) {
            return null;
        }

        $value = (float) $model->{$key};

        return $value > 0 ? $value : null;
    }

    private static function unitsPerUsdFromLiveApi(string $code): ?float
    {
        if (app()->environment('testing')) {
            return null;
        }

        try {
            $rates = Cache::remember('paypal_usd_fx_rates', now()->addHours(12), function () {
                $response = Http::timeout(5)->get('https://open.er-api.com/v6/latest/USD');

                if (! $response->successful() || ($response->json('result') !== 'success')) {
                    return [];
                }

                return $response->json('rates') ?? [];
            });

            $rate = $rates[$code] ?? null;

            return is_numeric($rate) ? (float) $rate : null;
        } catch (\Throwable $e) {
            Log::warning('PayPal FX lookup failed', ['currency' => $code, 'error' => $e->getMessage()]);

            return null;
        }
    }
}
