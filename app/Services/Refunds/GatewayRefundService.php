<?php

namespace App\Services\Refunds;

use App\Models\EpayPayment;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentGatewayCredential;
use App\Models\XenditPayment;
use App\Services\PaypalCurrency;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Mollie\Api\MollieApiClient;
use Razorpay\Api\Api as RazorpayApi;
use Throwable;

class GatewayRefundService
{
    public const GATEWAY_METHODS = [
        'stripe',
        'razorpay',
        'paypal',
        'flutterwave',
        'paystack',
        'payfast',
        'xendit',
        'mollie',
        'tap',
        'tlync',
        'epay',
    ];

    public function applyOrderRefund(Order $order, float $amount, string $refundType): void
    {
        if (! in_array($refundType, ['full', 'partial'], true)) {
            return;
        }

        $order->amount_paid = max(0, round((float) $order->amount_paid - $amount, 2));
        $order->status = round((float) $order->amount_paid, 2) >= round((float) $order->total, 2)
            ? 'paid'
            : 'payment_due';
        $order->save();
    }

    public function isGatewayMethod(?string $paymentMethod): bool
    {
        return in_array(strtolower((string) $paymentMethod), self::GATEWAY_METHODS, true);
    }

    public function shouldRefundOnGateway(Payment $payment, string $refundType): bool
    {
        if (! in_array($refundType, ['full', 'partial'], true)) {
            return false;
        }

        return $this->isGatewayMethod($payment->payment_method);
    }

    public function refund(Payment $payment, float|string $amount, ?string $reason = null): GatewayRefundResult
    {
        $method = strtolower((string) $payment->payment_method);

        if (! $this->isGatewayMethod($method)) {
            return GatewayRefundResult::skipped();
        }

        $transactionId = trim((string) ($payment->transaction_id ?? ''));

        if ($transactionId === '') {
            return GatewayRefundResult::failed(
                __('messages.gatewayRefundMissingTransactionId')
            );
        }

        $credentials = $this->credentials($payment);

        if (! $credentials) {
            return GatewayRefundResult::failed(
                __('messages.gatewayRefundMissingCredentials')
            );
        }

        $amount = round((float) $amount, 2);
        $reason = $reason ?: 'Customer refund';

        try {
            return match ($method) {
                'stripe' => $this->refundStripe($credentials, $transactionId, $amount),
                'razorpay' => $this->refundRazorpay($credentials, $transactionId, $amount),
                'paypal' => $this->refundPaypal($payment, $credentials, $transactionId, $amount, $reason),
                'flutterwave' => $this->refundFlutterwave($credentials, $transactionId, $amount),
                'paystack' => $this->refundPaystack($credentials, $transactionId, $amount),
                'payfast' => $this->refundPayfast($credentials, $transactionId, $amount, $reason),
                'xendit' => $this->refundXendit($payment, $credentials, $transactionId, $amount),
                'mollie' => $this->refundMollie($payment, $credentials, $transactionId, $amount),
                'tap' => $this->refundTap($payment, $credentials, $transactionId, $amount, $reason),
                'epay' => $this->refundEpay($payment, $credentials, $transactionId, $amount),
                'tlync' => GatewayRefundResult::failed(
                    __('messages.gatewayRefundTlyncManual')
                ),
                default => GatewayRefundResult::skipped(),
            };
        } catch (Throwable $e) {
            Log::error('Gateway refund failed', [
                'payment_id' => $payment->id,
                'method' => $method,
                'error' => $e->getMessage(),
            ]);

            return GatewayRefundResult::failed($e->getMessage());
        }
    }

    private function credentials(Payment $payment): ?PaymentGatewayCredential
    {
        $payment->loadMissing('order.branch.restaurant.paymentGateways');

        return $payment->order?->branch?->restaurant?->paymentGateways;
    }

    private function currencyCode(Payment $payment): string
    {
        $payment->loadMissing('order.branch.restaurant.currency');

        return strtoupper((string) ($payment->order?->branch?->restaurant?->currency?->currency_code ?? 'USD'));
    }

    private function toMinorUnits(float $amount): int
    {
        return (int) round($amount * 100);
    }

