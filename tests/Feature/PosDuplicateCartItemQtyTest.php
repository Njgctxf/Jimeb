<?php

namespace Tests\Feature;

use App\Http\Controllers\PosAjaxController;
use App\Livewire\Pos\Pos;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Reproduces Order #1196-style bug: two POS cart taps of the same item
 * must accumulate qty/amount on save (not drop the second line while keeping cart total).
 */
class PosDuplicateCartItemQtyTest extends TestCase
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

    private function invokePrivate(object $object, string $method, array $args = [])
    {
        $reflection = new ReflectionMethod($object, $method);
        $reflection->setAccessible(true);

        return $reflection->invokeArgs($object, $args);
    }

    private function seedOrderWithMenuItem(): array
    {
        if (!Schema::hasTable('orders') || !Schema::hasTable('order_items') || !Schema::hasTable('menu_items')) {
            $this->markTestSkipped('Required tables missing');
        }

        $branchId = DB::table('menu_items')->whereNotNull('branch_id')->value('branch_id')
            ?? DB::table('orders')->whereNotNull('branch_id')->value('branch_id');
        $menuItemId = DB::table('menu_items')->where('branch_id', $branchId)->value('id')
            ?? DB::table('menu_items')->value('id');

        if (!$branchId || !$menuItemId) {
            $this->markTestSkipped('No branch/menu item available');
        }

        $now = now();
        $orderPayload = [
            'branch_id' => $branchId,
            'order_number' => 'QTY-TEST-' . uniqid(),
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

        return [
            'branch_id' => (int) $branchId,
            'menu_item_id' => (int) $menuItemId,
            'order_id' => (int) $orderId,
        ];
    }

    public function test_ajax_upsert_accumulates_duplicate_cart_lines(): void
    {
        $seed = $this->seedOrderWithMenuItem();
        $order = Order::find($seed['order_id']);
        $this->assertNotNull($order);

        $controller = new class extends PosAjaxController {
            public $branch;

            public function __construct()
            {
                // skip parent constructor
            }
        };
        $controller->branch = (object) ['id' => $seed['branch_id']];

        $kotMap = [];
        $touched = [];

        $line = [
            'branch_id' => $seed['branch_id'],
            'order_id' => $seed['order_id'],
            'menu_item_id' => $seed['menu_item_id'],
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
        if (Schema::hasColumn('order_items', 'is_free_item_from_stamp')) {
            // already set above
        } else {
            unset($line['is_free_item_from_stamp'], $line['stamp_rule_id']);
        }

        // Two POS cart taps of the same item (unique keys on the client).
        $this->invokePrivate($controller, 'upsertAjaxCartOrderItem', [
            $order,
            $line,
            [],
            null,
            &$kotMap,
            &$touched,
        ]);
        $this->invokePrivate($controller, 'upsertAjaxCartOrderItem', [
            $order,
            $line,
            [],
            null,
            &$kotMap,
            &$touched,
        ]);

        $items = OrderItem::where('order_id', $seed['order_id'])->get();
        $this->assertCount(1, $items, 'Duplicate cart taps should merge into one order_item');
        $this->assertSame(2, (int) $items->first()->quantity);
        $this->assertEquals(3.0, (float) $items->first()->amount);
        $this->assertEquals(
            (float) $order->fresh()->sub_total,
            (float) $items->sum('amount'),
            'Line amounts must match order sub_total'
        );
    }

    public function test_ajax_upsert_keeps_different_modifier_sets_separate(): void
    {
        if (!Schema::hasTable('modifier_options') || !Schema::hasTable('order_item_modifier_option')) {
            $this->markTestSkipped('Modifier tables missing');
        }

        $seed = $this->seedOrderWithMenuItem();
        $order = Order::find($seed['order_id']);

        $modifierIds = DB::table('modifier_options')->limit(2)->pluck('id')->map(fn ($id) => (int) $id)->all();
        if (count($modifierIds) < 2) {
            $this->markTestSkipped('Need at least 2 modifier options');
        }

        $controller = new class extends PosAjaxController {
            public $branch;

            public function __construct()
            {
            }
        };
        $controller->branch = (object) ['id' => $seed['branch_id']];

        $kotMap = [];
        $touched = [];
        $base = [
            'branch_id' => $seed['branch_id'],
            'order_id' => $seed['order_id'],
            'menu_item_id' => $seed['menu_item_id'],
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
            unset($base['is_free_item_from_stamp'], $base['stamp_rule_id']);
        }

        $this->invokePrivate($controller, 'upsertAjaxCartOrderItem', [
            $order,
            $base,
            [$modifierIds[0]],
            null,
            &$kotMap,
            &$touched,
        ]);
        $this->invokePrivate($controller, 'upsertAjaxCartOrderItem', [
            $order,
            $base,
            [$modifierIds[1]],
            null,
            &$kotMap,
            &$touched,
        ]);

        $items = OrderItem::where('order_id', $seed['order_id'])->get();
        $this->assertCount(2, $items, 'Different modifier sets must stay as separate lines');
        $this->assertEquals(3.0, (float) $items->sum('amount'));
    }

    public function test_livewire_upsert_accumulates_duplicate_cart_lines(): void
    {
        $seed = $this->seedOrderWithMenuItem();
        $order = Order::find($seed['order_id']);

        $pos = new class extends Pos {
            public function __construct()
            {
                // skip Livewire boot
            }
        };

        $touched = [];
        $attrs = [
            'order_id' => $seed['order_id'],
            'menu_item_id' => $seed['menu_item_id'],
            'menu_item_variation_id' => null,
            'quantity' => 1,
            'price' => 1.5,
            'amount' => 1.5,
            'is_free_item_from_stamp' => false,
            'stamp_rule_id' => null,
        ];
        if (Schema::hasColumn('order_items', 'branch_id')) {
            $attrs['branch_id'] = $seed['branch_id'];
        }
        if (!Schema::hasColumn('order_items', 'is_free_item_from_stamp')) {
            unset($attrs['is_free_item_from_stamp'], $attrs['stamp_rule_id']);
        }

        $this->invokePrivate($pos, 'upsertCartLineOrderItem', [$order, $attrs, [], &$touched]);
        $this->invokePrivate($pos, 'upsertCartLineOrderItem', [$order, $attrs, [], &$touched]);

        $items = OrderItem::where('order_id', $seed['order_id'])->get();
        $this->assertCount(1, $items);
        $this->assertSame(2, (int) $items->first()->quantity);
        $this->assertEquals(3.0, (float) $items->first()->amount);
    }
}
