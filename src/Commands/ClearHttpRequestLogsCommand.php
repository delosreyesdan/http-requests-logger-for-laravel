<?php

declare(strict_types=1);

namespace Ddelosreyes\HttpRequestsLogger\Commands;

use Ddelosreyes\HttpRequestsLogger\Actions\RequestLogBufferAction;
use Illuminate\Console\Command;

class ClearHttpRequestLogsCommand extends Command
{
    protected $signature = 'logs:clear';

    protected $description = 'Discard the HTTP request logs buffer without persisting';

    public function handle(): int
    {
        RequestLogBufferAction::clear();

        $this->info('HTTP request logs buffer cleared.');

        return self::SUCCESS;
    }
}
