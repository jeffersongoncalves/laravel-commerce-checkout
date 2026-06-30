<?php

namespace JeffersonGoncalves\Commerce\Checkout\Workflows;

use JeffersonGoncalves\Commerce\Checkout\Workflows\Steps\CompleteCartStep;
use JeffersonGoncalves\Commerce\Checkout\Workflows\Steps\CreateOrderItemsStep;
use JeffersonGoncalves\Commerce\Checkout\Workflows\Steps\CreateOrderStep;
use JeffersonGoncalves\Commerce\Checkout\Workflows\Steps\ReserveInventoryStep;
use JeffersonGoncalves\Commerce\Core\Workflow\Workflow;

class CompleteCartWorkflow
{
    public static function build(): Workflow
    {
        return (new Workflow)
            ->addStep(new CreateOrderStep)
            ->addStep(new CreateOrderItemsStep)
            ->addStep(new ReserveInventoryStep)
            ->addStep(new CompleteCartStep);
    }
}
