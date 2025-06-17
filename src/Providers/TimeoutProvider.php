<?php

namespace Juanparati\LaravelTimeout\Providers;

use Illuminate\Support\ServiceProvider;
use Juanparati\LaravelTimeout\Timeout;

/**
 * Laravel service provider.
 */
class TimeoutProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../config/config.php' => config_path('timeout.php'),
            ], 'config');
        }
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Automatically apply the package configuration
        $this->mergeConfigFrom(__DIR__.'/../../config/config.php', 'timeout');

        \DB::macro(config('timeout.method'), function (
            int|float $seconds,
            callable $callback,
            ?string $connection = null
        ): float {
            return Timeout::getInstance()->timeout($seconds, $callback, $connection);
        });
    }
}
