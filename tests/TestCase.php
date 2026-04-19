<?php

namespace ItsmeLaravel\Itsme\Tests;

use Illuminate\Support\Facades\Route;
use ItsmeLaravel\Itsme\ItsmeServiceProvider;
use ItsmeLaravel\Itsme\Tests\Models\User;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Load test-specific migrations first (creates the users table),
        // then the package migration (adds itsme_id column to users).
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
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
     * Define routes for the test application.
     */
    protected function defineRoutes($router): void
    {
        // Provide a minimal login route so controller error redirects don't throw.
        $router->get('/login', fn () => 'login')->name('login');
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

        // Use the test User model so the controller can create/update users.
        $app['config']->set('auth.providers.users.model', User::class);

        // Setup Itsme config
        $app['config']->set('itsme.client_id', 'test_client_id');
        $app['config']->set('itsme.client_secret', 'test_client_secret');
        $app['config']->set('itsme.environment', 'sandbox');
        $app['config']->set('itsme.redirect', 'http://localhost/itsme/callback');
    }
}

