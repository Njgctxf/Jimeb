<?php

namespace Tests\Feature;

use App\Http\Controllers\PosAjaxController;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Smoke tests for POS table merge / order merge discount behavior.
 * Uses the live DB inside a transaction that is always rolled back.
 */
class OrderMergeSmokeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    public function test_merge_tables_stores_order_ids_not_eloquent_models(): void
    {
        if (!Schema::hasTable('orders') || !Schema::hasTable('tables')) {
            $this->markTestSkipped('Required tables missing');
        }

        $branchId = DB::table('orders')->whereNotNull('branch_id')->value('branch_id');
        $tableId = DB::table('orders')
            ->where('branch_id', $branchId)
            ->whereNotNull('table_id')
            ->where('status', '!=', 'paid')
            ->where('status', '!=', 'canceled')
            ->value('table_id');

        if (!$branchId || !$tableId) {
            $this->markTestSkipped('No unpaid table order available for merge smoke test');
        }

        $controller = new class extends PosAjaxController {
            public $branch;
            public function __construct()
            {
                // skip parent constructor
            }
        };
        $controller->branch = (object) ['id' => $branchId];

        $request = Request::create('/ajax/pos/merge-tables', 'POST', [
            'table_ids' => [$tableId],
            'current_table_id' => null,
            'order_type_id' => null,
        ]);

        $response = $controller->mergeTables($request);
        $payload = $response->getData(true);

        $this->assertTrue($payload['success'] ?? false, json_encode($payload));
        $this->assertTrue($payload['data']['reload_required'] ?? false);

        $mergeData = session('pos_merge_data');
        $this->assertIsArray($mergeData);
        $this->assertNotEmpty($mergeData['orders_to_merge']);

        foreach ($mergeData['orders_to_merge'] as $orderRef) {
            $this->assertTrue(is_int($orderRef) || is_numeric($orderRef), 'Expected order IDs in session, got: ' . gettype($orderRef));
            $this->assertFalse($orderRef instanceof Order, 'Session must not store Eloquent Order models');
        }

        session()->forget('pos_merge_data');
    }

    public function test_orders_merge_combines_regular_discounts_without_requiring_loyalty(): void
    {
        if (!Schema::hasTable('orders') || !Schema::hasTable('order_items')) {
            $this->markTestSkipped('Required tables missing');
        }

        $branchId = DB::table('orders')->value('branch_id');
        $menuItemId = DB::table('menu_items')->where('branch_id', $branchId)->value('id')
            ?? DB::table('menu_items')->value('id');

        if (!$branchId || !$menuItemId) {
            $this->markTestSkipped('No branch/menu item available');
        }

        $now = now();
        $basePayload = [
            'branch_id' => $branchId,
            'order_number' => 'MERGE-TEST-A-' . uniqid(),
            'date_time' => $now,
            'status' => 'kot',
            'order_status' => 'placed',
            'sub_total' => 100,
            'total' => 90,
            'discount_amount' => 10,
            'discount_type' => 'percent',
            'discount_value' => 10,
            'total_tax_amount' => 0,
            'tip_amount' => 0,
            'delivery_fee' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ];
        if (Schema::hasColumn('orders', 'stamp_discount_amount')) {
            $basePayload['stamp_discount_amount'] = 0;
        }

        $baseId = DB::table('orders')->insertGetId($basePayload);

        $otherPayload = $basePayload;
        $otherPayload['order_number'] = 'MERGE-TEST-B-' . uniqid();
        $otherPayload['sub_total'] = 50;
        $otherPayload['total'] = 45;
        $otherPayload['discount_amount'] = 5;
        $otherPayload['discount_type'] = 'fixed';
        $otherPayload['discount_value'] = 5;
        $otherId = DB::table('orders')->insertGetId($otherPayload);

        $itemPayload = [
            'order_id' => $baseId,
            'menu_item_id' => $menuItemId,
            'quantity' => 1,
            'price' => 100,
            'amount' => 100,
            'created_at' => $now,
            'updated_at' => $now,
        ];
        if (Schema::hasColumn('order_items', 'is_free_item_from_stamp')) {
            $itemPayload['is_free_item_from_stamp'] = false;
        }
        DB::table('order_items')->insert($itemPayload);

        $itemPayload['order_id'] = $otherId;
        $itemPayload['price'] = 50;
        $itemPayload['amount'] = 50;
        DB::table('order_items')->insert($itemPayload);

        $baseKotId = DB::table('kots')->insertGetId([
            'order_id' => $baseId,
            'kot_number' => 'K-A-' . uniqid(),
            'status' => 'in_kitchen',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $otherKotId = DB::table('kots')->insertGetId([
            'order_id' => $otherId,
            'kot_number' => 'K-B-' . uniqid(),
            'status' => 'in_kitchen',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('kot_items')->insert([
            [
                'kot_id' => $baseKotId,
                'menu_item_id' => $menuItemId,
                'quantity' => 1,
                'status' => 'pending',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'kot_id' => $otherKotId,
                'menu_item_id' => $menuItemId,
                'quantity' => 1,
                'status' => 'pending',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        $component = new \App\Livewire\Order\Orders();
        $component->selectedOrders = [$baseId, $otherId];
        $component->mergeOrders();

        $base = Order::find($baseId);
        $this->assertNotNull($base, 'Base order should still exist after merge');
        $this->assertNull(Order::find($otherId), 'Merged order should be deleted');

        $this->assertEquals(150.0, (float) $base->sub_total);
        $this->assertEquals(15.0, (float) $base->discount_amount);
        $this->assertSame('fixed', $base->discount_type);
        $this->assertEquals(15.0, (float) $base->discount_value);
        $this->assertEquals(135.0, (float) $base->total);
    }
}
