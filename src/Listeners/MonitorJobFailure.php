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

        if (! $this->service->shouldMonitor($event->job->getQueue(), $event->job->resolveName())) {
            return;
        }

        $activeKey = $this->service->getActiveCacheKey();
        $bucketKey = $this->service->getBucketCacheKey();
        $cooldownKey = $this->service->getCoolDownCacheKey();


        if (! Cache::has($activeKey)) {
            if (Cache::has($cooldownKey)) {
                return; // Ignore failures during cooldown
            }

            $windowConfig = $this->service->getConfig('thresholds.default.window_minutes');
            // If null, default to 5. If false/0, treat as 0 (immediate).
            $windowMinutes = $windowConfig === null ? 5 : (int) $windowConfig;

            // If 0, use a small buffer (e.g. 1 sec) to allow current process to finish
            $ttl = $windowMinutes > 0 ? $windowMinutes * 60 : 1;
            Cache::put($activeKey, true, $ttl);

            // Note: In 'sync' driver, this runs immediately.
            AnalyzeWatchdogFailures::dispatch()
                ->delay(now()->addMinutes($windowMinutes));
        }

        $failures = Cache::get($bucketKey, []);
        $failures[] = [
            'job' => $event->job->resolveName(),
            'queue' => $event->job->getQueue(),
            'exception' => $event->exception->getMessage(),
            'failed_at' => now()->toDateTimeString(),
        ];

        // Store with a TTL slightly longer than the window to ensure the Job finds it
        $windowConfig = $this->service->getConfig('thresholds.default.window_minutes');
        $windowMinutes = $windowConfig === null ? 5 : (int) $windowConfig;
        
        $ttl = $windowMinutes * 60 + 60;
        Cache::put($bucketKey, $failures, $ttl);
    }
}
