<?php

namespace JeffersonGoncalves\Commerce\Checkout\Workflows;

use JeffersonGoncalves\Commerce\Checkout\Workflows\Steps\CreateReturnStep;
use JeffersonGoncalves\Commerce\Checkout\Workflows\Steps\MarkReturnReceivedStep;
use JeffersonGoncalves\Commerce\Checkout\Workflows\Steps\RecordRefundStep;
use JeffersonGoncalves\Commerce\Checkout\Workflows\Steps\RestockInventoryStep;
use JeffersonGoncalves\Commerce\Core\Workflow\Workflow;

class ReturnWorkflow
{
    public static function build(): Workflow
    {
        return (new Workflow)
            ->addStep(new CreateReturnStep)
            ->addStep(new RestockInventoryStep)
            ->addStep(new RecordRefundStep)
            ->addStep(new MarkReturnReceivedStep);
    }
}
