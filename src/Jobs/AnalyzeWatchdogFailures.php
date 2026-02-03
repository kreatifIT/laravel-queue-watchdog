<?php

namespace Kreatif\QueueWatchdog\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Kreatif\QueueWatchdog\Notifications\QueueAlert;
use Kreatif\QueueWatchdog\AnonymousNotifiable;
use Kreatif\QueueWatchdog\Services\WatchDogService;

class AnalyzeWatchdogFailures implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(WatchDogService $service): void
    {
        $bucketKey = $service->getBucketCacheKey();
        $activeKey = $service->getActiveCacheKey();
        $cooldownKey = $service->getCoolDownCacheKey();

        // 1. Retrieve collected failures
        $failures = Cache::get($bucketKey, []);
        $count = count($failures);

        // 2. Check Thresholds
        $limit = $service->getConfig('thresholds.default.failure_limit') ?? 5;

        if ($count >= $limit) {
            // Trigger Alert
            $notifiable = new AnonymousNotifiable();
            try {
                Notification::send($notifiable, new QueueAlert($failures, $count));
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error("Queue Watchdog failed to send summary: " . $e->getMessage());
            }

            // 3. Set Cooldown
            $cooldownMinutes = $service->getConfig('thresholds.default.cooldown_minutes') ?? 5;
            if ($cooldownMinutes > 0) {
                Cache::put($cooldownKey, true, $cooldownMinutes * 60);
            }
        }

        // 4. Cleanup
        Cache::forget($bucketKey);
        Cache::forget($activeKey);
    }
}