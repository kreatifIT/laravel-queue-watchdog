<?php

namespace Kreatif\QueueWatchdog\Listeners;

use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Cache;
use Kreatif\QueueWatchdog\Jobs\AnalyzeWatchdogFailures;
use Kreatif\QueueWatchdog\Services\WatchDogService;

class MonitorJobFailure
{
    public function __construct(
        protected WatchDogService $service
    ) {}

    public function handle(JobFailed $event): void
    {
        // 1. Filter
        if (! $this->service->shouldMonitor($event->job->getQueue())) {
            return;
        }

        $activeKey = $this->service->getActiveCacheKey();
        $bucketKey = $this->service->getBucketCacheKey();
        $cooldownKey = $this->service->getCoolDownCacheKey();

        // 2. Check Cooldown (Only if we are NOT already collecting)
        // If we are collecting, we keep adding to the bucket regardless of cooldown.
        // If we are NOT collecting, we check if we should start.
        if (! Cache::has($activeKey)) {
            if (Cache::has($cooldownKey)) {
                return; // Ignore failures during cooldown
            }

            // Start New Collection Window
            $windowMinutes = $this->service->getConfig('thresholds.default.window_minutes') ?? 5;
            
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
        $windowMinutes = $this->service->getConfig('thresholds.default.window_minutes') ?? 5;
        $ttl = $windowMinutes * 60 + 60; 
        Cache::put($bucketKey, $failures, $ttl);
    }
}