<?php

namespace JeffersonGoncalves\Commerce\Checkout\Workflows\Steps;

use JeffersonGoncalves\Commerce\Core\Workflow\Step;
use JeffersonGoncalves\Commerce\Core\Workflow\WorkflowContext;
use JeffersonGoncalves\Commerce\Order\Models\Order;
use JeffersonGoncalves\Commerce\Order\Models\OrderExchange;

class CreateExchangeStep extends Step
{
    public function handle(WorkflowContext $context): OrderExchange
    {
        /** @var Order $order */
        $order = $context->input['order'];

        /** @var OrderExchange $exchange */
        $exchange = OrderExchange::query()->create([
            'order_id' => $order->id,
            'status' => 'requested',
            'difference_amount' => $context->input['difference_amount'] ?? null,
            'location_id' => $context->input['location_id'] ?? null,
        ]);

        /** @var array<string, int> $inbound */
        $inbound = $context->input['inbound'] ?? [];
        foreach ($inbound as $orderLineItemId => $quantity) {
            $exchange->items()->create(['type' => 'inbound', 'order_line_item_id' => $orderLineItemId, 'quantity' => $quantity]);
        }

        /** @var array<string, int> $outbound */
        $outbound = $context->input['outbound'] ?? [];
        foreach ($outbound as $variantId => $quantity) {
            $exchange->items()->create(['type' => 'outbound', 'variant_id' => $variantId, 'quantity' => $quantity]);
        }

        return $exchange;
    }

    public function compensate(mixed $result, WorkflowContext $context): void
    {
        if ($result instanceof OrderExchange) {
            $result->items()->forceDelete();
            $result->forceDelete();
        }
    }

    public function name(): string
    {
        return 'create_exchange';
    }
}
