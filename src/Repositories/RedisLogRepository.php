<?php

declare(strict_types=1);

namespace Ddelosreyes\HttpRequestsLogger\Repositories;

use Ddelosreyes\HttpRequestsLogger\Contracts\HttpRequestLogRepository;
use Redis;

class RedisLogRepository implements HttpRequestLogRepository
{
    protected ?Redis $connection = null;

    public function storeBatch(array $logs): void
    {
        $redis   = $this->connection();
        $key     = config('http-request-logger.table');
        $maxSize = (int) config('http-request-logger.redis_store.max_entries', 10000);

        foreach ($logs as $log) {
            $redis->rPush($key, json_encode($log, JSON_THROW_ON_ERROR));
        }

        // Cap the list so it never grows unbounded in memory.
        if ($maxSize > 0) {
            $redis->lTrim($key, -$maxSize, -1);
        }
    }

    protected function connection(): Redis
    {
        if ($this->connection !== null) {
            return $this->connection;
        }

        $redis = new Redis();

        $redis->connect(
            config('http-request-logger.redis.host', '127.0.0.1'),
            (int) config('http-request-logger.redis.port', 6379)
        );

        if ($password = config('http-request-logger.redis.password')) {
            $redis->auth($password);
        }

        if ($db = config('http-request-logger.redis.database')) {
            $redis->select($db);
        }

        $this->connection = $redis;

        return $this->connection;
    }
}
