<?php

declare(strict_types=1);

namespace Ddelosreyes\HttpRequestsLogger\Support;

class LogMasker
{
    public static function mask(array $log): array
    {
        $mask = config('http-request-logger.mask', []);

        if (empty($mask)) {
            return $log;
        }

        if (! empty($mask['headers']) && isset($log['headers'])) {
            $headers = json_decode($log['headers'], true) ?? [];

            foreach ($mask['headers'] as $header) {
                $key = strtolower($header);
                if (isset($headers[$key])) {
                    $headers[$key] = ['***'];
                }
            }

            $log['headers'] = json_encode($headers, JSON_THROW_ON_ERROR);
        }

        if (! empty($mask['body']) && isset($log['body'])) {
            $body        = json_decode($log['body'], true) ?? [];
            $log['body'] = json_encode(self::maskNestedKeys($body, $mask['body']), JSON_THROW_ON_ERROR);
        }

        return $log;
    }

    private static function maskNestedKeys(array $data, array $keys): array
    {
        foreach ($data as $k => $v) {
            if (in_array($k, $keys, true)) {
                $data[$k] = '***';
            } elseif (is_array($v)) {
                $data[$k] = self::maskNestedKeys($v, $keys);
            }
        }

        return $data;
    }
}
