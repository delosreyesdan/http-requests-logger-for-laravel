<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Storage Driver
    |--------------------------------------------------------------------------
    | Where to store request logs.
    | Supported: "database", "dynamodb"
    */
    'storage' => env('HTTP_REQUEST_LOGGER_STORAGE', 'database'),

    /*
    |--------------------------------------------------------------------------
    | Batch Size
    |--------------------------------------------------------------------------
    | How many logs to buffer before flushing to storage.
    */
    'batch_size' => env('HTTP_REQUEST_LOGGER_BATCH_SIZE', 500),

    /*
    |--------------------------------------------------------------------------
    | Database Table
    |--------------------------------------------------------------------------
    */
    'table' => env('HTTP_REQUEST_LOGGER_TABLE', 'r'),

    /*
    |--------------------------------------------------------------------------
    | Redis Connection
    |--------------------------------------------------------------------------
    | Used for buffering logs before batch insert.
    */
    'redis' => [
        'scheme'   => env('REDIS_SCHEME', 'tcp'), // 👈 added scheme
        'host'     => env('REDIS_HOST', '127.0.0.1'),
        'port'     => env('REDIS_PORT', 6379),
        'password' => env('REDIS_PASSWORD', null),
        'database' => env('REDIS_DB', 0),
    ],

];
