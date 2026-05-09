<?php

declare(strict_types=1);

namespace Ddelosreyes\HttpRequestsLogger;

use Ddelosreyes\HttpRequestsLogger\Actions\RequestLogBufferAction;

class HttpRequestLogger
{
    public function add(array $log): void
    {
        RequestLogBufferAction::add($log);
    }

    public function flush(): void
    {
        RequestLogBufferAction::flush();
    }

    public function clear(): void
    {
        RequestLogBufferAction::clear();
    }

    public function status(): array
    {
        try {
            $redis     = RequestLogBufferAction::connection();
            $buffered  = $redis->lLen(RequestLogBufferAction::key());
            $batchSize = (int) config('http-request-logger.batch_size', 500);

            return [
                'key'        => RequestLogBufferAction::key(),
                'buffered'   => $buffered,
                'batch_size' => $batchSize,
                'fill_pct'   => $batchSize > 0 ? round(($buffered / $batchSize) * 100, 1) : 0,
            ];
        } catch (\RedisException $e) {
            return [
                'key'        => RequestLogBufferAction::key(),
                'buffered'   => null,
                'batch_size' => config('http-request-logger.batch_size', 500),
                'fill_pct'   => null,
                'error'      => $e->getMessage(),
            ];
        }
    }
}
