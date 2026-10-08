<?php

namespace Tests\Unit;

use Modules\Inventory\Entities\InventoryItem;
use Modules\Inventory\Entities\Unit;
use Modules\Inventory\Services\PackSizeConverter;
use Tests\TestCase;

class PackSizeConverterTest extends TestCase
{
    private function whiskeyItem(): InventoryItem
    {
        $item = new InventoryItem([
            'unit_id' => 1,
            'purchase_unit_id' => 2,
            'units_per_purchase' => 750,
        ]);

        $item->setRelation('unit', new Unit(['name' => 'Milliliter', 'symbol' => 'ml']));
        $item->setRelation('purchaseUnit', new Unit(['name' => 'Bottle', 'symbol' => 'btl']));

        return $item;
    }

    private function waterItem(): InventoryItem
    {
        $item = new InventoryItem([
            'unit_id' => 3,
            'purchase_unit_id' => 3,
            'units_per_purchase' => 1,
        ]);

        $item->setRelation('unit', new Unit(['name' => 'Piece', 'symbol' => 'pc']));
        $item->setRelation('purchaseUnit', new Unit(['name' => 'Piece', 'symbol' => 'pc']));

        return $item;
    }

    public function test_bottle_receive_converts_to_millilitres(): void
    {
        $item = $this->whiskeyItem();

        $this->assertSame(9000.0, PackSizeConverter::toStockQuantity($item, 12));
        $this->assertSame(0.04, PackSizeConverter::toStockUnitPrice($item, 30));
        $this->assertSame(6.0, PackSizeConverter::toPurchaseQuantity($item, 4500));
    }

    public function test_retail_piece_is_noop_when_pack_size_is_one(): void
    {
        $item = $this->waterItem();

        $this->assertFalse(PackSizeConverter::hasPackSize($item));
        $this->assertSame(24.0, PackSizeConverter::toStockQuantity($item, 24));
        $this->assertSame(12.5, PackSizeConverter::toStockUnitPrice($item, 12.5));
        $this->assertNull(PackSizeConverter::packSizeLabel($item));
        $this->assertNull(PackSizeConverter::pourHint($item, 1));
    }

    public function test_pour_hint_and_stock_display_for_shots(): void
    {
        $item = $this->whiskeyItem();

        $this->assertTrue(PackSizeConverter::hasPackSize($item));
        $this->assertSame('1 btl = 750 ml', PackSizeConverter::packSizeLabel($item));
        $this->assertSame('4500 ml (6 btl)', PackSizeConverter::formatStockWithPurchaseEquivalent($item, 4500));
        $this->assertSame(
            '100 ml ≈ 0.133 btl · ~7 servings per 750 ml',
            PackSizeConverter::pourHint($item, 100)
        );
    }

    public function test_case_pack_for_water(): void
    {
        $item = new InventoryItem([
            'unit_id' => 3,
            'purchase_unit_id' => 4,
            'units_per_purchase' => 24,
        ]);
        $item->setRelation('unit', new Unit(['name' => 'Piece', 'symbol' => 'pc']));
        $item->setRelation('purchaseUnit', new Unit(['name' => 'Case', 'symbol' => 'case']));

        $this->assertSame(48.0, PackSizeConverter::toStockQuantity($item, 2));
        $this->assertSame('1 case = 24 pc', PackSizeConverter::packSizeLabel($item));
        $this->assertSame('48 pc (2 case)', PackSizeConverter::formatStockWithPurchaseEquivalent($item, 48));
    }
}
