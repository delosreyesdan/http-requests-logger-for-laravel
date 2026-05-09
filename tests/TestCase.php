<?php

namespace Ddelosreyes\HttpRequestsLogger\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as Orchestra;
use Ddelosreyes\HttpRequestsLogger\Providers\HttpRequestLoggerServiceProvider;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function getPackageProviders($app)
    {
        return [
            HttpRequestLoggerServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
    }

    protected function defineDatabaseMigrations(): void
    {
        Schema::create('http_request_logs', function (Blueprint $table) {
            $table->id();
            $table->char('direction', 3)->default('in')->index();
            $table->string('method', 10)->index();
            $table->text('url');
            $table->unsignedSmallInteger('status')->nullable()->index();
            $table->string('ip', 45)->nullable()->index();
            $table->text('user_agent')->nullable();
            $table->json('headers')->nullable();
            $table->json('body')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->timestamps();
        });
    }
}
