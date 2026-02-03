<?php

namespace Kreatif\QueueWatchdog\Listeners;

use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Kreatif\QueueWatchdog\Jobs\AnalyzeWatchdogFailures;
use Illuminate\Support\Str;

class MonitorJobFailure
{
    public function handle(JobFailed $event): void
    {
        $config = Config::get('queue-watchdog');

        // 1. Filter
        if (! $this->shouldMonitor($event->job->getQueue(), $config['queues'] ?? ['*'])) {
            return;
        }

        $prefix = $config['cache_prefix'] ?? 'queue_watchdog_';
        $activeKey = $prefix . 'collection_active';
        $bucketKey = $prefix . 'bucket';
        $cooldownKey = $prefix . 'cooldown';

        // 2. Check Cooldown (Only if we are NOT already collecting)
        // If we are collecting, we keep adding to the bucket regardless of cooldown.
        // If we are NOT collecting, we check if we should start.
        if (! Cache::has($activeKey)) {
            if (Cache::has($cooldownKey)) {
                return; // Ignore failures during cooldown
            }

            // Start New Collection Window
            $windowMinutes = $config['thresholds']['default']['window_minutes'] ?? 5;
            
            // Mark collection as active for the window duration
            Cache::put($activeKey, true, $windowMinutes * 60);

            // Schedule the analysis job at the end of the window
            // Note: In 'sync' driver, this runs immediately.
            AnalyzeWatchdogFailures::dispatch()
                ->delay(now()->addMinutes($windowMinutes));
        }

        // 3. Add Failure to Bucket
        $failures = Cache::get($bucketKey, []);
        $failures[] = [
            'job' => $event->job->resolveName(),
            'queue' => $event->job->getQueue(),
            'exception' => $event->exception->getMessage(),
            'failed_at' => now()->toDateTimeString(),
        ];

        // Store with a TTL slightly longer than the window to ensure the Job finds it
        $ttl = ($config['thresholds']['default']['window_minutes'] ?? 5) * 60 + 60; 
        Cache::put($bucketKey, $failures, $ttl);
    }

    protected function shouldMonitor(string $queue, array $filters): bool
    {
        $excluded = array_filter($filters, fn($f) => str_starts_with($f, '!'));
        $included = array_filter($filters, fn($f) => ! str_starts_with($f, '!'));

        foreach ($excluded as $filter) {
            $pattern = ltrim($filter, '!');
            if (Str::is($pattern, $queue)) return false;
        }

        if (empty($included)) return true;

        foreach ($included as $pattern) {
            if (Str::is($pattern, $queue)) return true;
        }

        return false;
    }
}