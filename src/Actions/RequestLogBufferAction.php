<?php

declare(strict_types=1);

namespace Ddelosreyes\HttpRequestsLogger\Actions;

use Ddelosreyes\HttpRequestsLogger\Contracts\HttpRequestLogRepository;
use Redis;

class RequestLogBufferAction
{
    protected static ?Redis $connection = null;

    public static function key(): string
    {
        return config('http-request-logger.redis.prefix', 'http_request_logs') . '_buffer';
    }

    public static function connection(): Redis
    {
        if (static::$connection !== null) {
            return static::$connection;
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

        static::$connection = $redis;

        return static::$connection;
    }

    /**
     * @throws \JsonException
     */
    public static function add(array $log): void
    {
        try {
            $redis = self::connection();
            $key   = self::key();

            $redis->rPush($key, json_encode($log, JSON_THROW_ON_ERROR));

            if ($redis->lLen($key) >= config('http-request-logger.batch_size')) {
                self::flush();
            }
        } catch (\RedisException $e) {
            if (config('http-request-logger.fallback_on_buffer_error', true)) {
                app(HttpRequestLogRepository::class)->storeBatch([$log]);
            }
        }
    }

    public static function clear(): void
    {
        try {
            self::connection()->del(self::key());
        } catch (\RedisException $e) {
            report($e);
        }
    }

    public static function resetConnection(): void
    {
        static::$connection = null;
    }

    public static function flush(): void
    {
        try {
            $redis     = self::connection();
            $key       = self::key();
            $batchSize = config('http-request-logger.batch_size');

            while ($redis->lLen($key) > 0) {
                $logs = [];

                for ($i = 0; $i < $batchSize; $i++) {
                    $item = $redis->lPop($key);

                    if ($item === false || $item === null) {
                        break;
                    }

                    $logs[] = json_decode($item, true);
                }

                if (! empty($logs)) {
                    app(HttpRequestLogRepository::class)->storeBatch($logs);
                }
            }
        } catch (\RedisException $e) {
            report($e);
        }
    }
}