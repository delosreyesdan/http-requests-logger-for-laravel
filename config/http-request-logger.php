<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Enabled
    |--------------------------------------------------------------------------
    | Master switch. Set to false to disable all logging without removing the
    | middleware registration — useful in test environments or staging.
    */
    'enabled' => env('HTTP_REQUEST_LOGGER_ENABLED', true),

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
    | Sample Rate
    |--------------------------------------------------------------------------
    | Fraction of requests to log (0.0–1.0). 1.0 logs every request. 0.1 logs
    | roughly 10%. Useful for high-traffic apps that don't need full coverage.
    */
    'sample_rate' => env('HTTP_REQUEST_LOGGER_SAMPLE_RATE', 1.0),

    /*
    |--------------------------------------------------------------------------
    | Database Table / Connection
    |--------------------------------------------------------------------------
    | 'table'      — the table name used by the database and redis drivers.
    | 'connection' — Laravel DB connection name. null uses the default connection.
    |                Useful for pointing logs at a dedicated analytics database.
    */
    'table'      => env('HTTP_REQUEST_LOGGER_TABLE', 'http_request_logs'),
    'connection' => env('HTTP_REQUEST_LOGGER_DB_CONNECTION', null),

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
    | User ID Logging
    |--------------------------------------------------------------------------
    | When true, the authenticated user's ID (auth()->id()) is captured on
    | each log entry. Requires the authentication middleware to run first.
    */
    'log_user_id' => env('HTTP_REQUEST_LOGGER_LOG_USER_ID', false),

    /*
    |--------------------------------------------------------------------------
    | Correlation ID Header
    |--------------------------------------------------------------------------
    | Header name to read (or generate) a request ID from. The resolved value
    | is stored as 'request_id' and shared across incoming/outgoing log entries
    | for the same request cycle so they can be correlated later.
    */
    'correlation_id_header' => env('HTTP_REQUEST_LOGGER_CORRELATION_ID_HEADER', 'X-Request-ID'),

    /*
    |--------------------------------------------------------------------------
    | Response Body Logging
    |--------------------------------------------------------------------------
    | When true, the response body is captured for both incoming and outgoing
    | requests. Bodies larger than max_body_size (bytes) are truncated.
    */
    'log_response_body' => env('HTTP_REQUEST_LOGGER_LOG_RESPONSE_BODY', false),
    'max_body_size'     => env('HTTP_REQUEST_LOGGER_MAX_BODY_SIZE', 10240),

    /*
    |--------------------------------------------------------------------------
    | Sensitive Data Masking
    |--------------------------------------------------------------------------
    | Fields listed under 'headers' and 'body' will have their values replaced
    | with "***". Header matching is case-insensitive. Body masking is recursive.
    */
    'mask' => [
        'headers' => [
            // 'Authorization',
            // 'Cookie',
            // 'X-Api-Key',
        ],
        'body' => [
            // 'password',
            // 'token',
            // 'secret',
            // 'credit_card',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Exclusions
    |--------------------------------------------------------------------------
    | 'fields' — log fields to omit from every entry.
    |            Supported: "direction", "ip", "user_agent", "headers",
    |                       "body", "response_body", "duration_ms"
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
    | Redis Permanent Store
    |--------------------------------------------------------------------------
    | Only applies when storage = "redis".
    | max_entries caps the list size via LTRIM after each batch write.
    | Set to 0 to disable capping (unbounded — not recommended).
    */
    'redis_store' => [
        'max_entries' => env('HTTP_REQUEST_LOGGER_REDIS_MAX_ENTRIES', 10000),
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
