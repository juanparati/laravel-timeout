<?php

namespace Juanparati\LaravelTimeout\Drivers;

use Illuminate\Database\Connection;
use Illuminate\Database\QueryException;
use Juanparati\LaravelTimeout\Exceptions\QueryTimeoutException;

class MysqlTimeoutDriver extends MariadbTimeoutDriver
{
    protected const VARIABLE_NAME = 'max_execution_time';

    public function __construct(protected Connection $connection)
    {
        if (! $this->isCompatible()) {
            throw new \RuntimeException('This driver is only compatible with MySQL');
        }
    }

    public function setTimeout(float|int $seconds): void
    {
        // MySQL requires milliseconds
        parent::setTimeout($seconds * 1000);
    }

    public function throwTimeoutException(\Throwable $error): never
    {
        // It will detect timeout for MariaDB
        if ($error instanceof QueryException
            && $error->getCode() === 'HY000'
            && str($error->getMessage())->contains('time exceeded', true)
        ) {
            throw new QueryTimeoutException($this->connection->getName(), $error);
        }

        throw $error;
    }

    public function canRaiseTimeoutException(): bool
    {
        // Unfortunately, MySQL doesn't raise exceptions on timeouts for calculated queries.
        return false;
    }
}
