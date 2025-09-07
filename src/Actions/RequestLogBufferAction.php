<?php

declare(strict_types=1);

namespace Ddelosreyes\HttpRequestsLogger\Actions;

use Ddelosreyes\HttpRequestsLogger\Contracts\HttpRequestLogRepository;
use Redis;

class RequestLogBufferAction
{
    protected static string $key = "http_request_logs_buffer";

    public static function connection(): Redis
    {
        $redis = new Redis();

        $redis->connect(
            config('http-request-logger.redis.host', "127.0.0.1"),
            (int) config('http-request-logger.redis.port', 6379)
        );

        if ($password = config('http-request-logger.redis.password')) {
            $redis->auth($password);
        }

        if ($db = config('http-request-logger.redis.database')) {
            $redis->select($db);
        }

        return $redis;
    }

    /**
     * @throws \JsonException
     */
    public static function add(
        array $log
    ): void
    {
        $redis = self::connection();

        $redis->rPush(
            static::$key,
            json_encode($log, JSON_THROW_ON_ERROR)
        );

        if ($redis->lLen(static::$key) >= config('http-request-logger.batch_size')) {
            self::flush($redis);
        }
    }

    public static function flush($redis = null): void
    {
        $redis = $redis ?: self::connection();

        $logs = [];

        for ($i = 0; $i < config('http-request-logger.batch_size'); $i++) {
            $item = $redis->lPop(static::$key);

            if (empty($item)) {
                break;
            }

            $logs[] = json_decode($item, true);
        }

        if (! empty($logs)) {
            app(HttpRequestLogRepository::class)->storeBatch($logs);
        }
    }
}