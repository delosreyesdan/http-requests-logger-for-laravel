<?php

namespace Ddelosreyes\HttpRequestsLogger\Contracts;

interface HttpRequestLogRepository
{
    public function storeBatch(array $logs): void;
}