    private function refundStripe(PaymentGatewayCredential $credentials, string $paymentIntentId, float $amount): GatewayRefundResult
    {
        $secret = $credentials->stripe_secret;

        if (! $secret) {
            return GatewayRefundResult::failed(__('messages.gatewayRefundMissingCredentials'));
        }

        $stripe = new \Stripe\StripeClient($secret);
        $refund = $stripe->refunds->create([
            'payment_intent' => $paymentIntentId,
            'amount' => $this->toMinorUnits($amount),
        ]);

        return GatewayRefundResult::success($refund->id ?? null);
    }

    private function refundRazorpay(PaymentGatewayCredential $credentials, string $paymentId, float $amount): GatewayRefundResult
    {
        $key = $credentials->razorpay_key;
        $secret = $credentials->razorpay_secret;

        if (! $key || ! $secret) {
            return GatewayRefundResult::failed(__('messages.gatewayRefundMissingCredentials'));
        }

        $api = new RazorpayApi($key, $secret);
        $refund = $api->payment->fetch($paymentId)->refund([
            'amount' => $this->toMinorUnits($amount),
        ]);

        $refundId = is_array($refund) ? ($refund['id'] ?? null) : ($refund->id ?? null);

        return GatewayRefundResult::success($refundId);
    }

    private function refundPaypal(
        Payment $payment,
        PaymentGatewayCredential $credentials,
        string $captureId,
        float $amount,
        string $reason
    ): GatewayRefundResult {
        $clientId = $credentials->paypal_payment_client_id;
        $secret = $credentials->paypal_payment_secret;

        if (! $clientId || ! $secret) {
            return GatewayRefundResult::failed(__('messages.gatewayRefundMissingCredentials'));
        }

        $apiBase = PaypalCurrency::apiBaseUrl($credentials->paypal_mode ?? 'sandbox');
        $currencyModel = $payment->order?->branch?->restaurant?->currency;
        $paypalAmount = PaypalCurrency::forPaypal($amount, $this->currencyCode($payment), $currencyModel);

        $response = Http::withBasicAuth($clientId, $secret)
            ->acceptJson()
            ->asJson()
            ->post("{$apiBase}/v2/payments/captures/{$captureId}/refund", [
                'amount' => [
                    'value' => PaypalCurrency::formatAmount($paypalAmount['amount'], $paypalAmount['currency']),
                    'currency_code' => $paypalAmount['currency'],
                ],
                'note_to_payer' => Str::limit($reason, 255),
            ]);

        if (! $response->successful()) {
            return GatewayRefundResult::failed(
                $response->json('message')
                    ?? $response->json('details.0.description')
                    ?? $response->body()
                    ?: 'PayPal refund failed'
            );
        }

        return GatewayRefundResult::success($response->json('id'));
    }

    private function refundFlutterwave(PaymentGatewayCredential $credentials, string $transactionRef, float $amount): GatewayRefundResult
    {
        $secret = $credentials->flutterwave_secret;

        if (! $secret) {
            return GatewayRefundResult::failed(__('messages.gatewayRefundMissingCredentials'));
        }

        $transactionId = $transactionRef;

        if (! ctype_digit((string) $transactionRef)) {
            $verify = Http::withToken($secret)
                ->acceptJson()
                ->get('https://api.flutterwave.com/v3/transactions/verify_by_reference', [
                    'tx_ref' => $transactionRef,
                ]);

            if (! $verify->successful() || ($verify->json('status') !== 'success')) {
                return GatewayRefundResult::failed(
                    $verify->json('message') ?? 'Unable to resolve Flutterwave transaction for refund'
                );
            }

            $transactionId = $verify->json('data.id');
        }

        $response = Http::withToken($secret)
            ->acceptJson()
            ->post("https://api.flutterwave.com/v3/transactions/{$transactionId}/refund", [
                'amount' => $amount,
            ]);

        if (! $response->successful() || ($response->json('status') !== 'success')) {
            return GatewayRefundResult::failed(
                $response->json('message') ?? 'Flutterwave refund failed'
            );
        }

        $refundId = $response->json('data.id') ?? $response->json('data.flw_ref');

        return GatewayRefundResult::success($refundId ? (string) $refundId : null);
    }

