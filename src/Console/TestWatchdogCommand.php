<?php

namespace Kreatif\QueueWatchdog\Console;

use Illuminate\Console\Command;
use Kreatif\QueueWatchdog\Jobs\TestFailJob;

class TestWatchdogCommand extends Command
{

    protected $signature = 'queue-watchdog:test {count=1} {queue=watchdog-test}';

    protected $description = 'Dispatch failing jobs to a specific queue to test the Watchdog if working or not';

    public function handle(): void
    {
        $count = (int) $this->argument('count');
        $queue = $this->argument('queue');

        $this->info("Dispatching {$count} test failure(s) to the [{$queue}] queue...");

        for ($i = 0; $i < $count; $i++) {
            TestFailJob::dispatch()->onQueue($queue);
        }

        $this->info("Done.");
        $this->newLine();
        $this->warn("IMPORTANT: Ensure you have a worker running for the [{$queue}] queue.");
        $this->line("Example: php artisan queue:work --queue={$queue} --once");
        $this->newLine();
        $this->comment("The watchdog will collect these failures and notify you once your configured threshold is reached.");
    }
}
