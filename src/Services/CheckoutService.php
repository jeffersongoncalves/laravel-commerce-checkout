<?php

namespace JeffersonGoncalves\Commerce\Checkout\Services;

use JeffersonGoncalves\Commerce\Cart\Models\Cart;
use JeffersonGoncalves\Commerce\Checkout\Workflows\CompleteCartWorkflow;
use JeffersonGoncalves\Commerce\Order\Events\OrderPlaced;
use JeffersonGoncalves\Commerce\Order\Models\Order;

class CheckoutService
{
    /**
     * Convert a cart into an order, reserving inventory along the way. The whole
     * operation runs as a saga — any failed step rolls back the prior ones.
     */
    public function complete(string $cartId): Order
    {
        /** @var Cart $cart */
        $cart = Cart::query()->with('items')->findOrFail($cartId);

        $context = CompleteCartWorkflow::build()->run(['cart' => $cart]);

        /** @var Order $order */
        $order = $context->result('create_order');

        event(new OrderPlaced($order));

        return $order;
    }
}
