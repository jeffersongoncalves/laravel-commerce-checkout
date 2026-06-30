<?php

namespace JeffersonGoncalves\Commerce\Checkout\Workflows\Steps;

use JeffersonGoncalves\Commerce\Cart\Models\Cart;
use JeffersonGoncalves\Commerce\Core\Workflow\Step;
use JeffersonGoncalves\Commerce\Core\Workflow\WorkflowContext;

class CompleteCartStep extends Step
{
    public function handle(WorkflowContext $context): string
    {
        /** @var Cart $cart */
        $cart = $context->input['cart'];

        $cart->update(['completed_at' => now()]);

        return $cart->id;
    }

    public function compensate(mixed $result, WorkflowContext $context): void
    {
        if (is_string($result)) {
            Cart::query()->whereKey($result)->update(['completed_at' => null]);
        }
    }

    public function name(): string
    {
        return 'complete_cart';
    }
}
