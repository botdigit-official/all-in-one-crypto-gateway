<?php

declare(strict_types=1);

namespace Botdigit\CryptoGateway\Tests;

use Botdigit\CryptoGateway\CryptoGatewayServiceProvider;
use Botdigit\CryptoGateway\Facades\CryptoGateway;
use Orchestra\Testbench\TestCase as BaseTestCase;

/**
 * Base test case for the CryptoGateway package.
 *
 * Uses Orchestra Testbench to simulate a Laravel application.
 */
abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            CryptoGatewayServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'CryptoGateway' => CryptoGateway::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('cryptogateway.network', 'testnet');
        $app['config']->set('cryptogateway.logging.enabled', false);
        $app['config']->set('cryptogateway.cache.enabled', false);

        // Use SQLite in-memory for testing
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
}
