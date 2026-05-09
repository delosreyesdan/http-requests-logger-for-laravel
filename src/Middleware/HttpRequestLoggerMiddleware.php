<?php

declare(strict_types=1);

namespace Ddelosreyes\HttpRequestsLogger\Middleware;

use Closure;
use Ddelosreyes\HttpRequestsLogger\Actions\RequestLogBufferAction;
use Illuminate\Http\Request;

class HttpRequestLoggerMiddleware
{
    /**
     * @throws \JsonException
     */
    public function handle(Request $request, Closure $next): mixed
    {
        if ($this->shouldSkip($request)) {
            return $next($request);
        }

        $startedAt = microtime(true);

        $response = $next($request);

        RequestLogBufferAction::add($this->buildLog($request, $response, $startedAt));

        return $response;
    }

    private function shouldSkip(Request $request): bool
    {
        $paths = config('http-request-logger.exclude.paths', []);

        if (empty($paths)) {
            return false;
        }

        // $request->is() matches against $request->path() which strips leading
        // slashes, so normalize patterns to allow both '/health' and 'health'.
        $normalized = array_map(fn ($p) => ltrim($p, '/'), $paths);

        return $request->is(...$normalized);
    }

    private function buildLog(Request $request, mixed $response, float $startedAt): array
    {
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
        ];

        return $this->stripExcludedFields($log);
    }

    private function stripExcludedFields(array $log): array
    {
        $excluded = config('http-request-logger.exclude.fields', []);

        return empty($excluded) ? $log : array_diff_key($log, array_flip($excluded));
    }
}