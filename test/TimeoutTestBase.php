<?php

namespace Juanparati\LaravelTimeout\Test;

use Juanparati\LaravelTimeout\Providers\TimeoutProvider;
use Orchestra\Testbench\TestCase;

/**
 * Class InMobileTest.
 */
abstract class TimeoutTestBase extends TestCase
{
    /**
     * Load service providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return string[]
     */
    protected function getPackageProviders($app): array
    {
        return [TimeoutProvider::class];
    }

    /**
     * Clear fakes before each test.
     *
     * @throws \ReflectionException
     */
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'default');
        config()->set('database.connections.default', [
            'driver' => env('DB_CONNECTION', 'mariadb'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'database' => env('DB_DATABASE', ''),
            'prefix' => '',
        ]);
    }
}
