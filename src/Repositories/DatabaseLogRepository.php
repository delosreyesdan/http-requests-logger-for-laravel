<?php

namespace Ddelosreyes\HttpRequestsLogger\Repositories;

use Ddelosreyes\HttpRequestsLogger\Contracts\HttpRequestLogRepository;
use Illuminate\Support\Facades\DB;

class DatabaseLogRepository implements HttpRequestLogRepository
{

    public function storeBatch(array $logs): void
    {
        DB::table(config('http-request-logger.table'))->insert($logs);
    }
}