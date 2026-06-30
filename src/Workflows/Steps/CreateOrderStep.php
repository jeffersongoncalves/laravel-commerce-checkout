<?php

namespace JeffersonGoncalves\Commerce\Checkout\Workflows\Steps;

use JeffersonGoncalves\Commerce\Cart\Models\Cart;
use JeffersonGoncalves\Commerce\Core\Workflow\Step;
use JeffersonGoncalves\Commerce\Core\Workflow\WorkflowContext;
use JeffersonGoncalves\Commerce\Order\Enums\OrderStatus;
use JeffersonGoncalves\Commerce\Order\Models\Order;

class CreateOrderStep extends Step
{
    public function handle(WorkflowContext $context): Order
    {
        /** @var Cart $cart */
        $cart = $context->input['cart'];

        return Order::query()->create([
            'cart_id' => $cart->id,
            'currency_code' => $cart->currency_code,
            'email' => $cart->email,
            'customer_id' => $cart->customer_id,
            'region_id' => $cart->region_id,
            'sales_channel_id' => $cart->sales_channel_id,
            'status' => OrderStatus::Pending,
        ]);
    }

    public function compensate(mixed $result, WorkflowContext $context): void
    {
        if ($result instanceof Order) {
            $result->forceDelete();
        }
    }

    public function name(): string
    {
        return 'create_order';
    }
}
