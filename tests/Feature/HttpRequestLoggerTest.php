<?php

use Ddelosreyes\HttpRequestsLogger\Actions\RequestLogBufferAction;
use Ddelosreyes\HttpRequestsLogger\Contracts\HttpRequestLogRepository;
use Ddelosreyes\HttpRequestsLogger\Middleware\HttpRequestLoggerMiddleware;
use Ddelosreyes\HttpRequestsLogger\Repositories\DatabaseLogRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    RequestLogBufferAction::resetConnection();
    RequestLogBufferAction::clear();
    DB::table(config('http-request-logger.table'))->truncate();
});

afterEach(function () {
    RequestLogBufferAction::resetConnection();
});

// ---------------------------------------------------------------------------
// Buffer
// ---------------------------------------------------------------------------

it('adds a log to the redis buffer', function () {
    $log = sampleLog();

    RequestLogBufferAction::add($log);

    $redis = RequestLogBufferAction::connection();
    $key   = RequestLogBufferAction::key();

    expect($redis->lLen($key))->toBeGreaterThan(0);
});

it('flushes logs into the database', function () {
    RequestLogBufferAction::add(sampleLog(['url' => '/flush-test']));
    RequestLogBufferAction::flush();

    expect(DB::table(config('http-request-logger.table'))
        ->where('url', '/flush-test')->exists()
    )->toBeTrue();
});

it('flush drains more than batch_size items in one call', function () {
    config(['http-request-logger.batch_size' => 2]);

    $redis = RequestLogBufferAction::connection();
    $key   = RequestLogBufferAction::key();

    for ($i = 1; $i <= 5; $i++) {
        $redis->rPush($key, json_encode(sampleLog(['url' => "/item/$i"])));
    }

    RequestLogBufferAction::flush();

    expect(DB::table(config('http-request-logger.table'))->count())->toBe(5);
});

// ---------------------------------------------------------------------------
// Repository
// ---------------------------------------------------------------------------

it('uses the database repository by default', function () {
    $repo = app(HttpRequestLogRepository::class);

    expect($repo)->toBeInstanceOf(DatabaseLogRepository::class);

    $repo->storeBatch([sampleLog(['url' => '/repo-test'])]);

    expect(DB::table(config('http-request-logger.table'))
        ->where('url', '/repo-test')->exists()
    )->toBeTrue();
});

// ---------------------------------------------------------------------------
// Middleware
// ---------------------------------------------------------------------------

it('middleware logs status code and duration', function () {
    $request  = Request::create('/mw-test', 'GET');
    $response = response('ok', 201);

    (new HttpRequestLoggerMiddleware())->handle($request, fn () => $response);

    RequestLogBufferAction::flush();

    expect(DB::table(config('http-request-logger.table'))
        ->where('url', '/mw-test')
        ->where('status', 201)
        ->whereNotNull('duration_ms')
        ->exists()
    )->toBeTrue();
});

it('middleware skips excluded paths', function () {
    config(['http-request-logger.exclude.paths' => ['/healthz']]);

    $request  = Request::create('/healthz', 'GET');
    $response = response('ok', 200);

    (new HttpRequestLoggerMiddleware())->handle($request, fn () => $response);

    RequestLogBufferAction::flush();

    expect(DB::table(config('http-request-logger.table'))
        ->where('url', '/healthz')->exists()
    )->toBeFalse();
});

// ---------------------------------------------------------------------------
// Fallback
// ---------------------------------------------------------------------------

it('falls back to database when redis is unavailable', function () {
    config([
        'http-request-logger.redis.host'             => '127.0.0.1',
        'http-request-logger.redis.port'             => 9,
        'http-request-logger.fallback_on_buffer_error' => true,
    ]);

    RequestLogBufferAction::resetConnection();

    RequestLogBufferAction::add(sampleLog(['url' => '/fallback-test']));

    expect(DB::table(config('http-request-logger.table'))
        ->where('url', '/fallback-test')->exists()
    )->toBeTrue();
});

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function sampleLog(array $overrides = []): array
{
    return array_merge([
        'direction'   => 'in',
        'method'      => 'GET',
        'url'         => '/api/test',
        'status'      => 200,
        'ip'          => '127.0.0.1',
        'user_agent'  => 'PestTest/1.0',
        'headers'     => json_encode(['accept' => ['application/json']]),
        'body'        => json_encode(['key' => 'value']),
        'duration_ms' => 10,
    ], $overrides);
}
