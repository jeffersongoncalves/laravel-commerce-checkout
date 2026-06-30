<?php

namespace JeffersonGoncalves\Commerce\Checkout\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    /**
     * Domain packages whose migrations this orchestration package needs.
     *
     * @var list<string>
     */
    protected array $migrationPackages = [
        'product', 'pricing', 'inventory', 'cart', 'order',
        'customer', 'region', 'sales-channel', 'tax', 'promotion',
    ];

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    protected function defineDatabaseMigrations(): void
    {
        $stubs = [];

        foreach ($this->migrationPackages as $package) {
            foreach (glob(__DIR__.'/../../'.$package.'/database/migrations/*.php.stub') ?: [] as $stub) {
                $stubs[] = $stub;
            }
        }

        // Create-table migrations first, then alters, so columns are added to
        // already-existing tables regardless of cross-package filename sort.
        usort($stubs, function (string $a, string $b): int {
            $ga = str_starts_with(basename($a), 'create_') ? 0 : 1;
            $gb = str_starts_with(basename($b), 'create_') ? 0 : 1;

            return [$ga, basename($a)] <=> [$gb, basename($b)];
        });

        $tempPath = sys_get_temp_dir().'/laravel-commerce-checkout-migrations';

        if (is_dir($tempPath)) {
            array_map('unlink', (array) glob($tempPath.'/*.php'));
        } else {
            mkdir($tempPath, 0755, true);
        }

        $index = 0;
        foreach ($stubs as $stub) {
            $name = basename(str_replace('.php.stub', '.php', $stub));
            copy($stub, sprintf('%s/%04d_%s', $tempPath, $index++, $name));
        }

        $this->loadMigrationsFrom($tempPath);
    }
}
