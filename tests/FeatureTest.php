<?php

namespace Kreatif\QueueWatchdog\Tests;

use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Illuminate\Queue\Events\JobFailed;
use Kreatif\QueueWatchdog\Notifications\QueueAlert;
use Kreatif\QueueWatchdog\Listeners\MonitorJobFailure;
use Illuminate\Support\Facades\Config;
use Mockery;

class FeatureTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        Cache::flush();
    }

    public function test_it_monitors_queue_failures_and_triggers_notification()
    {
        Config::set('queue-watchdog.thresholds.default.failure_limit', 2);
        Config::set('queue-watchdog.thresholds.default.window_minutes', 1);

        $event = new JobFailed(
            'test-connection',
            $this->mockJob('default'),
            new Exception('Test Exception')
        );

        $listener = new MonitorJobFailure();

        // First failure
        $listener->handle($event);
        Notification::assertNothingSent();

        // Second failure (reaches limit)
        $listener->handle($event);

        Notification::assertSentTo(new \Kreatif\QueueWatchdog\AnonymousNotifiable(), QueueAlert::class);
    }

    public function test_it_respects_queue_filters()
    {
        Config::set('queue-watchdog.queues', ['monitored']);
        Config::set('queue-watchdog.thresholds.default.failure_limit', 1);

        $listener = new MonitorJobFailure();

        // Failure on ignored queue
        $listener->handle(new JobFailed('test', $this->mockJob('ignored'), new Exception()));
        Notification::assertNothingSent();

        // Failure on monitored queue
        $listener->handle(new JobFailed('test', $this->mockJob('monitored'), new Exception()));
        Notification::assertSentTo(new \Kreatif\QueueWatchdog\AnonymousNotifiable(), QueueAlert::class);
    }

    public function test_it_respects_exclusions_and_wildcards()
    {
        Config::set('queue-watchdog.queues', ['*', '!secret', 'sync*']);
        Config::set('queue-watchdog.thresholds.default.failure_limit', 1);

        $listener = new MonitorJobFailure();

        // Excluded queue
        $listener->handle(new JobFailed('test', $this->mockJob('secret'), new Exception()));
        Notification::assertNothingSent();

        // Wildcard queue
        $listener->handle(new JobFailed('test', $this->mockJob('sync-users'), new Exception()));
        Notification::assertSentTo(new \Kreatif\QueueWatchdog\AnonymousNotifiable(), QueueAlert::class);
    }

    protected function mockJob($queue)
    {
        $job = Mockery::mock(\Illuminate\Contracts\Queue\Job::class);
        $job->allows()->getQueue()->andReturn($queue);
        $job->allows()->resolveName()->andReturn('TestJob');
        return $job;
    }
}