<?php

namespace Kreatif\QueueWatchdog\Tests;

use Kreatif\QueueWatchdog\QueueWatchdogServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            QueueWatchdogServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');
        config()->set('queue-watchdog.enabled', true);
    }
}
