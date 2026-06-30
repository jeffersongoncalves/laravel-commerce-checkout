<?php

namespace JeffersonGoncalves\Commerce\Checkout\Workflows;

use JeffersonGoncalves\Commerce\Checkout\Workflows\Steps\CreateExchangeStep;
use JeffersonGoncalves\Commerce\Checkout\Workflows\Steps\MarkExchangeReceivedStep;
use JeffersonGoncalves\Commerce\Checkout\Workflows\Steps\ReserveExchangeOutboundStep;
use JeffersonGoncalves\Commerce\Checkout\Workflows\Steps\RestockExchangeInboundStep;
use JeffersonGoncalves\Commerce\Core\Workflow\Workflow;

class ExchangeWorkflow
{
    public static function build(): Workflow
    {
        return (new Workflow)
            ->addStep(new CreateExchangeStep)
            ->addStep(new RestockExchangeInboundStep)
            ->addStep(new ReserveExchangeOutboundStep)
            ->addStep(new MarkExchangeReceivedStep);
    }
}
