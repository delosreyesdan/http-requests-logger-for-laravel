<?php

declare(strict_types=1);

namespace Ddelosreyes\HttpRequestsLogger\Repositories;

use Ddelosreyes\HttpRequestsLogger\Contracts\HttpRequestLogRepository;

class NullLogRepository implements HttpRequestLogRepository
{
    public function storeBatch(array $logs): void {}
}