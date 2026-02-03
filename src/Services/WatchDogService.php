<?php

namespace Kreatif\QueueWatchdog\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

class WatchDogService
{
    public function getConfig(?string $key = null): mixed
    {
        $config = Config::get('queue-watchdog', []);
        if ($key !== null) {
            return Arr::get($config, $key);
        }
        return $config;
    }

    public function getCoolDownCacheKey(): string
    {
        $prefix = $this->getConfig('cache_prefix') ?? 'queue_watchdog_';
        return $prefix . 'cooldown';
    }

    public function getBucketCacheKey(): string {
        $prefix = $this->getConfig('cache_prefix') ?? 'queue_watchdog_';
        return $prefix . 'bucket';
    }

    public function getActiveCacheKey(): string {
        $prefix = $this->getConfig('cache_prefix') ?? 'queue_watchdog_';
        return $prefix . 'collection_active';
    }

    public function shouldMonitor(string $queue, ?string $jobName = null): bool
    {
        // Ignore our own internal analysis job to prevent infinite loops
        if ($jobName === \Kreatif\QueueWatchdog\Jobs\AnalyzeWatchdogFailures::class) {
            return false;
        }

        $filters = $this->getConfig('queues') ?? ['*'];

        $excluded = array_filter($filters, fn($f) => str_starts_with($f, '!'));
        $included = array_filter($filters, fn($f) => ! str_starts_with($f, '!'));

        // Check exclusions first
        foreach ($excluded as $filter) {
            $pattern = ltrim($filter, '!');
            if (Str::is($pattern, $queue)) {
                return false;
            }
        }

        // If no inclusions are specified (and not excluded), monitor everything
        if (empty($included)) {
            return true;
        }

        // Check inclusions
        foreach ($included as $pattern) {
            if (Str::is($pattern, $queue)) {
                return true;
            }
        }

        return false;
    }
}