<?php

use Illuminate\Support\Facades\Event;
use JeffersonGoncalves\Commerce\Cart\Models\Cart;
use JeffersonGoncalves\Commerce\Checkout\Services\CheckoutService;
use JeffersonGoncalves\Commerce\Checkout\Services\ReturnService;
use JeffersonGoncalves\Commerce\Order\Events\OrderPlaced;
use JeffersonGoncalves\Commerce\Order\Events\ReturnReceived;
use JeffersonGoncalves\Commerce\Order\Models\Order;

it('dispatches OrderPlaced when a cart is completed', function () {
    Event::fake([OrderPlaced::class]);

    $variant = seedVariant('SKU-EVT-1', 2500);
    seedInventory('SKU-EVT-1', 50);

    $cart = Cart::factory()->create(['currency_code' => 'usd']);
    $cart->items()->create([
        'title' => 'X', 'quantity' => 1, 'unit_price' => 2500,
        'product_id' => $variant->product_id, 'variant_id' => $variant->id,
    ]);

    (new CheckoutService)->complete($cart->id);

    Event::assertDispatched(OrderPlaced::class);
});

it('dispatches ReturnReceived when a return is processed', function () {
    Event::fake([ReturnReceived::class]);

    $variant = seedVariant('SKU-EVT-2', 10);
    seedInventory('SKU-EVT-2', 10);

    $order = Order::factory()->create(['currency_code' => 'usd']);
    $line = $order->items()->create([
        'title' => 'Y', 'quantity' => 1, 'unit_price' => 1000,
        'product_id' => $variant->product_id, 'variant_id' => $variant->id,
    ]);

    (new ReturnService)->create($order->id, [$line->id => 1], refundAmount: 1000);

    Event::assertDispatched(ReturnReceived::class);
});
