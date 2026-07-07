<?php

declare(strict_types=1);

namespace Botdigit\CryptoGateway;

use Botdigit\CryptoGateway\Console\AddCoinCommand;
use Botdigit\CryptoGateway\Console\CheckBalancesCommand;
use Botdigit\CryptoGateway\Console\HealthCheckCommand;
use Botdigit\CryptoGateway\Console\InstallCommand;
use Botdigit\CryptoGateway\Console\SyncTransactionsCommand;
use Illuminate\Support\ServiceProvider;

class CryptoGatewayServiceProvider extends ServiceProvider
{
    /**
     * Register bindings in the container.
     */
    public function register(): void
    {
        // Merge default config (so users don't HAVE to publish)
        $this->mergeConfigFrom(
            __DIR__ . '/../config/cryptogateway.php',
            'cryptogateway'
        );

        // Register the GatewayManager as a singleton
        $this->app->singleton('cryptogateway', function ($app) {
            return new GatewayManager($app);
        });

        // Alias for type-hinting
        $this->app->alias('cryptogateway', GatewayManager::class);
    }

    /**
     * Bootstrap package services.
     */
    public function boot(): void
    {
        $this->registerPublishing();
        $this->registerMigrations();
        $this->registerRoutes();
        $this->registerCommands();
    }

    /**
     * Register publishable resources.
     */
    protected function registerPublishing(): void
    {
        if ($this->app->runningInConsole()) {
            // Config
            $this->publishes([
                __DIR__ . '/../config/cryptogateway.php' => config_path('cryptogateway.php'),
            ], 'cryptogateway-config');

            // Migrations
            $this->publishes([
                __DIR__ . '/../database/migrations/' => database_path('migrations'),
            ], 'cryptogateway-migrations');

            // All assets
            $this->publishes([
                __DIR__ . '/../config/cryptogateway.php' => config_path('cryptogateway.php'),
                __DIR__ . '/../database/migrations/'     => database_path('migrations'),
            ], 'cryptogateway');
        }
    }

    /**
     * Register database migrations.
     */
    protected function registerMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    /**
     * Register webhook routes if enabled.
     */
    protected function registerRoutes(): void
    {
        if ($this->app['config']->get('cryptogateway.webhook.enabled', true)) {
            $this->loadRoutesFrom(__DIR__ . '/../routes/webhook.php');
        }
    }

    /**
     * Register Artisan commands.
     */
    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
                AddCoinCommand::class,
                CheckBalancesCommand::class,
                SyncTransactionsCommand::class,
                HealthCheckCommand::class,
            ]);
        }
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            'cryptogateway',
            GatewayManager::class,
        ];
    }
}
