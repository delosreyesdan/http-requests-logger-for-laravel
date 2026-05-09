<?php

declare(strict_types=1);

namespace Ddelosreyes\HttpRequestsLogger\Commands;

use Ddelosreyes\HttpRequestsLogger\Actions\RequestLogBufferAction;
use Illuminate\Console\Command;

class StatusHttpRequestLogsCommand extends Command
{
    protected $signature = 'logs:status';

    protected $description = 'Show the current HTTP request logs buffer status';

    public function handle(): int
    {
        try {
            $redis     = RequestLogBufferAction::connection();
            $key       = RequestLogBufferAction::key();
            $buffered  = $redis->lLen($key);
            $batchSize = (int) config('http-request-logger.batch_size', 500);
            $fillPct   = $batchSize > 0 ? round(($buffered / $batchSize) * 100, 1) : 0;

            $this->table(
                ['Buffer Key', 'Buffered', 'Batch Size', 'Fill %'],
                [[$key, number_format($buffered), number_format($batchSize), "{$fillPct}%"]]
            );
        } catch (\RedisException $e) {
            $this->error('Redis unavailable: ' . $e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
