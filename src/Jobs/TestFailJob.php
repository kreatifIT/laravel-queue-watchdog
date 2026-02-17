<?php

namespace Kreatif\QueueWatchdog\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Exception;

class TestFailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        throw new Exception("Queue Watchdog Test: This is a deliberate failure to verify that the monitoring system and notifications are working correctly.");
    }
}
