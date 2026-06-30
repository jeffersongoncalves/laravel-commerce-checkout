<?php

namespace JeffersonGoncalves\Commerce\Checkout\Services;

use JeffersonGoncalves\Commerce\Pricing\Models\Price;
use JeffersonGoncalves\Commerce\Product\Models\ProductVariant;

/**
 * Resolves the applicable unit price (in minor units) for a variant given a
 * currency and quantity, honouring the price set and quantity tiers.
 */
class PricingResolver
{
    public function resolve(ProductVariant $variant, string $currency, int $quantity = 1): ?int
    {
        if ($variant->price_set_id === null) {
            return null;
        }

        $amount = Price::query()
            ->where('price_set_id', $variant->price_set_id)
            ->where('currency_code', $currency)
            ->where(fn ($q) => $q->whereNull('min_quantity')->orWhere('min_quantity', '<=', $quantity))
            ->where(fn ($q) => $q->whereNull('max_quantity')->orWhere('max_quantity', '>=', $quantity))
            ->orderBy('amount')
            ->value('amount');

        return $amount === null ? null : (int) $amount;
    }
}
