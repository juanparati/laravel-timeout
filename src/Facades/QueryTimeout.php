<?php

namespace Juanparati\QueryTimeout\Facades;

use Illuminate\Support\Facades\Facade;
use Juanparati\QueryTimeout\QueryTimeout as QueryTimeoutService;

/**
 * @method static float __invoke(int|float $seconds, callable $callback, string|\Illuminate\Database\Connection|null $connection = null)
 *
 * @see \Juanparati\QueryTimeout\QueryTimeout
 */
class QueryTimeout extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return QueryTimeoutService::class;
    }
}
