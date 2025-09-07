<?php

use Ddelosreyes\HttpRequestsLogger\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()
    ->uses(
    TestCase::class,
    RefreshDatabase::class,
    )->in('Feature', 'Unit');

