# Http Requests Logger for Laravel

> Work in progress — API may change before v1.0.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/ddelosreyes/http-requests-logger-for-laravel.svg?style=flat-square)](https://packagist.org/packages/ddelosreyes/http-requests-logger-for-laravel)

Logs incoming and outgoing HTTP requests without hammering your database. Every request is pushed to a Redis/Valkey list first, then flushed to storage in batches — keeping per-request DB writes entirely off the hot path.

---

## Requirements

- PHP 8.2+
- Laravel 10, 11, or 12
- `ext-redis`

---

## Installation

```bash
composer require ddelosreyes/http-requests-logger-for-laravel
```

```bash
php artisan vendor:publish --provider="Ddelosreyes\HttpRequestsLogger\Providers\HttpRequestLoggerServiceProvider" --tag=config
php artisan vendor:publish --provider="Ddelosreyes\HttpRequestsLogger\Providers\HttpRequestLoggerServiceProvider" --tag=migrations
php artisan migrate
```

---

## Usage

**Register the middleware** to log incoming requests:

```php
// Laravel 11+ — bootstrap/app.php
->withMiddleware(function (Middleware $middleware) {
    $middleware->append(\Ddelosreyes\HttpRequestsLogger\Middleware\HttpRequestLoggerMiddleware::class);
})

// Laravel 10 — app/Http/Kernel.php
protected $middleware = [
    \Ddelosreyes\HttpRequestsLogger\Middleware\HttpRequestLoggerMiddleware::class,
];
```

Each entry captures: `direction`, `method`, `url`, `status`, `ip`, `user_agent`, `headers`, `body`, `duration_ms`.

**Log outgoing requests** (Laravel `Http::` facade) by setting:

```env
HTTP_REQUEST_LOGGER_LOG_OUTGOING=true
```

**Manually push a log** from anywhere:

```php
use Ddelosreyes\HttpRequestsLogger\Facades\HttpRequestLogger;

HttpRequestLogger::add([...]);
```

Or via the action directly:

```php
use Ddelosreyes\HttpRequestsLogger\Actions\RequestLogBufferAction;

RequestLogBufferAction::add([...]);
```

---

## Commands

```bash
php artisan logs:flush          # drain the buffer to storage
php artisan logs:clear          # discard the buffer without persisting
php artisan logs:status         # show buffer key, count, batch size, fill %
php artisan logs:prune --days=30  # delete database records older than N days
```

The buffer flushes automatically when it reaches `batch_size`. For low-traffic apps that never hit that threshold, enable the built-in scheduled drain:

```env
HTTP_REQUEST_LOGGER_SCHEDULE=true
HTTP_REQUEST_LOGGER_SCHEDULE_CRON="* * * * *"
```

Or wire it yourself:

```php
Schedule::command('logs:flush')->everyMinute()->withoutOverlapping();
```

---

## Querying Logs

An Eloquent model is provided with query scopes for common filters:

```php
use Ddelosreyes\HttpRequestsLogger\Models\HttpRequestLog;

HttpRequestLog::incoming()->get();
HttpRequestLog::outgoing()->get();
HttpRequestLog::failed()->get();                    // status >= 400
HttpRequestLog::successful()->get();                // status < 400
HttpRequestLog::slow(500)->get();                   // duration_ms > 500
HttpRequestLog::withStatus(422)->get();
HttpRequestLog::fromIp('1.2.3.4')->get();
HttpRequestLog::forUrl('/api/users')->get();
HttpRequestLog::within(60)->get();                  // last 60 minutes
HttpRequestLog::forUser(42)->get();                 // by user ID
HttpRequestLog::withRequestId('abc-123')->get();    // by correlation ID
```

---

## Sensitive Data Masking

Fields listed under `mask.headers` and `mask.body` have their values replaced with `***` before the log is stored. Header matching is case-insensitive. Body masking is recursive.

```php
'mask' => [
    'headers' => ['Authorization', 'Cookie', 'X-Api-Key'],
    'body'    => ['password', 'token', 'secret', 'credit_card'],
],
```

---

## Response Body Logging

Disabled by default to avoid storing large payloads. Enable and cap the size:

```env
HTTP_REQUEST_LOGGER_LOG_RESPONSE_BODY=true
HTTP_REQUEST_LOGGER_MAX_BODY_SIZE=10240
```

Bodies larger than `max_body_size` bytes are stored truncated with a `...[truncated]` suffix.

---

## Storage Drivers

Set via `HTTP_REQUEST_LOGGER_STORAGE`. Default: `database`.

| Driver     | Notes |
|------------|-------|
| `database` | Any Laravel-supported DB (MySQL, PostgreSQL, SQLite). Single `INSERT` per batch. |
| `dynamodb` | Uses `batchWriteItem` in chunks of 25 with one unprocessed-item retry. |
| `redis`    | Permanent Redis/Valkey list. Suitable for short-lived retention. |
| `null`     | Discards all logs. Useful for disabling without touching the middleware. |

---

## Configuration

```php
return [
    // Master on/off switch — disable without removing middleware registration
    'enabled'     => env('HTTP_REQUEST_LOGGER_ENABLED', true),

    'storage'    => env('HTTP_REQUEST_LOGGER_STORAGE', 'database'),
    'batch_size' => env('HTTP_REQUEST_LOGGER_BATCH_SIZE', 500),

    // Log only a fraction of requests (0.0–1.0). 0.1 = ~10% of traffic.
    'sample_rate' => env('HTTP_REQUEST_LOGGER_SAMPLE_RATE', 1.0),

    'table'      => env('HTTP_REQUEST_LOGGER_TABLE', 'http_request_logs'),
    // Point logs at a dedicated DB connection instead of the default
    'connection' => env('HTTP_REQUEST_LOGGER_DB_CONNECTION', null),

    // Write directly to storage if Redis is unavailable instead of crashing
    'fallback_on_buffer_error' => env('HTTP_REQUEST_LOGGER_FALLBACK', true),

    // Log outgoing Http:: requests via the ResponseReceived event
    'log_outgoing' => env('HTTP_REQUEST_LOGGER_LOG_OUTGOING', false),

    // Capture auth()->id() on each log entry
    'log_user_id' => env('HTTP_REQUEST_LOGGER_LOG_USER_ID', false),

    // Header used to read/generate a request correlation ID
    'correlation_id_header' => env('HTTP_REQUEST_LOGGER_CORRELATION_ID_HEADER', 'X-Request-ID'),

    // Capture response bodies (both incoming and outgoing)
    'log_response_body' => env('HTTP_REQUEST_LOGGER_LOG_RESPONSE_BODY', false),
    'max_body_size'     => env('HTTP_REQUEST_LOGGER_MAX_BODY_SIZE', 10240),

    'mask' => [
        'headers' => [], // e.g. ['Authorization', 'Cookie']
        'body'    => [], // e.g. ['password', 'token']
    ],

    'exclude' => [
        'fields' => [], // e.g. ['headers', 'body']
        'paths'  => [], // e.g. ['/health', '/api/internal/*']
    ],

    'redis' => [
        'driver'   => env('HTTP_REQUEST_LOGGER_BUFFER_DRIVER', 'redis'), // "redis" or "valkey"
        'host'     => env('REDIS_HOST', '127.0.0.1'),
        'port'     => env('REDIS_PORT', 6379),
        'password' => env('REDIS_PASSWORD', null),
        'database' => env('REDIS_DB', 0),
        'prefix'   => env('HTTP_REQUEST_LOGGER_KEY_PREFIX', 'http_request_logs'),
    ],

    'redis_store' => [
        'max_entries' => env('HTTP_REQUEST_LOGGER_REDIS_MAX_ENTRIES', 10000),
    ],

    'dynamodb' => [
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'schedule' => [
        'enabled' => env('HTTP_REQUEST_LOGGER_SCHEDULE', false),
        'cron'    => env('HTTP_REQUEST_LOGGER_SCHEDULE_CRON', '* * * * *'),
    ],
];
```

---

## Testing

Requires a running Redis/Valkey instance. Use the included Docker setup:

```bash
make build
make test
```