    private function refundPaystack(PaymentGatewayCredential $credentials, string $reference, float $amount): GatewayRefundResult
    {
        $secret = $credentials->paystack_secret_data;

        if (! $secret) {
            return GatewayRefundResult::failed(__('messages.gatewayRefundMissingCredentials'));
        }

        $response = Http::withToken($secret)
            ->acceptJson()
            ->post('https://api.paystack.co/refund', [
                'transaction' => $reference,
                'amount' => $this->toMinorUnits($amount),
            ]);

        if (! $response->successful() || ($response->json('status') !== true)) {
            return GatewayRefundResult::failed(
                $response->json('message') ?? 'Paystack refund failed'
            );
        }

        $refundId = $response->json('data.id') ?? $response->json('data.transaction.id');

        return GatewayRefundResult::success($refundId ? (string) $refundId : null);
    }

    private function refundPayfast(
        PaymentGatewayCredential $credentials,
        string $pfPaymentId,
        float $amount,
        string $reason
    ): GatewayRefundResult {
        $merchantId = $credentials->payfast_merchant_id_data;
        $passphrase = $credentials->payfast_passphrase_data;

        if (! $merchantId || ! $passphrase) {
            return GatewayRefundResult::failed(__('messages.gatewayRefundMissingCredentials'));
        }

        $timestamp = now()->format('Y-m-d\TH:i:sP');
        $body = [
            'amount' => (string) $this->toMinorUnits($amount),
            'reason' => Str::limit($reason, 255),
            'notify_buyer' => '1',
        ];

        $signaturePayload = array_merge([
            'merchant-id' => (string) $merchantId,
            'version' => 'v1',
            'timestamp' => $timestamp,
            'passphrase' => $passphrase,
        ], $body);

        ksort($signaturePayload);
        $signature = md5(http_build_query($signaturePayload));

        $url = 'https://api.payfast.co.za/refunds/' . $pfPaymentId;
        if (($credentials->payfast_mode ?? 'sandbox') === 'sandbox') {
            $url .= '?testing=true';
        }

        $response = Http::asForm()
            ->withHeaders([
                'merchant-id' => (string) $merchantId,
                'version' => 'v1',
                'timestamp' => $timestamp,
                'signature' => $signature,
            ])
            ->post($url, $body);

        if (! $response->successful()) {
            return GatewayRefundResult::failed(
                $response->json('data.response')
                    ?? $response->json('status')
                    ?? $response->body()
                    ?: 'PayFast refund failed'
            );
        }

        $refundId = $response->json('data.refund_id')
            ?? $response->json('data.id')
            ?? $pfPaymentId;

        return GatewayRefundResult::success($refundId ? (string) $refundId : null);
    }

    private function refundXendit(
        Payment $payment,
        PaymentGatewayCredential $credentials,
        string $transactionId,
        float $amount
    ): GatewayRefundResult {
        $secret = $credentials->xendit_secret_key;

        if (! $secret) {
            return GatewayRefundResult::failed(__('messages.gatewayRefundMissingCredentials'));
        }

        $xenditPayment = XenditPayment::query()
            ->where('order_id', $payment->order_id)
            ->where(function ($query) use ($transactionId) {
                $query->where('xendit_payment_id', $transactionId)
                    ->orWhere('xendit_external_id', $transactionId)
                    ->orWhere('xendit_invoice_id', $transactionId);
            })
            ->first();

        $invoiceId = $xenditPayment?->xendit_invoice_id
            ?: $xenditPayment?->xendit_payment_id
            ?: $transactionId;

        $payload = [
            'invoice_id' => $invoiceId,
            'amount' => $amount,
            'reason' => 'REQUESTED_BY_CUSTOMER',
            'reference_id' => 'refund_' . $payment->id . '_' . Str::random(8),
            'currency' => $this->currencyCode($payment),
        ];

        $response = Http::withBasicAuth($secret, '')
            ->acceptJson()
            ->post('https://api.xendit.co/refunds', $payload);

        if (! $response->successful()) {
            return GatewayRefundResult::failed(
                $response->json('message') ?? $response->body() ?: 'Xendit refund failed'
            );
        }

        return GatewayRefundResult::success($response->json('id'));
    }

