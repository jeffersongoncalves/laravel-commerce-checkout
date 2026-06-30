<?php

use JeffersonGoncalves\Commerce\Checkout\Services\ReturnService;
use JeffersonGoncalves\Commerce\Inventory\Models\InventoryItem;
use JeffersonGoncalves\Commerce\Inventory\Models\InventoryLevel;
use JeffersonGoncalves\Commerce\Order\Models\Order;
use JeffersonGoncalves\Commerce\Product\Models\ProductVariant;

function seedReturnable(string $sku, int $stocked): ProductVariant
{
    $variant = ProductVariant::factory()->create(['sku' => $sku]);
    $item = InventoryItem::factory()->create(['sku' => $sku]);
    InventoryLevel::factory()->create([
        'inventory_item_id' => $item->id,
        'location_id' => 'sloc_main',
        'stocked_quantity' => $stocked,
        'reserved_quantity' => 0,
    ]);

    return $variant;
}

it('processes a return: restocks inventory and records the refund', function () {
    $variant = seedReturnable('SKU-RET-1', 10);

    $order = Order::factory()->create(['currency_code' => 'usd']);
    $line = $order->items()->create([
        'title' => 'X',
        'quantity' => 3,
        'unit_price' => 1000,
        'product_id' => $variant->product_id,
        'variant_id' => $variant->id,
    ]);

    $return = (new ReturnService)->create($order->id, [$line->id => 2], refundAmount: 2000, locationId: 'sloc_main');
    $return->load('items');

    $level = InventoryLevel::query()
        ->where('inventory_item_id', InventoryItem::query()->where('sku', 'SKU-RET-1')->value('id'))
        ->first();

    expect($return->status)->toBe('received')
        ->and($return->refund_amount)->toBe(2000)
        ->and($return->items)->toHaveCount(1)
        ->and($level->stocked_quantity)->toBe(12)
        ->and($order->fresh()->refundedTotal())->toBe(2000);
});

it('processes a return with no refund amount', function () {
    $variant = seedReturnable('SKU-RET-2', 5);

    $order = Order::factory()->create(['currency_code' => 'usd']);
    $line = $order->items()->create([
        'title' => 'Y',
        'quantity' => 1,
        'unit_price' => 1000,
        'product_id' => $variant->product_id,
        'variant_id' => $variant->id,
    ]);

    $return = (new ReturnService)->create($order->id, [$line->id => 1]);

    $level = InventoryLevel::query()
        ->where('inventory_item_id', InventoryItem::query()->where('sku', 'SKU-RET-2')->value('id'))
        ->first();

    expect($return->status)->toBe('received')
        ->and($level->stocked_quantity)->toBe(6)
        ->and($order->fresh()->refundedTotal())->toBe(0);
});
