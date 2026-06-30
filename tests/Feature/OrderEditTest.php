<?php

use JeffersonGoncalves\Commerce\Checkout\Services\OrderEditService;
use JeffersonGoncalves\Commerce\Inventory\Models\InventoryItem;
use JeffersonGoncalves\Commerce\Inventory\Models\InventoryLevel;
use JeffersonGoncalves\Commerce\Order\Models\Order;

function editLevel(string $sku): InventoryLevel
{
    return InventoryLevel::query()
        ->where('inventory_item_id', InventoryItem::query()->where('sku', $sku)->value('id'))
        ->first();
}

it('adds, updates and removes order items keeping inventory in sync', function () {
    $variant = seedReturnable('SKU-EDIT-1', 20);
    $order = Order::factory()->create();
    $service = new OrderEditService;

    $item = $service->addItem($order->id, [
        'title' => 'X', 'quantity' => 2, 'unit_price' => 1000,
        'product_id' => $variant->product_id, 'variant_id' => $variant->id,
    ]);
    expect(editLevel('SKU-EDIT-1')->reserved_quantity)->toBe(2);

    $service->updateItemQuantity($order->id, $item->id, 5);
    expect(editLevel('SKU-EDIT-1')->reserved_quantity)->toBe(5);

    $service->updateItemQuantity($order->id, $item->id, 1);
    expect(editLevel('SKU-EDIT-1')->reserved_quantity)->toBe(1);

    $service->removeItem($order->id, $item->id);

    expect(editLevel('SKU-EDIT-1')->reserved_quantity)->toBe(0)
        ->and($order->items()->count())->toBe(0);
});
