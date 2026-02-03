<?php

namespace Kreatif\QueueWatchdog\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Kreatif\QueueWatchdog\Notifications\QueueAlert;
use Kreatif\QueueWatchdog\AnonymousNotifiable;
use Kreatif\QueueWatchdog\Services\WatchDogService;

class AnalyzeWatchdogFailures implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $backoff = 10;

    public function handle(WatchDogService $service): void
    {
        $bucketKey = $service->getBucketCacheKey();
        $activeKey = $service->getActiveCacheKey();
        $cooldownKey = $service->getCoolDownCacheKey();

        // 1. Retrieve collected failures
        $failures = Cache::get($bucketKey, []);
        $count = count($failures);

        if ($count === 0) {
            // Nothing to report or already handled by another job
            Cache::forget($activeKey);
            return;
        }

        // 2. Check Thresholds
        // If limit is false/null/0, default to 1 (report any failure found)
        $limitConfig = $service->getConfig('thresholds.default.failure_limit');
        $limit = empty($limitConfig) ? 1 : (int) $limitConfig;

        if ($count >= $limit) {
            // Trigger Alert


            $notifiable = new AnonymousNotifiable();
            try {
                Notification::send($notifiable, new QueueAlert($failures, $count));
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error("Queue Watchdog failed to send summary: " . $e->getMessage());
            }

            $cooldownMinutes = $service->getConfig('thresholds.default.cooldown_minutes') ?? 0;
            if ($cooldownMinutes > 0) {
                Cache::put($cooldownKey, true, $cooldownMinutes * 60);
            }
        }

        Cache::forget($bucketKey);
        Cache::forget($activeKey);
    }
}
