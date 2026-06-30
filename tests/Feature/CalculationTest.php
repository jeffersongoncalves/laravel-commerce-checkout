<?php

use JeffersonGoncalves\Commerce\Cart\Models\Cart;
use JeffersonGoncalves\Commerce\Checkout\Services\CartCalculator;
use JeffersonGoncalves\Commerce\Checkout\Services\PromotionApplicator;
use JeffersonGoncalves\Commerce\Checkout\Services\TaxResolver;
use JeffersonGoncalves\Commerce\Promotion\Enums\ApplicationMethodType;
use JeffersonGoncalves\Commerce\Promotion\Enums\PromotionStatus;
use JeffersonGoncalves\Commerce\Promotion\Models\ApplicationMethod;
use JeffersonGoncalves\Commerce\Promotion\Models\Promotion;
use JeffersonGoncalves\Commerce\Tax\Models\TaxRate;
use JeffersonGoncalves\Commerce\Tax\Models\TaxRegion;

function seedTax(string $country, float $rate): void
{
    $region = TaxRegion::factory()->create(['country_code' => $country, 'province_code' => null]);
    TaxRate::factory()->create(['tax_region_id' => $region->id, 'is_default' => true, 'rate' => $rate]);
}

function seedPromo(string $code, ApplicationMethodType $type, int $value): void
{
    $promo = Promotion::factory()->create(['code' => $code, 'status' => PromotionStatus::Active]);
    ApplicationMethod::factory()->create(['promotion_id' => $promo->id, 'type' => $type, 'value' => $value]);
}

it('resolves tax from the region default rate', function () {
    seedTax('us', 8.5);

    expect((new TaxResolver)->resolve(10000, 'US'))->toBe(850)
        ->and((new TaxResolver)->resolve(10000, 'br'))->toBe(0);
});

it('applies percentage and fixed promotions', function () {
    seedPromo('PCT10', ApplicationMethodType::Percentage, 10);
    seedPromo('OFF500', ApplicationMethodType::Fixed, 500);

    expect((new PromotionApplicator)->discountFor(3000, 'PCT10'))->toBe(300)
        ->and((new PromotionApplicator)->discountFor(3000, 'OFF500'))->toBe(500)
        ->and((new PromotionApplicator)->discountFor(3000, 'MISSING'))->toBe(0);
});

it('computes the full cart totals breakdown', function () {
    seedTax('us', 10);
    seedPromo('SAVE10', ApplicationMethodType::Percentage, 10);

    $cart = Cart::factory()->create([
        'currency_code' => 'usd',
        'shipping_address' => ['country_code' => 'us'],
    ]);
    $cart->items()->create(['title' => 'A', 'quantity' => 2, 'unit_price' => 1000]);
    $cart->items()->create(['title' => 'B', 'quantity' => 1, 'unit_price' => 1000]);
    $cart->shippingMethods()->create(['name' => 'Standard', 'amount' => 500]);

    $totals = (new CartCalculator)->totals($cart->fresh(), 'SAVE10');

    expect($totals)->toBe([
        'subtotal' => 3000,
        'discount' => 300,
        'tax' => 270,      // 10% of (3000 - 300)
        'shipping' => 500,
        'total' => 3470,   // 2700 + 270 + 500
    ]);
});

it('computes totals without a promotion', function () {
    seedTax('us', 10);

    $cart = Cart::factory()->create([
        'currency_code' => 'usd',
        'shipping_address' => ['country_code' => 'us'],
    ]);
    $cart->items()->create(['title' => 'A', 'quantity' => 1, 'unit_price' => 2000]);

    expect((new CartCalculator)->totals($cart->fresh()))->toBe([
        'subtotal' => 2000,
        'discount' => 0,
        'tax' => 200,
        'shipping' => 0,
        'total' => 2200,
    ]);
});
