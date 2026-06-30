<?php

namespace JeffersonGoncalves\Commerce\Checkout\Workflows\Steps;

use JeffersonGoncalves\Commerce\Core\Workflow\Step;
use JeffersonGoncalves\Commerce\Core\Workflow\WorkflowContext;
use JeffersonGoncalves\Commerce\Order\Models\Order;
use JeffersonGoncalves\Commerce\Order\Models\OrderReturn;
use JeffersonGoncalves\Commerce\Order\Models\OrderTransaction;

class RecordRefundStep extends Step
{
    public function handle(WorkflowContext $context): ?OrderTransaction
    {
        /** @var Order $order */
        $order = $context->input['order'];
        /** @var OrderReturn $return */
        $return = $context->result('create_return');

        if ($return->refund_amount === null || $return->refund_amount <= 0) {
            return null;
        }

        return OrderTransaction::query()->create([
            'order_id' => $order->id,
            'amount' => $return->refund_amount,
            'currency_code' => $order->currency_code,
            'type' => 'refund',
        ]);
    }

    public function compensate(mixed $result, WorkflowContext $context): void
    {
        if ($result instanceof OrderTransaction) {
            $result->forceDelete();
        }
    }

    public function name(): string
    {
        return 'record_refund';
    }
}
