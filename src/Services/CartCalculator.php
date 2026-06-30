<?php

namespace JeffersonGoncalves\Commerce\Checkout\Services;

use JeffersonGoncalves\Commerce\Cart\Models\Cart;

/**
 * Computes a cart's monetary breakdown: item subtotal, promotion discount,
 * tax on the discounted amount, shipping and the grand total.
 */
class CartCalculator
{
    public function __construct(
        private TaxResolver $tax = new TaxResolver,
        private PromotionApplicator $promotions = new PromotionApplicator,
    ) {}

    /**
     * @return array{subtotal: int, discount: int, tax: int, shipping: int, total: int}
     */
    public function totals(Cart $cart, ?string $promotionCode = null): array
    {
        $cart->loadMissing('items', 'shippingMethods');

        $subtotal = (int) $cart->items->sum(fn ($item) => $item->subtotal());

        $discount = $promotionCode !== null
            ? $this->promotions->discountFor($subtotal, $promotionCode)
            : 0;

        $taxable = $subtotal - $discount;

        $country = is_array($cart->shipping_address) ? ($cart->shipping_address['country_code'] ?? null) : null;
        $province = is_array($cart->shipping_address) ? ($cart->shipping_address['province'] ?? null) : null;

        $tax = $country !== null ? $this->tax->resolve($taxable, $country, $province) : 0;

        $shipping = (int) $cart->shippingMethods->sum('amount');

        return [
            'subtotal' => $subtotal,
            'discount' => $discount,
            'tax' => $tax,
            'shipping' => $shipping,
            'total' => $taxable + $tax + $shipping,
        ];
    }
}
