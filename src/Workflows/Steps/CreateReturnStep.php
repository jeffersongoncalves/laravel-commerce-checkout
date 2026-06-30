<?php

namespace JeffersonGoncalves\Commerce\Checkout\Workflows\Steps;

use JeffersonGoncalves\Commerce\Core\Workflow\Step;
use JeffersonGoncalves\Commerce\Core\Workflow\WorkflowContext;
use JeffersonGoncalves\Commerce\Order\Models\Order;
use JeffersonGoncalves\Commerce\Order\Models\OrderReturn;

class CreateReturnStep extends Step
{
    public function handle(WorkflowContext $context): OrderReturn
    {
        /** @var Order $order */
        $order = $context->input['order'];

        /** @var OrderReturn $return */
        $return = OrderReturn::query()->create([
            'order_id' => $order->id,
            'status' => 'requested',
            'refund_amount' => $context->input['refund_amount'] ?? null,
            'location_id' => $context->input['location_id'] ?? null,
        ]);

        /** @var array<string, int> $items */
        $items = $context->input['items'] ?? [];

        foreach ($items as $orderLineItemId => $quantity) {
            $return->items()->create([
                'order_line_item_id' => $orderLineItemId,
                'quantity' => $quantity,
            ]);
        }

        return $return;
    }

    public function compensate(mixed $result, WorkflowContext $context): void
    {
        if ($result instanceof OrderReturn) {
            $result->items()->forceDelete();
            $result->forceDelete();
        }
    }

    public function name(): string
    {
        return 'create_return';
    }
}
