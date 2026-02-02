<?php

namespace Kreatif\QueueWatchdog\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Queue\Events\JobFailed;

class QueueAlert extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public JobFailed $event,
        public int $failureCount
    ) {}

    public function via($notifiable): array
    {
        return array_keys(config('queue-watchdog.notifications', []));
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->error()
            ->subject('Queue Watchdog Alert: High Failure Rate Detected')
            ->line("The queue watchdog has detected {$this->failureCount} failures within the configured time window.")
            ->line("Queue: " . $this->event->job->getQueue())
            ->line("Last failed job: " . $this->event->job->resolveName())
            ->line("Exception: " . $this->event->exception->getMessage())
            ->action('View Failed Jobs', url('/admin/failed-jobs')) // Adjust if needed
            ->line('Please check your queue workers and job logic.');
    }

    public function toSlack($notifiable): SlackMessage
    {
        return (new SlackMessage)
            ->error()
            ->content('Queue Watchdog Alert!')
            ->attachment(function ($attachment) {
                $attachment->title('High Failure Rate Detected')
                    ->fields([
                        'Failures' => $this->failureCount,
                        'Queue' => $this->event->job->getQueue(),
                        'Job' => $this->event->job->resolveName(),
                        'Exception' => $this->event->exception->getMessage(),
                    ]);
            });
    }
}
