<?php

namespace Juanparati\LaravelTimeout\Contracts;

use Illuminate\Database\Connection;

interface TimeoutDriver
{
    public function __construct(Connection $connection);

    public function setTimeout(int|float $seconds): void;

    public function saveDefaultTimeout(): int|float;

    public function resetTimeout(): void;

    public function isCompatible(): bool;

    public function throwTimeoutException(\Throwable $error): never;

    public function canRaiseTimeoutException(): bool;
}
