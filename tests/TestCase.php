<?php

namespace Ddelosreyes\HttpRequestsLogger\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Ddelosreyes\HttpRequestsLogger\Providers\HttpRequestLoggerServiceProvider;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            HttpRequestLoggerServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        // Use in-memory SQLite for testing
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Load package migrations into the in-memory DB
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->artisan('migrate', ['--database' => 'testing'])->run();
    }
}
