<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Storage Driver
    |--------------------------------------------------------------------------
    | Where to persist flushed logs.
    | Supported: "database", "dynamodb", "redis", "null"
    |
    | "database" — any Laravel-supported DB (MySQL, PostgreSQL, SQLite, etc.)
    | "dynamodb" — AWS DynamoDB via batchWriteItem
    | "redis"    — permanent Redis/Valkey list (uses the same connection config below)
    | "null"     — discards all logs; useful for disabling without removing middleware
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
    'table' => env('HTTP_REQUEST_LOGGER_TABLE', 'http_request_logs'),

    /*
    |--------------------------------------------------------------------------
    | Fallback on Buffer Error
    |--------------------------------------------------------------------------
    | When true, logs are written directly to storage if the buffer
    | (Redis/Valkey) is unavailable, so no data is lost.
    */
    'fallback_on_buffer_error' => env('HTTP_REQUEST_LOGGER_FALLBACK', true),

    /*
    |--------------------------------------------------------------------------
    | Outgoing Request Logging
    |--------------------------------------------------------------------------
    | When true, outgoing HTTP requests made via Laravel's Http facade are
    | also logged. Requires Laravel's Http client (Guzzle) to be in use.
    */
    'log_outgoing' => env('HTTP_REQUEST_LOGGER_LOG_OUTGOING', false),

    /*
    |--------------------------------------------------------------------------
    | Exclusions
    |--------------------------------------------------------------------------
    | 'fields' — log fields to omit from every entry.
    |            Supported: "direction", "ip", "user_agent", "headers", "body", "duration_ms"
    |
    | 'paths'  — URL path patterns to skip entirely (wildcards supported).
    |            Example: ['/health', '/api/internal/*']
    */
    'exclude' => [
        'fields' => [],
        'paths'  => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Buffer Connection (Redis / Valkey)
    |--------------------------------------------------------------------------
    | Logs are pushed here first and flushed to storage in batches.
    | Both Redis and Valkey are supported — they share the same protocol
    | and this package uses ext-redis directly for performance.
    |
    | 'driver'  : Label only — "redis" or "valkey", no behavioural difference.
    | 'prefix'  : Prefix for the buffer list key in Redis/Valkey.
    */
    'redis' => [
        'driver'   => env('HTTP_REQUEST_LOGGER_BUFFER_DRIVER', 'redis'),
        'host'     => env('REDIS_HOST', '127.0.0.1'),
        'port'     => env('REDIS_PORT', 6379),
        'password' => env('REDIS_PASSWORD', null),
        'database' => env('REDIS_DB', 0),
        'prefix'   => env('HTTP_REQUEST_LOGGER_KEY_PREFIX', 'http_request_logs'),
    ],

    /*
    |--------------------------------------------------------------------------
    | DynamoDB
    |--------------------------------------------------------------------------
    | Only required when storage is set to "dynamodb".
    */
    'dynamodb' => [
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Auto-Schedule
    |--------------------------------------------------------------------------
    | When enabled, the package registers a scheduled task that runs
    | `logs:flush` on the given cron expression. This acts as a safety net
    | drain for when traffic is too low to reach batch_size on its own.
    |
    | Default cron: every minute.
    */
    'schedule' => [
        'enabled' => env('HTTP_REQUEST_LOGGER_SCHEDULE', false),
        'cron'    => env('HTTP_REQUEST_LOGGER_SCHEDULE_CRON', '* * * * *'),
    ],

];
