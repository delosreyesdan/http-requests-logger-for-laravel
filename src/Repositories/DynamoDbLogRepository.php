<?php

namespace Ddelosreyes\HttpRequestsLogger\Repositories;

use Ddelosreyes\HttpRequestsLogger\Contracts\HttpRequestLogRepository;
use Aws\DynamoDb\DynamoDbClient;

class DynamoDbLogRepository implements HttpRequestLogRepository
{
    protected DynamoDbClient $client;

    public function __construct()
    {
        $this->client = new DynamoDbClient([
            'region'  => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'version' => 'latest',
        ]);
    }

    public function storeBatch(array $logs): void
    {
        foreach ($logs as $log) {
            $this->client->putItem([
                'TableName' => config('http-request-logger.table'),
                'Item'      => array_map(fn($v) => ['S' => (string) $v], $log),
            ]);
        }
    }
}