<?php

declare(strict_types=1);

namespace Ddelosreyes\HttpRequestsLogger\Repositories;

use Aws\DynamoDb\DynamoDbClient;
use Ddelosreyes\HttpRequestsLogger\Contracts\HttpRequestLogRepository;
use Illuminate\Support\Str;

class DynamoDbLogRepository implements HttpRequestLogRepository
{
    // DynamoDB batchWriteItem hard limit
    private const DYNAMO_BATCH_LIMIT = 25;

    protected DynamoDbClient $client;

    public function __construct()
    {
        $this->client = new DynamoDbClient([
            'region'  => config('http-request-logger.dynamodb.region', 'us-east-1'),
            'version' => 'latest',
        ]);
    }

    public function storeBatch(array $logs): void
    {
        $table = config('http-request-logger.table');

        foreach (array_chunk($logs, self::DYNAMO_BATCH_LIMIT) as $chunk) {
            $unprocessed = $this->writeChunk($table, $chunk);

            // Retry unprocessed items once before giving up
            if (! empty($unprocessed)) {
                $this->writeChunk($table, $unprocessed, formatted: true);
            }
        }
    }

    private function writeChunk(string $table, array $items, bool $formatted = false): array
    {
        $requests = $formatted
            ? $items
            : array_map(fn($log) => ['PutRequest' => ['Item' => $this->formatItem($log)]], $items);

        $response = $this->client->batchWriteItem([
            'RequestItems' => [$table => $requests],
        ]);

        return $response['UnprocessedItems'][$table] ?? [];
    }

    private function formatItem(array $log): array
    {
        $item = ['id' => ['S' => (string) Str::uuid()]];

        foreach ($log as $key => $value) {
            $item[$key] = ['S' => (string) ($value ?? '')];
        }

        return $item;
    }
}