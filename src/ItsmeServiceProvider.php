<?php

namespace ItsmeLaravel\Itsme;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use ItsmeLaravel\Itsme\Middleware\RequireItsmeAuth;
use ItsmeLaravel\Itsme\Services\ItsmeService;
use ItsmeLaravel\Itsme\Services\OpenIdDiscovery;
use ItsmeLaravel\Itsme\Services\TokenValidator;

class ItsmeServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/itsme.php',
            'itsme'
        );

        $this->app->singleton(ItsmeService::class, function ($app) {
            return new ItsmeService(
                $app->make(TokenValidator::class),
                $app->make(OpenIdDiscovery::class)
            );
        });

        $this->app->singleton(TokenValidator::class);
        $this->app->singleton(OpenIdDiscovery::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Publish configuration
        $this->publishes([
            __DIR__ . '/../config/itsme.php' => config_path('itsme.php'),
        ], 'itsme-config');

        // Publish migrations
        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'itsme-migrations');

        // Publish views
        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/itsme'),
        ], 'itsme-views');

        // Publish language files
        $this->publishes([
            __DIR__ . '/../resources/lang' => lang_path('vendor/itsme'),
        ], 'itsme-lang');

        // Load routes
        $this->loadRoutes();

        // Load views
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'itsme');

        // Load language files
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'itsme');

        // Register middleware alias
        $this->registerMiddleware();

        // Register Artisan commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                \ItsmeLaravel\Itsme\Console\Commands\TestItsmeConfig::class,
            ]);
        }
    }

    /**
     * Load the package routes.
     */
    protected function loadRoutes(): void
    {
        $router = $this->app->make(\Illuminate\Routing\Router::class);

        $router->group([
            'prefix' => 'itsme',
            'middleware' => 'web',
        ], function () {
            $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        });
    }

    /**
     * Register the package middleware alias.
     */
    protected function registerMiddleware(): void
    {
        $router = $this->app->make(\Illuminate\Routing\Router::class);
        $router->aliasMiddleware('itsme.auth', RequireItsmeAuth::class);
    }
}

