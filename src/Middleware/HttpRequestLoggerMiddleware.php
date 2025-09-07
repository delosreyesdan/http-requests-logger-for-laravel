<?php

namespace Ddelosreyes\HttpRequestsLogger\Middleware;

use Ddelosreyes\HttpRequestsLogger\Actions\RequestLogBufferAction;

class HttpRequestLoggerMiddleware
{
    /**
     * @throws \JsonException
     */
    public function handle($request, \Closure $next)
    {
        $response = $next($request);

        RequestLogBufferAction::add([
            'method' => $request->getMethod(),
            'url' => $request->getRequestUri(),
            'ip' => $request->getClientIp(),
            'user_agent' => $request->getUserAgent(),
            'headers' => json_encode($request->headers->all()),
            'body' => json_encode($request->all()),
        ]);

        return $response;
    }
}