# 📦 Http Requests Logger for Laravel

#  🚧  WORK IN PROGRESS.

[![Latest Version on Packagist](https://img.shields.io/packagist/v//ddelosreyes/http-requests-logger-for-laravel.svg?style=flat-square)](https://packagist.org/packages//ddelosreyes/http-requests-logger-for-laravel)  

> Yet another HTTP request logger for Laravel — but built to be **fast, buffered, and flexible**.

This package logs incoming HTTP requests in **batches** to your preferred storage:
- ✅ **Database** (MySQL, Postgres, SQLite, etc.)
- ✅ **Redis buffer** (auto-flushed in batches)
- 🚧 DynamoDB support (coming soon)

---

## ✨ Features
- 🔄 **Buffered logging** — requests are first pushed to Redis for efficiency.
- 🗄 **Pluggable storage** — store logs in your DB or Redis (configurable).
- ⚡️ **Batch inserts** — reduces DB overhead by inserting multiple logs at once.
- 🎛 **Configurable batch size & Redis connection**.
- 🧪 **Pest + Testbench** powered test suite with Docker setup.

---

## 📥 Installation

Require the package via Composer:

```bash
composer require ddelosreyes/http-requests-logger-for-laravel
```

If auto-discovery is disabled, register the service provider manually:
```php
// config/app.php
'providers' => [
    Ddelosreyes\\HttpRequestsLogger\\HttpRequestsLoggerServiceProvider::class,
];
```

Publish the config and migration:
```bash
php artisan vendor:publish --provider="Ddelosreyes\\HttpRequestsLogger\\HttpRequestsLoggerServiceProvider" --tag=config
php artisan vendor:publish --provider="Ddelosreyes\\HttpRequestsLogger\\HttpRequestsLoggerServiceProvider" --tag=migrations
php artisan migrate
```

---
## ⚙️ Configuration


In the config/http-requests-logger.php
```php
return [
    'storage'    => env('HTTP_REQUEST_LOGGER_STORAGE', 'database'),
    'batch_size' => env('HTTP_REQUEST_LOGGER_BATCH_SIZE', 500),
    'table'      => env('HTTP_REQUEST_LOGGER_TABLE', 'http_request_logs'),

    'redis' => [
        'scheme'   => env('REDIS_SCHEME', 'tcp'),
        'host'     => env('REDIS_HOST', '127.0.0.1'),
        'port'     => env('REDIS_PORT', 6379),
        'password' => env('REDIS_PASSWORD', null),
        'database' => env('REDIS_DB', 0),
        'timeout'  => env('REDIS_TIMEOUT', 1.5),
    ],
];
```

In your dotenv
```dotenv
HTTP_REQUEST_LOGGER_STORAGE=database
HTTP_REQUEST_LOGGER_BATCH_SIZE=500
HTTP_REQUEST_LOGGER_TABLE=http_request_logs

REDIS_SCHEME=tcp
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=null
REDIS_DB=0
````

---
## 🔍 How It Works
1. Every incoming request is captured (method, URL, IP, user agent, headers, body)
2. Log payload is pushed to Redis (list)
3. Once the buffer reaches `batch_size`, logs are flushed into SQL in a single insert
4. Flush can also be triggered manually


---
## 📝 Logged Data

```php
[
  'method'     => $request->getMethod(),
  'url'        => $request->getRequestUri(),
  'ip'         => $request->getClientIp(),
  'user_agent' => $request->getUserAgent(),
  'headers'    => json_encode($request->headers->all()),
  'body'       => json_encode($request->all()),
]
```



---
## Usage
Option A: Middleware
```php
// app/Http/Kernel.php
protected $middleware = [
    \Ddelosreyes\HttpRequestsLogger\Http\Middleware\LogHttpRequest::class,
];
```

Option B: Manually log in controller/job
```php
use Ddelosreyes\HttpRequestsLogger\Actions\RequestLogBufferAction;

RequestLogBufferAction::add($request);
```



---
## 🧪 Testing
This package is built with PestPHP + Orchestra Testbench.

Run tests locally with Docker:

```bash
make build
make test
```