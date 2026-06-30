<?php

namespace JeffersonGoncalves\Commerce\Checkout\Services;

use JeffersonGoncalves\Commerce\Promotion\Enums\ApplicationMethodType;
use JeffersonGoncalves\Commerce\Promotion\Enums\PromotionStatus;
use JeffersonGoncalves\Commerce\Promotion\Models\Promotion;

/**
 * Computes the discount (minor units) an active promotion code grants against a
 * subtotal, based on the promotion's application method.
 */
class PromotionApplicator
{
    public function discountFor(int $subtotal, string $code): int
    {
        $promotion = Promotion::query()
            ->where('code', $code)
            ->where('status', PromotionStatus::Active)
            ->with('applicationMethod')
            ->first();

        $method = $promotion?->applicationMethod;

        if ($method === null) {
            return 0;
        }

        $discount = match ($method->type) {
            ApplicationMethodType::Percentage => (int) round($subtotal * ($method->value / 100)),
            ApplicationMethodType::Fixed => $method->value,
        };

        return max(0, min($discount, $subtotal));
    }
}
