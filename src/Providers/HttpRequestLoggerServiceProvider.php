<?php

declare(strict_types=1);

namespace Ddelosreyes\HttpRequestsLogger\Providers;

use Ddelosreyes\HttpRequestsLogger\Actions\RequestLogBufferAction;
use Ddelosreyes\HttpRequestsLogger\Commands\ClearHttpRequestLogsCommand;
use Ddelosreyes\HttpRequestsLogger\Commands\FlushHttpRequestLogsCommand;
use Ddelosreyes\HttpRequestsLogger\Commands\PruneHttpRequestLogsCommand;
use Ddelosreyes\HttpRequestsLogger\Commands\StatusHttpRequestLogsCommand;
use Ddelosreyes\HttpRequestsLogger\Contracts\HttpRequestLogRepository;
use Ddelosreyes\HttpRequestsLogger\HttpRequestLogger;
use Ddelosreyes\HttpRequestsLogger\Repositories\DatabaseLogRepository;
use Ddelosreyes\HttpRequestsLogger\Repositories\DynamoDbLogRepository;
use Ddelosreyes\HttpRequestsLogger\Repositories\NullLogRepository;
use Ddelosreyes\HttpRequestsLogger\Repositories\RedisLogRepository;
use Ddelosreyes\HttpRequestsLogger\Support\LogMasker;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Client\Events\ResponseReceived;
use Illuminate\Support\Facades\Event;
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
                'dynamodb' => new DynamoDbLogRepository(),
                'redis'    => new RedisLogRepository(),
                'null'     => new NullLogRepository(),
                default    => new DatabaseLogRepository(),
            };
        });

        $this->app->singleton(HttpRequestLogger::class);
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

        if ($this->app->runningInConsole()) {
            $this->commands([
                FlushHttpRequestLogsCommand::class,
                ClearHttpRequestLogsCommand::class,
                PruneHttpRequestLogsCommand::class,
                StatusHttpRequestLogsCommand::class,
            ]);
        }

        if (config('http-request-logger.log_outgoing', false)) {
            $this->registerOutgoingRequestListener();
        }

        if (config('http-request-logger.schedule.enabled', false)) {
            $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
                $schedule->command('logs:flush')
                    ->cron(config('http-request-logger.schedule.cron', '* * * * *'))
                    ->withoutOverlapping();
            });
        }
    }

    private function registerOutgoingRequestListener(): void
    {
        Event::listen(ResponseReceived::class, function (ResponseReceived $event) {
            $header    = config('http-request-logger.correlation_id_header', 'X-Request-ID');
            $incoming  = app('request');
            $requestId = $incoming->attributes->get('_http_logger_request_id')
                ?? $incoming->header($header);

            $log = [
                'direction'   => 'out',
                'method'      => $event->request->method(),
                'url'         => $event->request->url(),
                'status'      => $event->response->status(),
                'ip'          => null,
                'user_agent'  => null,
                'headers'     => json_encode($event->request->headers(), JSON_THROW_ON_ERROR),
                'body'        => json_encode($event->request->data(), JSON_THROW_ON_ERROR),
                'duration_ms' => isset($event->response->transferStats)
                    ? (int) round($event->response->transferStats->getTransferTime() * 1000)
                    : null,
                'request_id'  => $requestId,
            ];

            if (config('http-request-logger.log_user_id', false)) {
                $log['user_id'] = auth()->id();
            }

            if (config('http-request-logger.log_response_body', false)) {
                $content         = $event->response->body();
                $maxSize         = (int) config('http-request-logger.max_body_size', 10240);
                $log['response_body'] = strlen($content) > $maxSize
                    ? substr($content, 0, $maxSize) . '...[truncated]'
                    : $content;
            }

            $log = LogMasker::mask($log);

            $excluded = config('http-request-logger.exclude.fields', []);
            if (! empty($excluded)) {
                $log = array_diff_key($log, array_flip($excluded));
            }

            RequestLogBufferAction::add($log);
        });
    }
}
