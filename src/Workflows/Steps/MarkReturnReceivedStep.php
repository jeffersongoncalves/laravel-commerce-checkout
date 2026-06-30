<?php

namespace JeffersonGoncalves\Commerce\Checkout\Workflows\Steps;

use JeffersonGoncalves\Commerce\Core\Workflow\Step;
use JeffersonGoncalves\Commerce\Core\Workflow\WorkflowContext;
use JeffersonGoncalves\Commerce\Order\Models\OrderReturn;

class MarkReturnReceivedStep extends Step
{
    public function handle(WorkflowContext $context): string
    {
        /** @var OrderReturn $return */
        $return = $context->result('create_return');
        $return->update(['status' => 'received']);

        return $return->id;
    }

    public function compensate(mixed $result, WorkflowContext $context): void
    {
        if (is_string($result)) {
            OrderReturn::query()->whereKey($result)->update(['status' => 'requested']);
        }
    }

    public function name(): string
    {
        return 'mark_return_received';
    }
}
