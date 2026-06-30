<?php

namespace JeffersonGoncalves\Commerce\Checkout\Services;

use JeffersonGoncalves\Commerce\Checkout\Workflows\ExchangeWorkflow;
use JeffersonGoncalves\Commerce\Order\Models\Order;
use JeffersonGoncalves\Commerce\Order\Models\OrderExchange;

class ExchangeService
{
    /**
     * Exchange returned items for new ones: restocks the inbound items and
     * reserves the outbound ones as a saga that rolls back on failure.
     *
     * @param  array<string, int>  $inbound  order line item id => quantity returned
     * @param  array<string, int>  $outbound  variant id => quantity shipped out
     */
    public function create(string $orderId, array $inbound, array $outbound, ?int $differenceAmount = null, ?string $locationId = null): OrderExchange
    {
        /** @var Order $order */
        $order = Order::query()->findOrFail($orderId);

        $context = ExchangeWorkflow::build()->run([
            'order' => $order,
            'inbound' => $inbound,
            'outbound' => $outbound,
            'difference_amount' => $differenceAmount,
            'location_id' => $locationId,
        ]);

        /** @var OrderExchange $exchange */
        $exchange = $context->result('create_exchange');

        return $exchange;
    }
}
