<?php

/**
 * Standalone verification for order/table merge discount + Loyalty-safe behavior.
 * Runs inside a DB transaction and always rolls back.
 */

use App\Http\Controllers\PosController;
use App\Livewire\Order\Orders;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$failed = 0;
$passed = 0;

function assert_true(bool $cond, string $msg): void
{
    global $failed, $passed;
    if ($cond) {
        echo "PASS  {$msg}\n";
        $passed++;
    } else {
        echo "FAIL  {$msg}\n";
        $failed++;
    }
}

function invoke_private(object $object, string $method, array $args = [])
{
    $ref = new ReflectionMethod($object, $method);
    $ref->setAccessible(true);
    return $ref->invokeArgs($object, $args);
}

echo "=== Order merge helper / smoke verification ===\n\n";

$controller = new PosController();

// 1) Regular discount combine
$result = invoke_private($controller, 'combineOrderDiscounts', [
    collect([
        new Order(['discount_amount' => 10]),
        new Order(['discount_amount' => 5.5]),
    ]),
    0.0,
]);
assert_true($result['type'] === 'fixed' && (float) $result['amount'] === 15.5, 'combineOrderDiscounts sums as fixed');

$result = invoke_private($controller, 'combineOrderDiscounts', [
    collect([new Order(['discount_amount' => 8])]),
    12.0,
]);
assert_true((float) $result['amount'] === 20.0, 'combineOrderDiscounts includes current amount');

$result = invoke_private($controller, 'combineOrderDiscounts', [
    collect([new Order(['discount_amount' => 0])]),
    0.0,
]);
assert_true($result['type'] === null && (float) $result['amount'] === 0.0, 'combineOrderDiscounts zero => null type');

