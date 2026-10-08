<?php

namespace Tests\Unit;

use App\Livewire\CustomerDisplay;
use App\Models\User;
use App\Support\CustomerDisplayPayload;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CustomerDisplayRefreshTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_refresh_applies_pusher_payload_and_bumps_refresh_key(): void
    {
        $display = new CustomerDisplay();

        $display->refreshCustomerDisplay([
            'order_number' => '42',
            'formatted_order_number' => 'ORD-42',
            'items' => [
                ['name' => 'Burger', 'qty' => 2, 'price' => 10, 'total_unit_price' => 10],
            ],
            'sub_total' => 20,
            'total' => 20,
            'discount' => 0,
            'status' => 'idle',
            'order_type' => 'dine_in',
        ]);

        $this->assertSame(1, $display->refreshKey);
        $this->assertSame('42', $display->orderNumber);
        $this->assertSame('ORD-42', $display->formattedOrderNumber);
        $this->assertEquals(20, $display->subTotal);
        $this->assertEquals(20, $display->total);
        $this->assertSame('idle', $display->status);
        $this->assertSame('dine_in', $display->orderType);
        $this->assertCount(1, $display->orderItems);
        $this->assertSame('Burger', $display->orderItems[0]['name']);
    }

    public function test_refresh_unwraps_nested_order_data_from_pusher_event(): void
    {
        $display = new CustomerDisplay();

        $display->refreshCustomerDisplay([
            'order_data' => [
                'order_number' => '7',
                'formatted_order_number' => 'ORD-7',
                'items' => [
                    ['name' => 'Tea', 'qty' => 1, 'price' => 5, 'total_unit_price' => 5],
                ],
                'sub_total' => 5,
                'total' => 5,
                'status' => 'idle',
            ],
            'timestamp' => now()->toISOString(),
        ]);

        $this->assertSame('7', $display->orderNumber);
        $this->assertEquals(5, $display->total);
        $this->assertCount(1, $display->orderItems);
    }

    public function test_refresh_without_payload_loads_user_cache(): void
    {
        $user = new User();
        $user->id = 4242;
        $this->actingAs($user);

        Cache::put('customer_display_cart_user_4242', [
            'order_number' => '99',
            'formatted_order_number' => 'ORD-99',
            'items' => [
                ['name' => 'Pizza', 'qty' => 1, 'price' => 12, 'total_unit_price' => 12],
            ],
            'sub_total' => 12,
            'total' => 12,
            'status' => 'idle',
        ], now()->addMinutes(5));

        $display = new CustomerDisplay();
        $display->refreshCustomerDisplay();

        $this->assertSame('99', $display->orderNumber);
        $this->assertEquals(12, $display->total);
        $this->assertSame('Pizza', $display->orderItems[0]['name']);
        $this->assertSame(1, $display->refreshKey);
    }

    public function test_billed_payload_clears_item_rows(): void
    {
        $normalized = CustomerDisplayPayload::normalize([
            'order_number' => '1',
            'items' => [
                ['name' => 'Burger', 'qty' => 1, 'price' => 10],
            ],
            'sub_total' => 10,
            'total' => 10,
            'taxes' => [['name' => 'GST', 'amount' => 1]],
            'extra_charges' => [['name' => 'Service', 'amount' => 2]],
            'status' => 'billed',
            'cash_due' => 10,
        ]);

        $this->assertSame([], $normalized['items']);
        $this->assertSame([], $normalized['taxes']);
        $this->assertSame([], $normalized['extra_charges']);

        $display = new CustomerDisplay();
        $display->refreshCustomerDisplay($normalized);

        $this->assertSame('billed', $display->status);
        $this->assertSame([], $display->orderItems);
        $this->assertEquals(10, $display->cashDue);
    }

    public function test_empty_cart_payload_clears_totals(): void
    {
        $normalized = CustomerDisplayPayload::normalize([
            'order_number' => null,
            'items' => [],
            'sub_total' => 0,
            'total' => 0,
            'taxes' => [['name' => 'GST', 'amount' => 1]],
            'status' => 'idle',
        ]);

        $this->assertSame([], $normalized['items']);
        $this->assertEquals(0, $normalized['sub_total']);
        $this->assertEquals(0, $normalized['total']);
        $this->assertSame([], $normalized['taxes']);
    }
}
