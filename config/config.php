<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Timeout method name
    |--------------------------------------------------------------------------
    |
    | The method name injected into DB (Default: \DB::timeout(3000, fn() => ...))
    |
     */
    'method' => 'timeout',

    /*
    |--------------------------------------------------------------------------
    | Runtime resolution
    |--------------------------------------------------------------------------
    |
    | Runtime resolution. Available values:
    | - microsecond
    | - millisecond (Default)
    | - second
    |
     */
    'resolution' => 'millisecond',
];
