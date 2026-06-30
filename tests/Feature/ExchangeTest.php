<?php

use JeffersonGoncalves\Commerce\Checkout\Services\ExchangeService;
use JeffersonGoncalves\Commerce\Core\Workflow\WorkflowException;
use JeffersonGoncalves\Commerce\Inventory\Models\InventoryItem;
use JeffersonGoncalves\Commerce\Inventory\Models\InventoryLevel;
use JeffersonGoncalves\Commerce\Order\Models\Order;
use JeffersonGoncalves\Commerce\Order\Models\OrderExchange;

it('exchanges items: restocks inbound and reserves outbound', function () {
    $inbound = seedReturnable('SKU-EX-IN', 10);
    $outbound = seedReturnable('SKU-EX-OUT', 20);

    $order = Order::factory()->create(['currency_code' => 'usd']);
    $line = $order->items()->create([
        'title' => 'In', 'quantity' => 2, 'unit_price' => 1000,
        'product_id' => $inbound->product_id, 'variant_id' => $inbound->id,
    ]);

    $exchange = (new ExchangeService)->create($order->id, [$line->id => 2], [$outbound->id => 3], differenceAmount: 500, locationId: 'sloc_main');
    $exchange->load('items');

    $inLevel = InventoryLevel::query()->where('inventory_item_id', InventoryItem::query()->where('sku', 'SKU-EX-IN')->value('id'))->first();
    $outLevel = InventoryLevel::query()->where('inventory_item_id', InventoryItem::query()->where('sku', 'SKU-EX-OUT')->value('id'))->first();

    expect($exchange->status)->toBe('received')
        ->and($exchange->items)->toHaveCount(2)
        ->and($exchange->difference_amount)->toBe(500)
        ->and($inLevel->stocked_quantity)->toBe(12)
        ->and($outLevel->reserved_quantity)->toBe(3);
});

it('rolls back the exchange when outbound stock is insufficient', function () {
    $inbound = seedReturnable('SKU-EX-IN2', 10);
    $outbound = seedReturnable('SKU-EX-OUT2', 2);

    $order = Order::factory()->create(['currency_code' => 'usd']);
    $line = $order->items()->create([
        'title' => 'In', 'quantity' => 1, 'unit_price' => 1000,
        'product_id' => $inbound->product_id, 'variant_id' => $inbound->id,
    ]);

    expect(fn () => (new ExchangeService)->create($order->id, [$line->id => 1], [$outbound->id => 10]))
        ->toThrow(WorkflowException::class);

    $inLevel = InventoryLevel::query()->where('inventory_item_id', InventoryItem::query()->where('sku', 'SKU-EX-IN2')->value('id'))->first();

    expect(OrderExchange::query()->count())->toBe(0)
        ->and($inLevel->stocked_quantity)->toBe(10); // inbound restock rolled back
});
