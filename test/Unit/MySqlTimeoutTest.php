<?php

namespace Juanparati\LaravelTimeout\Test\Unit;

class MySqlTimeoutTest extends MariaDbTimeoutTest
{
    protected function setUp(): void
    {
        parent::setUp();
        config()->set('database.default', 'mysql');
    }
}
