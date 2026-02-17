<?php

namespace Kreatif\QueueWatchdog;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Kreatif\QueueWatchdog\Listeners\MonitorJobFailure;
use Illuminate\Support\Facades\Event;
use Illuminate\Queue\Events\JobFailed;
use Kreatif\QueueWatchdog\Services\WatchDogService;

class QueueWatchdogServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-queue-watchdog')
            ->hasConfigFile()
            ->hasCommand(\Kreatif\QueueWatchdog\Console\TestWatchdogCommand::class);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(WatchDogService::class, function ($app) {
            return new WatchDogService();
        });
    }

    public function packageBooted(): void
    {

        if (config('queue-watchdog.enabled')) {
            Event::listen(JobFailed::class, MonitorJobFailure::class);
        }
    }
}
