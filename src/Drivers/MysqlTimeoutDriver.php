<?php

namespace Juanparati\LaravelTimeout\Drivers;

use Illuminate\Database\Connection;

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
        throw $error;
    }

    public function canRaiseTimeoutException(): bool
    {
        return false;
    }
}
