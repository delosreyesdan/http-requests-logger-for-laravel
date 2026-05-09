<?php

namespace Ddelosreyes\HttpRequestsLogger\Repositories;

use Ddelosreyes\HttpRequestsLogger\Contracts\HttpRequestLogRepository;
use Illuminate\Support\Facades\DB;

class DatabaseLogRepository implements HttpRequestLogRepository
{

    public function storeBatch(array $logs): void
    {
        $now = now()->toDateTimeString();

        $logs = array_map(fn($log) => $log + ['created_at' => $now, 'updated_at' => $now], $logs);

        $connection = config('http-request-logger.connection');
        $builder = $connection
            ? DB::connection($connection)->table(config('http-request-logger.table'))
            : DB::table(config('http-request-logger.table'));

        $builder->insert($logs);
    }
}