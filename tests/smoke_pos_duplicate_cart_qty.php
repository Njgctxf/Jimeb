<?php

/**
 * Smoke test: duplicate POS cart taps must accumulate qty/amount on save
 * (Order #1196: item 1.500 shown, total 3.000).
 *
 * Usage: php tests/smoke_pos_duplicate_cart_qty.php
 */

use App\Http\Controllers\PosAjaxController;
use App\Livewire\Pos\Pos;
use App\Models\Order;
use App\Models\OrderItem;
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

echo "=== POS duplicate cart qty smoke ===\n\n";

if (!Schema::hasTable('orders') || !Schema::hasTable('order_items') || !Schema::hasTable('menu_items')) {
    echo "SKIP  required tables missing\n";
    exit(0);
}

$branchId = DB::table('menu_items')->whereNotNull('branch_id')->value('branch_id')
    ?? DB::table('orders')->whereNotNull('branch_id')->value('branch_id');
$menuItemId = DB::table('menu_items')->where('branch_id', $branchId)->value('id')
    ?? DB::table('menu_items')->value('id');

if (!$branchId || !$menuItemId) {
    echo "SKIP  no branch/menu item\n";
    exit(0);
}

DB::beginTransaction();

try {
    $now = now();
    $orderPayload = [
        'branch_id' => $branchId,
        'order_number' => 'QTY-SMOKE-' . uniqid(),
        'date_time' => $now,
        'status' => 'billed',
        'order_status' => 'placed',
        'sub_total' => 3.0,
        'total' => 3.0,
        'discount_amount' => 0,
        'total_tax_amount' => 0,
        'tip_amount' => 0,
        'delivery_fee' => 0,
        'created_at' => $now,
        'updated_at' => $now,
    ];
    if (Schema::hasColumn('orders', 'stamp_discount_amount')) {
        $orderPayload['stamp_discount_amount'] = 0;
    }
    $orderId = DB::table('orders')->insertGetId($orderPayload);
    $order = Order::find($orderId);
    assert_true((bool) $order, "seeded order #{$orderId}");

    $line = [
        'branch_id' => (int) $branchId,
        'order_id' => (int) $orderId,
        'menu_item_id' => (int) $menuItemId,
        'menu_item_variation_id' => null,
        'quantity' => 1,
        'price' => 1.5,
        'amount' => 1.5,
        'note' => null,
        'tax_amount' => 0,
        'tax_percentage' => 0,
        'tax_breakup' => null,
        'is_free_item_from_stamp' => false,
        'stamp_rule_id' => null,
    ];
    if (!Schema::hasColumn('order_items', 'is_free_item_from_stamp')) {
        unset($line['is_free_item_from_stamp'], $line['stamp_rule_id']);
    }

    // --- Ajax path (main POS) ---
    $ajax = new class extends PosAjaxController {
        public $branch;

        public function __construct()
        {
        }
    };
    $ajax->branch = (object) ['id' => (int) $branchId];

    $kotMap = [];
    $touched = [];
    invoke_private($ajax, 'upsertAjaxCartOrderItem', [$order, $line, [], null, &$kotMap, &$touched]);
    invoke_private($ajax, 'upsertAjaxCartOrderItem', [$order, $line, [], null, &$kotMap, &$touched]);

    $ajaxItems = OrderItem::where('order_id', $orderId)->get();
    assert_true($ajaxItems->count() === 1, 'ajax: one merged order_item (was undercount bug)');
    assert_true((int) $ajaxItems->first()->quantity === 2, 'ajax: qty=2 after two cart taps');
    assert_true(abs((float) $ajaxItems->first()->amount - 3.0) < 0.001, 'ajax: amount=3.000');
    assert_true(
        abs((float) $ajaxItems->sum('amount') - (float) $order->fresh()->sub_total) < 0.001,
        'ajax: line sum matches order sub_total 3.000'
    );

    // Clean items for Livewire path
    OrderItem::where('order_id', $orderId)->delete();

    // --- Livewire path ---
    $pos = new class extends Pos {
        public function __construct()
        {
        }
    };
    $touchedLw = [];
    $attrs = [
        'order_id' => (int) $orderId,
        'menu_item_id' => (int) $menuItemId,
        'menu_item_variation_id' => null,
        'quantity' => 1,
        'price' => 1.5,
        'amount' => 1.5,
        'is_free_item_from_stamp' => false,
        'stamp_rule_id' => null,
    ];
    if (Schema::hasColumn('order_items', 'branch_id')) {
        $attrs['branch_id'] = (int) $branchId;
    }
    if (!Schema::hasColumn('order_items', 'is_free_item_from_stamp')) {
        unset($attrs['is_free_item_from_stamp'], $attrs['stamp_rule_id']);
    }

    invoke_private($pos, 'upsertCartLineOrderItem', [$order, $attrs, [], &$touchedLw]);
    invoke_private($pos, 'upsertCartLineOrderItem', [$order, $attrs, [], &$touchedLw]);

    $lwItems = OrderItem::where('order_id', $orderId)->get();
    assert_true($lwItems->count() === 1, 'livewire: one merged order_item');
    assert_true((int) $lwItems->first()->quantity === 2, 'livewire: qty=2');
    assert_true(abs((float) $lwItems->first()->amount - 3.0) < 0.001, 'livewire: amount=3.000');

    // --- Different modifiers stay separate (ajax) ---
    if (Schema::hasTable('modifier_options') && Schema::hasTable('order_item_modifier_option')) {
        $modifierIds = DB::table('modifier_options')->limit(2)->pluck('id')->map(fn ($id) => (int) $id)->all();
        if (count($modifierIds) >= 2) {
            OrderItem::where('order_id', $orderId)->delete();
            $kotMap2 = [];
            $touched2 = [];
            invoke_private($ajax, 'upsertAjaxCartOrderItem', [$order, $line, [$modifierIds[0]], null, &$kotMap2, &$touched2]);
            invoke_private($ajax, 'upsertAjaxCartOrderItem', [$order, $line, [$modifierIds[1]], null, &$kotMap2, &$touched2]);
            $modItems = OrderItem::where('order_id', $orderId)->get();
            assert_true($modItems->count() === 2, 'ajax: different modifiers stay as 2 lines');
            assert_true(abs((float) $modItems->sum('amount') - 3.0) < 0.001, 'ajax: modifier lines sum to 3.000');
        } else {
            echo "SKIP  need 2 modifier options for modifier separation check\n";
        }
    } else {
        echo "SKIP  modifier tables missing\n";
    }
} catch (Throwable $e) {
    echo 'ERROR  ' . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    $failed++;
} finally {
    DB::rollBack();
}

echo "\n{$passed} passed, {$failed} failed\n";
exit($failed > 0 ? 1 : 0);
