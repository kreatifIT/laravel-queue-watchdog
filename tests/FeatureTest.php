<?php

namespace Kreatif\QueueWatchdog\Tests;

use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Bus;
use Illuminate\Queue\Events\JobFailed;
use Kreatif\QueueWatchdog\Notifications\QueueAlert;
use Kreatif\QueueWatchdog\Listeners\MonitorJobFailure;
use Kreatif\QueueWatchdog\Jobs\AnalyzeWatchdogFailures;
use Illuminate\Support\Facades\Config;
use Mockery;

class FeatureTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        Bus::fake(); // Fake the job dispatching
        Cache::flush();
    }

    public function test_it_schedules_analysis_job_on_first_failure()
    {
        Config::set('queue-watchdog.thresholds.default.window_minutes', 5);

        $event = new JobFailed('test', $this->mockJob('default'), new Exception('Error'));
        $listener = app(MonitorJobFailure::class);

        $listener->handle($event);

        // Should dispatch the analysis job
        Bus::assertDispatched(AnalyzeWatchdogFailures::class, function ($job) {
            return !is_null($job->delay);
        });

        // Failures should be in cache
        $this->assertCount(1, Cache::get('queue_watchdog_bucket'));
    }

    public function test_it_collects_failures_during_window()
    {
        // Start window
        Cache::put('queue_watchdog_collection_active', true, 300);
        Cache::put('queue_watchdog_bucket', [['job' => 'OldJob']]);

        $event = new JobFailed('test', $this->mockJob('default'), new Exception('Error'));
        $listener = app(MonitorJobFailure::class);

        $listener->handle($event);

        // Should NOT dispatch new job (window active)
        Bus::assertNotDispatched(AnalyzeWatchdogFailures::class);

        // Bucket should grow
        $this->assertCount(2, Cache::get('queue_watchdog_bucket'));
    }

    public function test_analysis_job_triggers_notification_if_limit_reached()
    {
        Config::set('queue-watchdog.thresholds.default.failure_limit', 2);
        
        // Populate bucket
        Cache::put('queue_watchdog_bucket', [
            ['job' => 'Job1', 'queue' => 'default', 'exception' => 'E1', 'failed_at' => now()],
            ['job' => 'Job2', 'queue' => 'default', 'exception' => 'E2', 'failed_at' => now()],
        ]);

        $job = new AnalyzeWatchdogFailures();
        $job->handle(app(\Kreatif\QueueWatchdog\Services\WatchDogService::class));

        Notification::assertSentTo(new \Kreatif\QueueWatchdog\AnonymousNotifiable(), QueueAlert::class, function ($notification) {
            return $notification->count === 2;
        });
    }

    public function test_analysis_job_sets_cooldown()
    {
        Config::set('queue-watchdog.thresholds.default.failure_limit', 1);
        Config::set('queue-watchdog.thresholds.default.cooldown_minutes', 10);
        
        Cache::put('queue_watchdog_bucket', [['job' => 'Job1']]);

        $job = new AnalyzeWatchdogFailures();
        $job->handle(app(\Kreatif\QueueWatchdog\Services\WatchDogService::class));

        $this->assertTrue(Cache::has('queue_watchdog_cooldown'));
    }

    public function test_it_ignores_analysis_job_failure()
    {
        $job = Mockery::mock(\Illuminate\Contracts\Queue\Job::class);
        $job->allows()->getQueue()->andReturn('default');
        $job->allows()->resolveName()->andReturn(AnalyzeWatchdogFailures::class);

        $event = new JobFailed('test', $job, new Exception('Error'));
        $listener = app(MonitorJobFailure::class);

        $listener->handle($event);

        // Should NOT dispatch analysis job
        Bus::assertNotDispatched(AnalyzeWatchdogFailures::class);
        // Should NOT be in cache
        $this->assertFalse(Cache::has('queue_watchdog_bucket'));
    }

    protected function mockJob($queue)
    {
        $job = Mockery::mock(\Illuminate\Contracts\Queue\Job::class);
        $job->allows()->getQueue()->andReturn($queue);
        $job->allows()->resolveName()->andReturn('TestJob');
        return $job;
    }
}
