<?php

namespace JeffersonGoncalves\Commerce\Checkout\Services;

use JeffersonGoncalves\Commerce\Tax\Models\TaxRegion;

/**
 * Resolves the tax amount (minor units) for a taxable amount in a given country
 * / province, using the most specific matching tax region's default rate.
 */
class TaxResolver
{
    public function resolve(int $amount, string $countryCode, ?string $province = null): int
    {
        $country = strtolower($countryCode);

        $region = null;

        if ($province !== null) {
            $region = TaxRegion::query()
                ->where('country_code', $country)
                ->where('province_code', $province)
                ->first();
        }

        $region ??= TaxRegion::query()
            ->where('country_code', $country)
            ->whereNull('province_code')
            ->first();

        if ($region === null) {
            return 0;
        }

        $rate = $region->rates()->where('is_default', true)->value('rate')
            ?? $region->rates()->value('rate')
            ?? 0.0;

        return (int) round($amount * ((float) $rate / 100));
    }
}
