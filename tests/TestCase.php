<?php

namespace Stboris\FilamentOutbox\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Stboris\FilamentOutbox\FilamentOutboxServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            FilamentOutboxServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
    }
}
