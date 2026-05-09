<?php

declare(strict_types=1);

namespace Ddelosreyes\HttpRequestsLogger\Middleware;

use Closure;
use Ddelosreyes\HttpRequestsLogger\Actions\RequestLogBufferAction;
use Ddelosreyes\HttpRequestsLogger\Support\LogMasker;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class HttpRequestLoggerMiddleware
{
    /**
     * @throws \JsonException
     */
    public function handle(Request $request, Closure $next): mixed
    {
        if (!$this->shouldLog($request)) {
            return $next($request);
        }

        $startedAt = microtime(true);
        $response  = $next($request);

        $log = $this->buildLog($request, $response, $startedAt);
        $log = LogMasker::mask($log);
        $log = $this->stripExcludedFields($log);

        RequestLogBufferAction::add($log);

        return $response;
    }

    private function shouldLog(Request $request): bool
    {
        if (!config('http-request-logger.enabled', true)) {
            return false;
        }

        $sampleRate = (float) config('http-request-logger.sample_rate', 1.0);
        if ($sampleRate < 1.0 && (mt_rand() / mt_getrandmax()) > $sampleRate) {
            return false;
        }

        $paths = config('http-request-logger.exclude.paths', []);
        if (!empty($paths)) {
            $normalized = array_map(fn($p) => ltrim($p, '/'), $paths);
            if ($request->is(...$normalized)) {
                return false;
            }
        }

        return true;
    }

    private function buildLog(Request $request, mixed $response, float $startedAt): array
    {
        $header    = config('http-request-logger.correlation_id_header', 'X-Request-ID');
        $requestId = $request->header($header) ?? (string) Str::uuid();

        // Store on request attributes so outgoing listeners in the same cycle share the same ID.
        $request->attributes->set('_http_logger_request_id', $requestId);

        $log = [
            'direction'   => 'in',
            'method'      => $request->getMethod(),
            'url'         => $request->getRequestUri(),
            'status'      => $response->getStatusCode(),
            'ip'          => $request->getClientIp(),
            'user_agent'  => $request->userAgent(),
            'headers'     => json_encode($request->headers->all(), JSON_THROW_ON_ERROR),
            'body'        => json_encode($request->all(), JSON_THROW_ON_ERROR),
            'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            'request_id'  => $requestId,
        ];

        if (config('http-request-logger.log_user_id', false)) {
            $log['user_id'] = auth()->id();
        }

        if (config('http-request-logger.log_response_body', false)) {
            $content         = (string) $response->getContent();
            $maxSize         = (int) config('http-request-logger.max_body_size', 10240);
            $log['response_body'] = strlen($content) > $maxSize
                ? substr($content, 0, $maxSize) . '...[truncated]'
                : $content;
        }

        return $log;
    }

    private function stripExcludedFields(array $log): array
    {
        $excluded = config('http-request-logger.exclude.fields', []);

        return empty($excluded) ? $log : array_diff_key($log, array_flip($excluded));
    }
}
