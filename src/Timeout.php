<?php

namespace Juanparati\LaravelTimeout;

use Illuminate\Database\Connection;
use Juanparati\LaravelTimeout\Contracts\TimeoutDriver;
use Juanparati\LaravelTimeout\Exceptions\QueryTimeoutException;

/**
 * Provides a control method through a closure for controlling query timeouts.
 *
 * It facilitates the implementation of a circuit-break pattern.
 */
class Timeout
{
    /**
     * Singleton instance.
     */
    protected static ?Timeout $_instance = null;

    /**
     * Timeout drivers cache
     *
     * @var array<string, TimeoutDriver>
     */
    protected array $drivers = [];

    /**
     * Singleton.
     */
    public static function getInstance(): static
    {
        if (! static::$_instance) {
            static::$_instance = new static;
        }

        return static::$_instance;
    }

    /**
     * Set max timeout into session and execute the callback.
     *
     * Returns the total query execution.
     *
     * @throws \Throwable
     */
    public function timeout(int|float $seconds, callable $callback, string|Connection|null $connection = null): float
    {
        $connection = $connection instanceof Connection
            ? $connection : ($connection ? \DB::connection($connection) : \DB::connection());

        $connectionName = $connection->getName();

        if (! isset($this->drivers[$connectionName])) {
            $this->drivers[$connectionName] = new (
                str($connection->getDriverName())
                    ->lower()
                    ->ucfirst()
                    ->prepend('\\Juanparati\\LaravelTimeout\\Drivers\\')
                    ->append('TimeoutDriver')->toString()
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

        // Unfortunately, some RDBMS like MySQL doesn't raise any error or warning when the query expired, so we
        // have to calculate the runtime and artificially to create the exception. This method is non-deterministic
        // because the PHP code will consume runtime.
        if (! $connection->canRaiseTimeoutException()) {
            if ($seconds < ($runtime / 1e6)) {
                throw new QueryTimeoutException($connectionName);
            }
        }

        return $runtime / $this->getTimeResolutionScale();
    }

    /**
     * Get and save the default session timeout.
     */
    protected function saveConnectionInfo(string $connection): void
    {
        try {
            $useMaxStatementTime = ! empty(\DB::select("SHOW VARIABLES LIKE 'max_statement_time'"));
        } catch (\Exception $e) {
            $useMaxStatementTime = false;
        }

        $statement = $useMaxStatementTime ? 'max_statement_time' : 'max_execution_time';
        $timeout = \DB::connection($connection)->select("SELECT @@SESSION.$statement AS value")[0]->value;

        $this->connectionInfo[$connection] = [
            'statement' => $statement,
            'timeout' => $timeout,
        ];
    }

    /**
     * Reset the default session timeout.
     */
    protected function resetTimeout(string $connection): void
    {
        static::setTimeout($connection, $this->connectionInfo[$connection]['timeout']);
    }

    /**
     * Set the maximum statement time.
     */
    protected function setTimeout(string $connection, int|float $seconds): void
    {
        if ($this->connectionInfo[$connection]['statement'] === 'max_execution_time') {
            $seconds = (float) $seconds * 1000; // max_execution_time uses milliseconds
        } else {
            $seconds = (int) $seconds;
        }

        \DB::connection($connection)
            ->statement("SET @@SESSION.{$this->connectionInfo[$connection]['statement']}=$seconds");
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
