<?php

namespace Kreatif\QueueWatchdog\Listeners;

use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Kreatif\QueueWatchdog\Notifications\QueueAlert;
use Illuminate\Support\Facades\Config;

class MonitorJobFailure
{
    public function handle(JobFailed $event): void
    {
        $config = Config::get('queue-watchdog');

        if (! $this->shouldMonitor($event->job->getQueue(), $config['queues'] ?? ['*'])) {
            return;
        }
        $key = $this->getCacheKey($event, $config);
        $failures = Cache::get($key, []);

        $now = time();
        $failures[] = $now;

        // Cleanup old failures outside the window
        $windowSeconds = ($config['thresholds']['default']['window_minutes'] ?? 10) * 60;
        $failures = array_filter($failures, fn($timestamp) => $timestamp > ($now - $windowSeconds));

        Cache::put($key, $failures, $windowSeconds);

        if (count($failures) >= ($config['thresholds']['default']['failure_limit'] ?? 5)) {
            $this->triggerAlert($event, $config, $key);
        }
    }

    protected function getCacheKey(JobFailed $event, array $config): string
    {
        $prefix = $config['cache_prefix'] ?? 'queue_watchdog_';
        $strategy = $config['aggregation'] ?? 'all';

        return match ($strategy) {
            'unique_jobs' => $prefix . 'failures:' . md5($event->job->resolveName()),
            'unique_exceptions' => $prefix . 'failures:' . md5(get_class($event->exception)),
            default => $prefix . 'failures:all',
        };
    }

    protected function shouldMonitor(string $queue, array $filters): bool
    {
        $excluded = array_filter($filters, fn($f) => str_starts_with($f, '!'));
        $included = array_filter($filters, fn($f) => ! str_starts_with($f, '!'));

        // Check exclusions first
        foreach ($excluded as $filter) {
            $pattern = ltrim($filter, '!');
            if (\Illuminate\Support\Str::is($pattern, $queue)) {
                return false;
            }
        }

        // If no inclusions are specified (and not excluded), monitor everything
        if (empty($included)) {
            return true;
        }

        // Check inclusions
        foreach ($included as $pattern) {
            if (\Illuminate\Support\Str::is($pattern, $queue)) {
                return true;
            }
        }

        return false;
    }

    protected function triggerAlert(JobFailed $event, array $config, string $key): void
    {
        $cooldownKey = $key . ':cooldown';

        if (Cache::has($cooldownKey)) {
            return;
        }

        $cooldownMinutes = $config['thresholds']['default']['cooldown_minutes'] ?? 30;
        Cache::put($cooldownKey, true, $cooldownMinutes * 60);

                $notifiable = new \Kreatif\QueueWatchdog\AnonymousNotifiable();

                

                try {

                    Notification::send($notifiable, new QueueAlert($event, count(Cache::get($key, []))));

                } catch (\Throwable $e) {

                    \Illuminate\Support\Facades\Log::error("Queue Watchdog failed to send notification: " . $e->getMessage());

                }

            }

        
}
