<?php

namespace Juanparati\LaravelTimeout;

use Illuminate\Database\QueryException;
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
     * Connections information.
     *
     * @var array<string,{timeout: float, statement: string}>
     */
    protected array $connectionInfo = [];

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
    public function timeout(int|float $seconds, callable $callback, ?string $connection = null): float
    {
        if ($connection === null) {
            $connection = \DB::getDefaultConnection();
        }

        if (! isset($this->connectionInfo[$connection])) {
            $this->saveConnectionInfo($connection);
        }

        $error = null;

        $this->setTimeout($connection, $seconds);

        $startTimer = now();

        try {
            $callback();
        } catch (\Throwable $error) {

            // It will detect timeout for MariaDB
            if ($error instanceof QueryException
                && $error->getCode() == 70100
                && str($error->getMessage())->contains('max_statement_time exceeded', true)
            ) {
                $error = new QueryTimeoutException($connection, $error);
            }
        }

        $runtime = $startTimer->diffInMicroseconds(now());

        $this->resetTimeout($connection);

        // Unfortunately, MySQL doesn't raise any error or warning with "max_execution_time", so we have to calculate
        // the runtime and artificially create the exception. This method is non-deterministic because PHP code
        // execution will use some runtime.
        if ($this->connectionInfo[$connection]['statement'] === 'max_execution_time') {
            if ($seconds < ($runtime / 1e6)) {
                $error = new QueryTimeoutException($connection);
            }
        }

        if ($error) {
            throw $error;
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
