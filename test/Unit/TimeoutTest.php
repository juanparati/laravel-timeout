<?php

namespace Juanparati\QueryTimeout\Test\Unit;

use Illuminate\Database\QueryException;
use Juanparati\QueryTimeout\Exceptions\QueryTimeoutException;
use Juanparati\QueryTimeout\QueryTimeout;
use Juanparati\QueryTimeout\Test\TimeoutTestBase;

class TimeoutTest extends TimeoutTestBase
{
    public function test_timeout()
    {
        $this->assertThrows(
            fn () => app(QueryTimeout::class)(2, fn () => static::generateSleepQuery(3)),
            QueryTimeoutException::class
        );

        // This one should not raise any error, because max_statement_time/max_execution_time was restored.
        $this->assertDoesntThrow(fn () => static::generateSleepQuery(3));

    }

    public function test_without_timeout()
    {
        $error = null;

        try {
            app(QueryTimeout::class)(3, fn () => \DB::select('SELECT TRUE'));
        } catch (QueryException $error) {
        }

        $this->assertNull($error);
    }

    public function test_runtime()
    {
        config()->set('timeout.resolution', 'second');

        $this->assertGreaterThan(
            app(QueryTimeout::class)(2, fn () => static::generateSleepQuery(1)),
            2
        );

        config()->set('timeout.resolution', 'millisecond');

        $this->assertGreaterThan(
            app(QueryTimeout::class)(2, fn () => static::generateSleepQuery(1)),
            1200
        );
    }

    protected static function generateSleepQuery(int $seconds)
    {
        $sleepFnc = config('database.connections.default.driver') === 'pgsql' ? 'PG_SLEEP' : 'SLEEP';

        return \DB::select(sprintf('SELECT %s(%d)', $sleepFnc, $seconds));
    }
}
