<?php

use Ddelosreyes\HttpRequestsLogger\Actions\RequestLogBufferAction;
use Ddelosreyes\HttpRequestsLogger\Contracts\HttpRequestLogRepository;
use Ddelosreyes\HttpRequestsLogger\Repositories\DatabaseLogRepository;
use Illuminate\Support\Facades\DB;

it('adds log to redis buffer', function () {
    $log = [
        'method'     => 'POST',
        'url'        => '/api/users',
        'ip'         => '192.168.1.100',
        'user_agent' => 'PestTest/1.0',
        'headers'    => json_encode([
            'accept' => ['application/json'],
            'content-type' => ['application/json'],
        ]),
        'body'       => json_encode([
            'name'  => 'John Doe',
            'email' => 'john@example.com',
        ]),
    ];

    RequestLogBufferAction::add($log);

    $redis = new \Redis();

    $config = config('http-request-logger.redis');

    $redis->connect($config['host'], $config['port']);

    expect($redis->lLen('http_request_logs_buffer'))->toBeGreaterThan(0);
});

it('flushes logs into database', function () {
    DB::table(config('http-request-logger.table'))->truncate();

    $log = [
        'method'     => 'POST',
        'url'        => '/api/users',
        'ip'         => '192.168.1.100',
        'user_agent' => 'PestTest/1.0',
        'headers'    => json_encode([
            'accept' => ['application/json'],
            'content-type' => ['application/json'],
        ]),
        'body'       => json_encode([
            'name'  => 'John Doe',
            'email' => 'john@example.com',
        ]),
    ];


    RequestLogBufferAction::add($log);
    RequestLogBufferAction::flush();

    expect(DB::table(config('http-request-logger.table'))
        ->where('url', $log['url'])->exists())->toBeTrue();
});

it('uses the database repository by default', function () {
    $repo = app(HttpRequestLogRepository::class);

    expect($repo)->toBeInstanceOf(DatabaseLogRepository::class);

    $log = [
        'method'     => 'GET',
        'url'        => '/api/users',
        'ip'         => '192.168.1.100',
        'user_agent' => 'PestTest/1.0',
        'headers'    => json_encode([
            'accept' => ['application/json'],
            'content-type' => ['application/json'],
        ]),
        'body'       => null
    ];


    $repo->storeBatch([
        $log,
    ]);

    expect(DB::table(config('http-request-logger.table'))
        ->where('url', $log['url'])->exists())->toBeTrue();
});
