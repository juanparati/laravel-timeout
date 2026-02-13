<?php

namespace Juanparati\LaravelTimeout\Drivers;

use Illuminate\Database\Connection;
use Illuminate\Database\QueryException;
use Juanparati\LaravelTimeout\Contracts\TimeoutDriver;
use Juanparati\LaravelTimeout\Exceptions\QueryTimeoutException;

class PgsqlTimeoutDriver implements TimeoutDriver
{
    public function __construct(protected Connection $connection) {
        if (!$this->isCompatible()) {
            throw new \RuntimeException('This driver is only compatible with PostgreSQL');
        }
    }

    public function setTimeout(float|int $seconds): void
    {
        $this->connection->statement("SET statement_timeout='{$seconds}s'");
    }

    public function saveDefaultTimeout(): int|float
    {
        // PostgreSQL doesn't require saving the statement_timeout because it can use the "RESET" statement.
        return $this->connection->select("SELECT current_setting('statement_timeout') AS value")[0]->value;
    }

    public function resetTimeout(): void
    {
        $this->connection->statement("RESET statement_timeout");
    }

    public function isCompatible(): bool
    {
        return !empty($this->connection->select("SHOW statement_timeout"));
    }

    public function throwTimeoutException(\Throwable $error) : never
    {
        // It will detect timeout for MariaDB
        if ($error instanceof QueryException
            && $error->getCode() == 57014
            && str($error->getMessage())->contains('timeout', true)
        ) {
            throw new QueryTimeoutException($this->connection->getName(), $error);
        }

        throw $error;
    }

    public function canRaiseTimeoutException(): bool
    {
        return true;
    }
}
