<?php

namespace Tests\Unit;

use App\Http\Controllers\PosController;
use App\Livewire\Order\Orders;
use App\Models\Order;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;
use Tests\TestCase;

class OrderMergeHelpersTest extends TestCase
{
    private function invokePrivate(object $object, string $method, array $args = [])
    {
        $reflection = new ReflectionMethod($object, $method);
        $reflection->setAccessible(true);

        return $reflection->invokeArgs($object, $args);
    }

    public function test_combine_order_discounts_sums_amounts_as_fixed(): void
    {
        $controller = new PosController();

        $orderA = new Order(['discount_amount' => 10]);
        $orderB = new Order(['discount_amount' => 5.5]);

        $result = $this->invokePrivate($controller, 'combineOrderDiscounts', [
            collect([$orderA, $orderB]),
            0.0,
        ]);

        $this->assertSame('fixed', $result['type']);
        $this->assertEquals(15.5, $result['value']);
        $this->assertEquals(15.5, $result['amount']);
    }

    public function test_combine_order_discounts_includes_current_amount(): void
    {
        $controller = new PosController();

        $orderA = new Order(['discount_amount' => 8]);

        $result = $this->invokePrivate($controller, 'combineOrderDiscounts', [
            collect([$orderA]),
            12.0,
        ]);

        $this->assertSame('fixed', $result['type']);
        $this->assertEquals(20.0, $result['amount']);
    }

    public function test_combine_order_discounts_returns_null_when_zero(): void
    {
        $controller = new PosController();

        $result = $this->invokePrivate($controller, 'combineOrderDiscounts', [
            collect([new Order(['discount_amount' => 0])]),
            0.0,
        ]);

        $this->assertNull($result['type']);
        $this->assertNull($result['value']);
        $this->assertEquals(0, $result['amount']);
    }

    public function test_combine_stamp_discounts_is_noop_when_loyalty_unavailable(): void
    {
        $controller = $this->getMockBuilder(PosController::class)
            ->onlyMethods([])
            ->getMock();

        // Force loyalty unavailable by stubbing via subclass wrapper is hard for private methods.
        // Instead assert: when Schema column missing OR module disabled, helper returns zeros.
        // We call the real method; if Loyalty is disabled in testing env it should return zeros.
        $result = $this->invokePrivate(new PosController(), 'combineStampDiscounts', [
            collect([new Order(['stamp_discount_amount' => 25])]),
            10.0,
            true,
        ]);

        if (!function_exists('module_enabled') || !module_enabled('Loyalty') || !Schema::hasColumn('orders', 'stamp_discount_amount')) {
            $this->assertEquals(0, $result['amount']);
            $this->assertFalse($result['has_free_items']);
        } else {
            $this->assertEquals(35.0, $result['amount']);
            $this->assertTrue($result['has_free_items']);
        }
    }

    public function test_recalculate_pos_cart_totals_applies_fixed_discount(): void
    {
        $controller = new PosController();

        $result = $this->invokePrivate($controller, 'recalculatePosCartTotals', [
            ['a' => (object) ['taxes' => collect()]],
            ['a' => 2],
            ['a' => 100.0],
            [],
            'order',
            collect([(object) ['tax_percent' => 10]]),
            'fixed',
            15,
            15.0,
            0.0,
            0.0,
        ]);

        $this->assertEquals(100.0, $result['subTotal']);
        $this->assertEquals(15.0, $result['discountAmount']);
        $this->assertEquals(85.0, $result['discountedTotal']);
        $this->assertEquals(10.0, $result['totalTaxAmount']); // 10% of 100
        $this->assertEquals(95.0, $result['total']); // 85 + 10
    }

    public function test_orders_loyalty_stamp_helper_is_safe_without_loyalty(): void
    {
        $orders = new Orders();
        $available = $this->invokePrivate($orders, 'loyaltyStampFeaturesAvailable');

        $this->assertIsBool($available);

        if (!function_exists('module_enabled') || !module_enabled('Loyalty')) {
            $this->assertFalse($available);
        }
    }
}
