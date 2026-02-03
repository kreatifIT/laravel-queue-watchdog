<?php

namespace Kreatif\QueueWatchdog\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackMessage;

class QueueAlert extends Notification
{
    public function __construct(
        public array $failures, // Array of failure data
        public int $count
    ) {}

    public function via($notifiable): array
    {
        return array_keys(array_filter(config('queue-watchdog.notifications', []), fn($config) => !empty($config)));
    }

    public function toMail($notifiable): MailMessage
    {
        $lastFailure = end($this->failures);
        
        return (new MailMessage)
            ->error()
            ->subject("Queue Watchdog Alert: {$this->count} Failures Detected")
            ->line("The queue watchdog has detected {$this->count} failures in the last collection window.")
            ->line("Last Failed Job: " . ($lastFailure['job'] ?? 'Unknown'))
            ->line("Queue: " . ($lastFailure['queue'] ?? 'Unknown'))
            ->line("Exception: " . Str::limit($lastFailure['exception'] ?? 'Unknown', 200))
            ->line("See your dashboard for full details.");
    }

    public function toSlack($notifiable): SlackMessage
    {
        $lastFailure = end($this->failures);

        return (new SlackMessage)
            ->error()
            ->content("Queue Watchdog Alert: {$this->count} failures detected in the collection window.")
            ->attachment(function ($attachment) use ($lastFailure) {
                $attachment->title('Last Failure Details')
                    ->fields([
                        'Job' => $lastFailure['job'] ?? 'Unknown',
                        'Queue' => $lastFailure['queue'] ?? 'Unknown',
                        'Exception' => Str::limit($lastFailure['exception'] ?? 'Unknown', 200),
                        'Time' => $lastFailure['failed_at'] ?? now()->toDateTimeString(),
                    ]);
            });
    }
}

use Illuminate\Support\Str;