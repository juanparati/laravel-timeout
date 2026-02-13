<?php

namespace Juanparati\LaravelTimeout\Drivers;

use Illuminate\Database\Connection;
use Juanparati\LaravelTimeout\Contracts\TimeoutDriver;

class MysqlTimeoutDriver extends MariadbTimeoutDriver
{
    protected const VARIABLE_NAME = 'max_execution_time';

    public function throwTimeoutException(\Throwable $error): never {
        throw $error;
    }

    public function canRaiseTimeoutException(): bool { return false; }
}
