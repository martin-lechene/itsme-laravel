<?php

namespace ItsmeLaravel\Itsme\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use ItsmeLaravel\Itsme\ItsmeServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    /**
     * Get package providers.
     */
    protected function getPackageProviders($app): array
    {
        return [
            ItsmeServiceProvider::class,
        ];
    }

    /**
     * Define environment setup.
     */
    protected function defineEnvironment($app): void
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Setup Itsme config
        $app['config']->set('itsme.client_id', 'test_client_id');
        $app['config']->set('itsme.client_secret', 'test_client_secret');
        $app['config']->set('itsme.environment', 'sandbox');
        $app['config']->set('itsme.redirect', 'http://localhost/itsme/callback');
    }
}

