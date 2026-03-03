<?php

namespace Juanparati\QueryTimeout;

use Illuminate\Database\Connection;
use Juanparati\QueryTimeout\Contracts\QueryTimeoutDriver;
use Juanparati\QueryTimeout\Exceptions\QueryTimeoutException;

/**
 * Provides a control method through a closure for controlling query timeouts.
 *
 * It facilitates the implementation of a circuit-break pattern.
 */
class QueryTimeout
{
    /**
     * Timeout drivers cache
     *
     * @var array<string, QueryTimeoutDriver>
     */
    protected array $drivers = [];

    /**
     * Set max timeout into session and execute the callback.
     *
     * Returns the total query execution.
     *
     * @throws \Throwable
     */
    public function __invoke(int|float $seconds, callable $callback, string|Connection|null $connection = null): float
    {
        $connection = $connection instanceof Connection
            ? $connection : ($connection ? \DB::connection($connection) : \DB::connection());

        $connectionName = $connection->getName();

        if (! isset($this->drivers[$connectionName])) {
            $this->drivers[$connectionName] = new (
                str($connection->getDriverName())
                    ->lower()
                    ->ucfirst()
                    ->prepend('\\Juanparati\\QueryTimeout\\Drivers\\')
                    ->append('QueryTimeoutDriver')->toString()
            )($connection);

            $this->drivers[$connectionName]->saveDefaultTimeout();
        }

        $connection = $this->drivers[$connectionName];
        $connection->setTimeout($seconds);

        $error = null;
        $startTimer = now();

        try {
            $callback();
        } catch (\Throwable $e) {
            $error = $e;
        }

        // It's important to reset the default timeout after the callback execution.
        $connection->resetTimeout();

        if ($error) {
            throw $connection->throwTimeoutException($error);
        }

        $runtime = $startTimer->diffInMicroseconds(now());

        // Unfortunately, some RDBMS like MySQL doesn't raise any error or warning every time when the query expired,
        // so we have to calculate the runtime and artificially to create the exception.
        // This method is non-deterministic because the PHP code will consume runtime.
        if (! $connection->canRaiseTimeoutException()) {
            if ($seconds < ($runtime / 1e6)) {
                throw new QueryTimeoutException($connectionName);
            }
        }

        return $runtime / $this->getTimeResolutionScale();
    }

    /**
     * Get the scale for the time resolution.
     */
    protected function getTimeResolutionScale(): int
    {
        return match (config('timeout.resolution')) {
            'microsecond', 'microseconds' => 1,
            'millisecond', 'milliseconds' => 1e3,
            default => 1e6,
        };
    }
}
