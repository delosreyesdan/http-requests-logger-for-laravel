<?php

declare(strict_types=1);

namespace Ddelosreyes\HttpRequestsLogger\Commands;

use Ddelosreyes\HttpRequestsLogger\Models\HttpRequestLog;
use Illuminate\Console\Command;

class PruneHttpRequestLogsCommand extends Command
{
    protected $signature = 'logs:prune {--days=30 : Delete logs older than this many days}';

    protected $description = 'Prune HTTP request logs older than a given number of days';

    public function handle(): int
    {
        $driver = config('http-request-logger.storage', 'database');

        if ($driver !== 'database') {
            $this->warn("logs:prune only supports the 'database' driver. Current driver: '{$driver}'.");
            return self::FAILURE;
        }

        $days    = (int) $this->option('days');
        $deleted = HttpRequestLog::where('created_at', '<', now()->subDays($days))->delete();

        $this->info("Pruned {$deleted} log " . ($deleted === 1 ? 'entry' : 'entries') . " older than {$days} days.");

        return self::SUCCESS;
    }
}
