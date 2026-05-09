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
use Ddelosreyes\HttpRequestsLogger\Actions\RequestLogBufferAction;

RequestLogBufferAction::add([...]);
```

---

## Commands

```bash
php artisan logs:flush   # drain the buffer to storage
php artisan logs:clear   # discard the buffer without persisting
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
    'storage'    => env('HTTP_REQUEST_LOGGER_STORAGE', 'database'),
    'batch_size' => env('HTTP_REQUEST_LOGGER_BATCH_SIZE', 500),
    'table'      => env('HTTP_REQUEST_LOGGER_TABLE', 'http_request_logs'),

    // Write directly to storage if Redis is unavailable instead of crashing
    'fallback_on_buffer_error' => env('HTTP_REQUEST_LOGGER_FALLBACK', true),

    // Log outgoing Http:: requests via the ResponseReceived event
    'log_outgoing' => env('HTTP_REQUEST_LOGGER_LOG_OUTGOING', false),

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
