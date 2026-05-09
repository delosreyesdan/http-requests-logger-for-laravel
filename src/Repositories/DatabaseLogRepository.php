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

        DB::table(config('http-request-logger.table'))->insert($logs);
    }
}