<?php

namespace Tests\Unit;

use App\Models\Branch;
use App\Models\Currency;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentGatewayCredential;
use App\Models\Restaurant;
use App\Services\Refunds\GatewayRefundResult;
use App\Services\Refunds\GatewayRefundService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GatewayRefundServiceTest extends TestCase
{
    private GatewayRefundService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new GatewayRefundService();
    }

    public function test_cash_and_waste_are_not_gateway_refunds(): void
    {
        $cash = $this->makePayment('cash', 'txn_1');
        $stripe = $this->makePayment('stripe', 'pi_123');

        $this->assertFalse($this->service->shouldRefundOnGateway($cash, 'full'));
        $this->assertFalse($this->service->shouldRefundOnGateway($stripe, 'waste'));
        $this->assertTrue($this->service->shouldRefundOnGateway($stripe, 'full'));
        $this->assertTrue($this->service->shouldRefundOnGateway($stripe, 'partial'));
    }

    public function test_cash_refund_is_skipped(): void
    {
        $result = $this->service->refund($this->makePayment('cash'), 10.00);

        $this->assertTrue($result->isSkipped());
        $this->assertSame(GatewayRefundResult::STATUS_SKIPPED, $result->status);
    }

    public function test_gateway_payment_without_transaction_id_fails_closed(): void
    {
        $payment = $this->makePayment('stripe', null);

        $result = $this->service->refund($payment, 25.00);

        $this->assertTrue($result->isFailed());
        $this->assertStringContainsString('transaction ID', (string) $result->errorMessage);
    }

    public function test_tlync_refund_fails_with_manual_message(): void
    {
        $result = $this->service->refund($this->makePayment('tlync', 'txn_abc'), 15.00);

        $this->assertTrue($result->isFailed());
        $this->assertStringContainsString('T-Lync', (string) $result->errorMessage);
    }

    public function test_paypal_refund_success_returns_gateway_refund_id(): void
    {
        Http::fake([
            'https://api-m.sandbox.paypal.com/v2/payments/captures/*/refund' => Http::response([
                'id' => 'REFUND-PAYPAL-1',
                'status' => 'COMPLETED',
            ], 201),
        ]);

        $payment = $this->makePayment('paypal', 'CAPTURE-123', [
            'paypal_mode' => 'sandbox',
            'sandbox_paypal_client_id' => 'client',
            'sandbox_paypal_secret' => 'secret',
        ]);

        $result = $this->service->refund($payment, 12.50, 'Customer request');

        $this->assertTrue($result->isSuccess());
        $this->assertSame('REFUND-PAYPAL-1', $result->gatewayRefundId);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/v2/payments/captures/CAPTURE-123/refund')
                && $request['amount']['value'] === '12.50'
                && $request['amount']['currency_code'] === 'USD';
        });
    }

    public function test_paypal_refund_api_error_fails_closed(): void
    {
        Http::fake([
            'https://api-m.sandbox.paypal.com/*' => Http::response([
                'message' => 'Capture already refunded',
            ], 422),
        ]);

        $payment = $this->makePayment('paypal', 'CAPTURE-456', [
            'paypal_mode' => 'sandbox',
            'sandbox_paypal_client_id' => 'client',
            'sandbox_paypal_secret' => 'secret',
        ]);

        $result = $this->service->refund($payment, 10.00);

        $this->assertTrue($result->isFailed());
        $this->assertStringContainsString('Capture already refunded', (string) $result->errorMessage);
    }

    public function test_paystack_refund_success(): void
    {
        Http::fake([
            'https://api.paystack.co/refund' => Http::response([
                'status' => true,
                'message' => 'Refund has been queued for processing',
                'data' => ['id' => 999],
            ], 200),
        ]);

        $payment = $this->makePayment('paystack', 'psk_ref_1', [
            'paystack_mode' => 'sandbox',
            'test_paystack_secret' => 'sk_test_xxx',
        ]);

        $result = $this->service->refund($payment, 20.00);

        $this->assertTrue($result->isSuccess());
        $this->assertSame('999', $result->gatewayRefundId);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.paystack.co/refund'
                && $request['transaction'] === 'psk_ref_1'
                && $request['amount'] === 2000;
        });
    }

    public function test_missing_credentials_fail_closed(): void
    {
        $payment = $this->makePayment('stripe', 'pi_abc', [
            'stripe_secret' => null,
        ]);

        $result = $this->service->refund($payment, 5.00);

        $this->assertTrue($result->isFailed());
    }

    public function test_apply_order_refund_decrements_amount_paid_and_sets_payment_due(): void
    {
        $order = \Mockery::mock(Order::class)->makePartial();
        $order->amount_paid = 100.00;
        $order->total = 100.00;
        $order->status = 'paid';
        $order->shouldReceive('save')->once()->andReturn(true);

        $this->service->applyOrderRefund($order, 100.00, 'full');

        $this->assertEquals(0.0, $order->amount_paid);
        $this->assertSame('payment_due', $order->status);
    }

    public function test_apply_order_refund_skips_waste(): void
    {
        $order = \Mockery::mock(Order::class)->makePartial();
        $order->amount_paid = 100.00;
        $order->total = 100.00;
        $order->status = 'paid';
        $order->shouldReceive('save')->never();

        $this->service->applyOrderRefund($order, 100.00, 'waste');

        $this->assertSame(100.00, $order->amount_paid);
        $this->assertSame('paid', $order->status);
    }

    public function test_apply_order_refund_partial_keeps_paid_when_still_covered(): void
    {
        $order = \Mockery::mock(Order::class)->makePartial();
        $order->amount_paid = 100.00;
        $order->total = 80.00;
        $order->status = 'paid';
        $order->shouldReceive('save')->once()->andReturn(true);

        $this->service->applyOrderRefund($order, 10.00, 'partial');

        $this->assertEquals(90.0, $order->amount_paid);
        $this->assertSame('paid', $order->status);
    }

    /**
     * @param  array<string, mixed>  $gatewayAttributes
     */
    private function makePayment(string $method, ?string $transactionId = 'txn_1', array $gatewayAttributes = []): Payment
    {
        $currency = new Currency();
        $currency->currency_code = 'USD';

        $credentials = new PaymentGatewayCredential();
        foreach ($gatewayAttributes as $key => $value) {
            $credentials->setAttribute($key, $value);
        }

        $restaurant = new Restaurant();
        $restaurant->setRelation('paymentGateways', $credentials);
        $restaurant->setRelation('currency', $currency);

        $branch = new Branch();
        $branch->setRelation('restaurant', $restaurant);

        $order = new Order([
            'total' => 100,
            'amount_paid' => 100,
            'status' => 'paid',
        ]);
        $order->setRelation('branch', $branch);

        $payment = new Payment([
            'payment_method' => $method,
            'transaction_id' => $transactionId,
            'amount' => 50,
            'order_id' => 1,
        ]);
        $payment->setRelation('order', $order);

        return $payment;
    }
}
