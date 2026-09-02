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

        // The package migration appends itsme_id to an existing users table:
        // create a baseline table first so migrations can run in tests.
        $this->createUsersTableIfMissing();

        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    protected function createUsersTableIfMissing(): void
    {
        if (\Schema::hasTable('users')) {
            return;
        }

        \Schema::create('users', function ($table) {
            $table->id();
            $table->string('email')->nullable();
            $table->string('name')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('phone')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
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

