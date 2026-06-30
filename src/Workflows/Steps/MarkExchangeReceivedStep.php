<?php

namespace JeffersonGoncalves\Commerce\Checkout\Workflows\Steps;

use JeffersonGoncalves\Commerce\Core\Workflow\Step;
use JeffersonGoncalves\Commerce\Core\Workflow\WorkflowContext;
use JeffersonGoncalves\Commerce\Order\Models\OrderExchange;

class MarkExchangeReceivedStep extends Step
{
    public function handle(WorkflowContext $context): string
    {
        /** @var OrderExchange $exchange */
        $exchange = $context->result('create_exchange');
        $exchange->update(['status' => 'received']);

        return $exchange->id;
    }

    public function compensate(mixed $result, WorkflowContext $context): void
    {
        if (is_string($result)) {
            OrderExchange::query()->whereKey($result)->update(['status' => 'requested']);
        }
    }

    public function name(): string
    {
        return 'mark_exchange_received';
    }
}
