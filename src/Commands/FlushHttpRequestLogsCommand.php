<?php

declare(strict_types=1);

namespace Ddelosreyes\HttpRequestsLogger\Commands;

use Ddelosreyes\HttpRequestsLogger\Actions\RequestLogBufferAction;
use Illuminate\Console\Command;

class FlushHttpRequestLogsCommand extends Command
{
    protected $signature = 'logs:flush';

    protected $description = 'Flush all buffered HTTP request logs to storage';

    public function handle(): int
    {
        RequestLogBufferAction::flush();

        $this->info('HTTP request logs buffer flushed.');

        return self::SUCCESS;
    }
}