    private function refundMollie(
        Payment $payment,
        PaymentGatewayCredential $credentials,
        string $molliePaymentId,
        float $amount
    ): GatewayRefundResult {
        $isSandbox = ($credentials->mollie_mode ?? 'test') === 'test';
        $apiKey = $isSandbox ? $credentials->test_mollie_key : $credentials->live_mollie_key;

        if (! $apiKey) {
            return GatewayRefundResult::failed(__('messages.gatewayRefundMissingCredentials'));
        }

        $mollie = new MollieApiClient();
        $mollie->setApiKey($apiKey);

        $molliePayment = $mollie->payments->get($molliePaymentId);
        $refund = $molliePayment->refund([
            'amount' => [
                'currency' => $this->currencyCode($payment),
                'value' => number_format($amount, 2, '.', ''),
            ],
        ]);

        return GatewayRefundResult::success($refund->id ?? null);
    }

    private function refundTap(
        Payment $payment,
        PaymentGatewayCredential $credentials,
        string $chargeId,
        float $amount,
        string $reason
    ): GatewayRefundResult {
        $secret = $credentials->tap_secret_key;

        if (! $secret) {
            return GatewayRefundResult::failed(__('messages.gatewayRefundMissingCredentials'));
        }

        $currency = $this->currencyCode($payment);

        $response = Http::withToken($secret)
            ->acceptJson()
            ->post('https://api.tap.company/v2/refunds', [
                'charge_id' => $chargeId,
                'amount' => $amount,
                'currency' => $currency,
                'description' => Str::limit($reason, 255),
                'reason' => 'requested_by_customer',
            ]);

        if (! $response->successful()) {
            return GatewayRefundResult::failed(
                $response->json('errors.0.description')
                    ?? $response->json('message')
                    ?? $response->body()
                    ?: 'Tap refund failed'
            );
        }

        return GatewayRefundResult::success($response->json('id'));
    }

    private function refundEpay(
        Payment $payment,
        PaymentGatewayCredential $credentials,
        string $transactionId,
        float $amount
    ): GatewayRefundResult {
        $isSandbox = ($credentials->epay_mode ?? 'sandbox') === 'sandbox';
        $clientId = $credentials->epay_client_id;
        $clientSecret = $credentials->epay_client_secret;

        if (! $clientId || ! $clientSecret) {
            return GatewayRefundResult::failed(__('messages.gatewayRefundMissingCredentials'));
        }

        $epayPayment = EpayPayment::query()
            ->where('order_id', $payment->order_id)
            ->where(function ($query) use ($transactionId) {
                $query->where('epay_payment_id', $transactionId)
                    ->orWhere('id', $transactionId);
            })
            ->first();

        $tokenUrl = $isSandbox
            ? 'https://test-epay-oauth.epayment.kz/oauth2/token'
            : 'https://epay-oauth.homebank.kz/oauth2/token';

        $tokenResponse = Http::asForm()->post($tokenUrl, [
            'grant_type' => 'client_credentials',
            'scope' => 'webapi usermanagement',
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
        ]);

        if (! $tokenResponse->successful()) {
            // Fall back to payment scope used during checkout
            $tokenResponse = Http::asForm()->post($tokenUrl, [
                'grant_type' => 'client_credentials',
                'scope' => 'payment',
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
            ]);
        }

        if (! $tokenResponse->successful()) {
            return GatewayRefundResult::failed(
                $tokenResponse->json('message') ?? 'Epay OAuth failed'
            );
        }

        $accessToken = $tokenResponse->json('access_token');
        $apiBase = $isSandbox
            ? 'https://test-epay-api.epayment.kz'
            : 'https://epay-api.homebank.kz';

        $operationId = $epayPayment?->epay_payment_id ?: $transactionId;
        $query = [
            'amount' => number_format($amount, 2, '.', ''),
            'externalID' => 'refund_' . $payment->id . '_' . Str::random(6),
        ];

        $response = Http::withToken($accessToken)
            ->acceptJson()
            ->post("{$apiBase}/operation/{$operationId}/refund?" . http_build_query($query));

        if (! $response->successful()) {
            return GatewayRefundResult::failed(
                $response->json('message')
                    ?? $response->body()
                    ?: 'Epay refund failed'
            );
        }

        $refundId = $response->json('id') ?? $response->json('transaction.id') ?? $operationId;

        return GatewayRefundResult::success($refundId ? (string) $refundId : null);
    }
}
