<?php

declare(strict_types=1);

namespace Ddelosreyes\HttpRequestsLogger\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static void  add(array $log)
 * @method static void  flush()
 * @method static void  clear()
 * @method static array status()
 *
 * @see \Ddelosreyes\HttpRequestsLogger\HttpRequestLogger
 */
class HttpRequestLogger extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Ddelosreyes\HttpRequestsLogger\HttpRequestLogger::class;
    }
}
