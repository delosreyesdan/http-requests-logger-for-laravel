<?php

namespace Ddelosreyes\HttpRequestsLogger\Providers;

use Ddelosreyes\HttpRequestsLogger\Contracts\HttpRequestLogRepository;
use Ddelosreyes\HttpRequestsLogger\Repositories\DatabaseLogRepository;
use Ddelosreyes\HttpRequestsLogger\Repositories\DynamoDbLogRepository;
use Illuminate\Support\ServiceProvider;

class HttpRequestLoggerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/http-request-logger.php', 'http-request-logger'
        );

        $this->app->bind(HttpRequestLogRepository::class, function ($app) {
            return match(config('http-request-logger.storage')) {
                'dynamodb' => new DynamoDBLogRepository(),
                default => new DatabaseLogRepository()
            };
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../../config/http-request-logger.php' => config_path('http-request-logger.php'),
        ], 'config');


        $this->publishes([
            __DIR__ . '/../../database/migrations/create_http_request_logs_table.php.stub'
            => database_path('migrations/'.date('Y_m_d_His').'_create_http_request_logs_table.php'),
        ], 'migrations');
    }
}