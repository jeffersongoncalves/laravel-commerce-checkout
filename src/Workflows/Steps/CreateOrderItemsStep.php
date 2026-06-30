<?php

namespace JeffersonGoncalves\Commerce\Checkout\Workflows\Steps;

use JeffersonGoncalves\Commerce\Cart\Models\Cart;
use JeffersonGoncalves\Commerce\Cart\Models\CartLineItem;
use JeffersonGoncalves\Commerce\Core\Workflow\Step;
use JeffersonGoncalves\Commerce\Core\Workflow\WorkflowContext;
use JeffersonGoncalves\Commerce\Order\Models\Order;
use JeffersonGoncalves\Commerce\Order\Models\OrderLineItem;

class CreateOrderItemsStep extends Step
{
    /**
     * @return array<int, string>
     */
    public function handle(WorkflowContext $context): array
    {
        /** @var Cart $cart */
        $cart = $context->input['cart'];
        /** @var Order $order */
        $order = $context->result('create_order');

        $ids = [];

        /** @var CartLineItem $item */
        foreach ($cart->items as $item) {
            $line = $order->items()->create([
                'title' => $item->title,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'product_id' => $item->product_id,
                'variant_id' => $item->variant_id,
            ]);

            $ids[] = $line->id;
        }

        return $ids;
    }

    public function compensate(mixed $result, WorkflowContext $context): void
    {
        if (is_array($result) && $result !== []) {
            OrderLineItem::query()->whereIn('id', $result)->forceDelete();
        }
    }

    public function name(): string
    {
        return 'create_order_items';
    }
}