// 2) Totals recalculation
$totals = invoke_private($controller, 'recalculatePosCartTotals', [
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
assert_true(
    (float) $totals['subTotal'] === 100.0
        && (float) $totals['discountAmount'] === 15.0
        && (float) $totals['discountedTotal'] === 85.0
        && (float) $totals['totalTaxAmount'] === 10.0
        && (float) $totals['total'] === 95.0,
    'recalculatePosCartTotals applies fixed discount + order tax'
);

// 3) Loyalty stamp helper safety
$ordersComponent = new Orders();
$loyaltyAvailable = invoke_private($ordersComponent, 'loyaltyStampFeaturesAvailable');
assert_true(is_bool($loyaltyAvailable), 'loyaltyStampFeaturesAvailable returns bool');

$stamp = invoke_private($controller, 'combineStampDiscounts', [
    collect([new Order(['stamp_discount_amount' => 25])]),
    10.0,
    true,
]);
if (!$loyaltyAvailable) {
    assert_true((float) $stamp['amount'] === 0.0 && $stamp['has_free_items'] === false, 'combineStampDiscounts is noop when Loyalty inactive');
} else {
    assert_true((float) $stamp['amount'] === 35.0 && $stamp['has_free_items'] === true, 'combineStampDiscounts sums when Loyalty active');
}

// 4) DB smoke: Orders::mergeOrders (rolled back)
DB::beginTransaction();
try {
    $branchId = DB::table('orders')->value('branch_id');
    $menuItemId = DB::table('menu_items')->where('branch_id', $branchId)->value('id')
        ?? DB::table('menu_items')->value('id');

    if (!$branchId || !$menuItemId) {
        echo "SKIP  Orders merge DB smoke (no branch/menu item)\n";
    } else {
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

        $item = [
            'order_id' => $baseId,
            'menu_item_id' => $menuItemId,
            'quantity' => 1,
            'price' => 100,
            'amount' => 100,
            'created_at' => $now,
            'updated_at' => $now,
        ];
        if (Schema::hasColumn('order_items', 'is_free_item_from_stamp')) {
            $item['is_free_item_from_stamp'] = false;
        }
        DB::table('order_items')->insert($item);
        $item['order_id'] = $otherId;
        $item['price'] = 50;
        $item['amount'] = 50;
        DB::table('order_items')->insert($item);

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

        $component = new Orders();
        $component->selectedOrders = [$baseId, $otherId];
        $component->mergeOrders();

        $base = Order::find($baseId);
        $otherGone = Order::find($otherId) === null;

        assert_true($base !== null, 'Orders merge keeps base order');
        assert_true($otherGone, 'Orders merge deletes secondary order');
        assert_true($base && (float) $base->sub_total === 150.0, 'Orders merge sub_total = 150');
        assert_true($base && (float) $base->discount_amount === 15.0, 'Orders merge discount_amount = 15');
        assert_true($base && $base->discount_type === 'fixed', 'Orders merge discount_type is fixed (not first-order percent)');
        assert_true($base && (float) $base->discount_value === 15.0, 'Orders merge discount_value = 15');
        assert_true($base && (float) $base->total === 135.0, 'Orders merge total = 135');
    }
} catch (Throwable $e) {
    echo 'FAIL  Orders merge DB smoke exception: ' . $e->getMessage() . "\n";
    $failed++;
} finally {
    DB::rollBack();
}

// 5) POS merge session stores IDs (not models)
DB::beginTransaction();
try {
    $branchId = DB::table('orders')->value('branch_id') ?? DB::table('branches')->value('id');
    $tableId = DB::table('tables')->where('branch_id', $branchId)->value('id')
        ?? DB::table('tables')->value('id');
    $menuItemId = DB::table('menu_items')->where('branch_id', $branchId)->value('id')
        ?? DB::table('menu_items')->value('id');

    if (!$branchId || !$tableId || !$menuItemId) {
        echo "SKIP  POS mergeTables session smoke (no branch/table/menu)\n";
    } else {
        $now = now();
        $tempOrderId = DB::table('orders')->insertGetId([
            'branch_id' => $branchId,
            'table_id' => $tableId,
            'order_number' => 'MERGE-POS-' . uniqid(),
            'date_time' => $now,
            'status' => 'kot',
            'order_status' => 'placed',
            'sub_total' => 20,
            'total' => 20,
            'discount_amount' => 0,
            'total_tax_amount' => 0,
            'tip_amount' => 0,
            'delivery_fee' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $refClass = new ReflectionClass(\App\Http\Controllers\PosAjaxController::class);
        $controller = $refClass->newInstanceWithoutConstructor();
        $branchProp = $refClass->getProperty('branch');
        $branchProp->setAccessible(true);
        $branchProp->setValue($controller, (object) ['id' => $branchId]);

        $request = Illuminate\Http\Request::create('/ajax/pos/merge-tables', 'POST', [
            'table_ids' => [$tableId],
            'current_table_id' => null,
        ]);

        $response = $controller->mergeTables($request);
        $payload = $response->getData(true);

        assert_true(($payload['success'] ?? false) === true, 'mergeTables returns success');
        $mergeData = session('pos_merge_data');
        assert_true(is_array($mergeData) && !empty($mergeData['orders_to_merge']), 'mergeTables stores session payload');

        $allIds = true;
        foreach ($mergeData['orders_to_merge'] as $ref) {
            if ((!is_int($ref) && !is_numeric($ref)) || $ref instanceof Order) {
                $allIds = false;
                break;
            }
        }
        assert_true($allIds, 'mergeTables stores order IDs (not Eloquent models)');
        assert_true(in_array((int) $tempOrderId, array_map('intval', $mergeData['orders_to_merge']), true), 'mergeTables includes temp unpaid order id');
        session()->forget('pos_merge_data');
    }
} catch (Throwable $e) {
    echo 'FAIL  POS mergeTables smoke exception: ' . $e->getMessage() . "\n";
    $failed++;
} finally {
    DB::rollBack();
}

// 6) Loyalty inactive: stamp fields must not break merge write path
DB::beginTransaction();
try {
    $branchId = DB::table('orders')->value('branch_id');
    $menuItemId = DB::table('menu_items')->where('branch_id', $branchId)->value('id')
        ?? DB::table('menu_items')->value('id');

    if (!$branchId || !$menuItemId) {
        echo "SKIP  Loyalty-off merge smoke\n";
    } else {
        $now = now();
        $mk = function (array $extra) use ($branchId, $now) {
            return array_merge([
                'branch_id' => $branchId,
                'order_number' => 'MERGE-LOY-' . uniqid(),
                'date_time' => $now,
                'status' => 'kot',
                'order_status' => 'placed',
                'sub_total' => 40,
                'total' => 40,
                'discount_amount' => 0,
                'total_tax_amount' => 0,
                'tip_amount' => 0,
                'delivery_fee' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ], $extra);
        };

        $a = DB::table('orders')->insertGetId($mk(['sub_total' => 40, 'total' => 40]));
        $b = DB::table('orders')->insertGetId($mk(['sub_total' => 30, 'total' => 30]));

        foreach ([$a => 40, $b => 30] as $oid => $amt) {
            $row = [
                'order_id' => $oid,
                'menu_item_id' => $menuItemId,
                'quantity' => 1,
                'price' => $amt,
                'amount' => $amt,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            DB::table('order_items')->insert($row);
            $kotId = DB::table('kots')->insertGetId([
                'order_id' => $oid,
                'kot_number' => 'K-L-' . uniqid(),
                'status' => 'in_kitchen',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            DB::table('kot_items')->insert([
                'kot_id' => $kotId,
                'menu_item_id' => $menuItemId,
                'quantity' => 1,
                'status' => 'pending',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // Force Loyalty-off path by temporarily mocking via subclass helper result:
        // Call merge after stubbing private method through anonymous extension is hard,
        // so we verify the public path does not throw even when stamp columns exist
        // and Loyalty may be active/inactive — merge must complete either way.
        $component = new Orders();
        $component->selectedOrders = [$a, $b];
        $component->mergeOrders();

        $base = Order::find($a);
        assert_true($base !== null && Order::find($b) === null, 'Loyalty-safe merge completes without exception');
        assert_true($base && (float) $base->sub_total === 70.0, 'Loyalty-safe merge sub_total correct');
        assert_true($base && (float) $base->total === 70.0, 'Loyalty-safe merge total correct');
    }
} catch (Throwable $e) {
    echo 'FAIL  Loyalty-off merge smoke exception: ' . $e->getMessage() . "\n";
    $failed++;
} finally {
    DB::rollBack();
}

echo "\n=== Results: {$passed} passed, {$failed} failed ===\n";
exit($failed > 0 ? 1 : 0);
