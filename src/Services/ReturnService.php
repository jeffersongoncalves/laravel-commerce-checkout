<?php

namespace JeffersonGoncalves\Commerce\Checkout\Services;

use JeffersonGoncalves\Commerce\Checkout\Workflows\ReturnWorkflow;
use JeffersonGoncalves\Commerce\Order\Events\ReturnReceived;
use JeffersonGoncalves\Commerce\Order\Models\Order;
use JeffersonGoncalves\Commerce\Order\Models\OrderReturn;

class ReturnService
{
    /**
     * Create a return for an order: records the returned items, restocks
     * inventory, records the refund transaction and marks the return received —
     * as a saga that rolls back on any failure.
     *
     * @param  array<string, int>  $items  order line item id => quantity returned
     */
    public function create(string $orderId, array $items, ?int $refundAmount = null, ?string $locationId = null): OrderReturn
    {
        /** @var Order $order */
        $order = Order::query()->findOrFail($orderId);

        $context = ReturnWorkflow::build()->run([
            'order' => $order,
            'items' => $items,
            'refund_amount' => $refundAmount,
            'location_id' => $locationId,
        ]);

        /** @var OrderReturn $return */
        $return = $context->result('create_return');

        event(new ReturnReceived($return));

        return $return;
    }
}
