<?php

namespace JeffersonGoncalves\Commerce\Checkout;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class CommerceCheckoutServiceProvider extends PackageServiceProvider
{
    public static string $name = 'commerce-checkout';

    public function configurePackage(Package $package): void
    {
        $package->name(static::$name);
    }
}
