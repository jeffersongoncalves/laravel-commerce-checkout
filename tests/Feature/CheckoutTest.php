<?php

use JeffersonGoncalves\Commerce\Cart\Models\Cart;
use JeffersonGoncalves\Commerce\Checkout\Services\CheckoutService;
use JeffersonGoncalves\Commerce\Checkout\Services\PricingResolver;
use JeffersonGoncalves\Commerce\Core\Workflow\WorkflowException;
use JeffersonGoncalves\Commerce\Inventory\Models\InventoryItem;
use JeffersonGoncalves\Commerce\Inventory\Models\InventoryLevel;
use JeffersonGoncalves\Commerce\Order\Models\Order;
use JeffersonGoncalves\Commerce\Pricing\Models\Price;
use JeffersonGoncalves\Commerce\Pricing\Models\PriceSet;
use JeffersonGoncalves\Commerce\Product\Models\ProductVariant;

function seedVariant(string $sku, int $amount = 2500): ProductVariant
{
    $variant = ProductVariant::factory()->create(['sku' => $sku]);
    $priceSet = PriceSet::factory()->create();
    $variant->update(['price_set_id' => $priceSet->id]);

    Price::factory()->create([
        'price_set_id' => $priceSet->id,
        'currency_code' => 'usd',
        'amount' => $amount,
        'min_quantity' => null,
        'max_quantity' => null,
    ]);

    return $variant->fresh();
}

function seedInventory(string $sku, int $stocked): void
{
    $item = InventoryItem::factory()->create(['sku' => $sku]);
    InventoryLevel::factory()->create([
        'inventory_item_id' => $item->id,
        'location_id' => 'sloc_main',
        'stocked_quantity' => $stocked,
        'reserved_quantity' => 0,
    ]);
}

it('resolves a variant price by currency and quantity', function () {
    $variant = seedVariant('SKU-RES-1', 1999);

    expect((new PricingResolver)->resolve($variant, 'usd', 2))->toBe(1999)
        ->and((new PricingResolver)->resolve($variant, 'eur', 1))->toBeNull();
});

it('completes a cart into an order and reserves inventory', function () {
    $variant = seedVariant('SKU-CHK-1', 2500);
    seedInventory('SKU-CHK-1', 100);

    $price = (new PricingResolver)->resolve($variant, 'usd', 2);

    $cart = Cart::factory()->create(['currency_code' => 'usd']);
    $cart->items()->create([
        'title' => 'T-Shirt',
        'quantity' => 2,
        'unit_price' => $price,
        'product_id' => $variant->product_id,
        'variant_id' => $variant->id,
    ]);

    $order = (new CheckoutService)->complete($cart->id);
    $order->load('items');

    $level = InventoryLevel::query()->where('inventory_item_id', InventoryItem::query()->where('sku', 'SKU-CHK-1')->value('id'))->first();

    expect($order->cart_id)->toBe($cart->id)
        ->and($order->items)->toHaveCount(1)
        ->and($order->total())->toBe(5000)
        ->and($level->reserved_quantity)->toBe(2)
        ->and($cart->fresh()->completed_at)->not->toBeNull();
});

it('rolls back the whole checkout when inventory cannot be reserved', function () {
    $variant = seedVariant('SKU-FAIL-1', 1000);
    seedInventory('SKU-FAIL-1', 5);

    $cart = Cart::factory()->create(['currency_code' => 'usd']);
    $cart->items()->create([
        'title' => 'Scarce',
        'quantity' => 10,
        'unit_price' => 1000,
        'product_id' => $variant->product_id,
        'variant_id' => $variant->id,
    ]);

    expect(fn () => (new CheckoutService)->complete($cart->id))->toThrow(WorkflowException::class);

    expect(Order::query()->count())->toBe(0)
        ->and($cart->fresh()->completed_at)->toBeNull();

    $level = InventoryLevel::query()->first();
    expect($level->reserved_quantity)->toBe(0);
});
