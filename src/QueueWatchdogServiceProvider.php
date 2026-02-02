<?php

namespace Kreatif\QueueWatchdog;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Kreatif\QueueWatchdog\Listeners\MonitorJobFailure;
use Illuminate\Support\Facades\Event;
use Illuminate\Queue\Events\JobFailed;

class QueueWatchdogServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-queue-watchdog')
            ->hasConfigFile();
    }

    public function packageBooted(): void
    {
        if (config('queue-watchdog.enabled')) {
            Event::listen(JobFailed::class, MonitorJobFailure::class);
        }
    }
}
