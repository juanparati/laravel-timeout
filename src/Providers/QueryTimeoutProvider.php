<?php

namespace Juanparati\QueryTimeout\Providers;

use Illuminate\Support\ServiceProvider;
use Juanparati\QueryTimeout\QueryTimeout;

/**
 * Laravel service provider.
 */
class QueryTimeoutProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../config/config.php' => config_path('query-timeout.php'),
            ], 'config');
        }
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Automatically apply the package configuration
        $this->mergeConfigFrom(__DIR__.'/../../config/config.php', 'query-timeout');

        $this->app->singleton(QueryTimeout::class);
    }
}
