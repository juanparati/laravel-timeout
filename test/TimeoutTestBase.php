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
            'driver' => 'mysql',
            'host' => '127.0.0.1',
            'port' => '33060',
            'username' => 'homestead',
            'password' => 'secret',
            'database' => '',
            'prefix' => '',
        ]);
    }
}
