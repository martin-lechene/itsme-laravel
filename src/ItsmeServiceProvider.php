<?php

namespace ItsmeLaravel\Itsme;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use ItsmeLaravel\Itsme\Services\ItsmeService;
use ItsmeLaravel\Itsme\Services\TokenValidator;
use ItsmeLaravel\Itsme\Services\OpenIdDiscovery;

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
        // Publier la configuration
        $this->publishes([
            __DIR__ . '/../config/itsme.php' => config_path('itsme.php'),
        ], 'itsme-config');

        // Publier les migrations
        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'itsme-migrations');

        // Publier les vues
        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/itsme'),
        ], 'itsme-views');

        // Publier les fichiers de langue
        $this->publishes([
            __DIR__ . '/../resources/lang' => lang_path('vendor/itsme'),
        ], 'itsme-lang');

        // Publier les assets
        $this->publishes([
            __DIR__ . '/../resources/assets' => public_path('vendor/itsme'),
        ], 'itsme-assets');

        // Charger les routes
        $this->loadRoutes();

        // Charger les vues
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'itsme');

        // Charger les fichiers de langue
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'itsme');

        // Enregistrer les commandes Artisan
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
        Route::group([
            'prefix' => 'itsme',
            'middleware' => 'web',
        ], function () {
            $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        });
    }
}

