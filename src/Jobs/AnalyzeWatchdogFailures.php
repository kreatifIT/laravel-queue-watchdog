<?php

namespace Kreatif\QueueWatchdog\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Notification;
use Kreatif\QueueWatchdog\Notifications\QueueAlert;
use Kreatif\QueueWatchdog\AnonymousNotifiable;

class AnalyzeWatchdogFailures implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $config = Config::get('queue-watchdog');
        $prefix = $config['cache_prefix'] ?? 'queue_watchdog_';
        $bucketKey = $prefix . 'bucket';
        $activeKey = $prefix . 'collection_active';
        $cooldownKey = $prefix . 'cooldown';

        // 1. Retrieve collected failures
        $failures = Cache::get($bucketKey, []);
        $count = count($failures);

        // 2. Check Thresholds
        $limit = $config['thresholds']['default']['failure_limit'] ?? 5;

        if ($count >= $limit) {
            // Trigger Alert
            $notifiable = new AnonymousNotifiable();
            try {
                Notification::send($notifiable, new QueueAlert($failures, $count));
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error("Queue Watchdog failed to send summary: " . $e->getMessage());
            }

            // 3. Set Cooldown
            $cooldownMinutes = $config['thresholds']['default']['cooldown_minutes'] ?? 5;
            if ($cooldownMinutes > 0) {
                Cache::put($cooldownKey, true, $cooldownMinutes * 60);
            }
        }

        // 4. Cleanup
        Cache::forget($bucketKey);
        Cache::forget($activeKey);
    }
}
