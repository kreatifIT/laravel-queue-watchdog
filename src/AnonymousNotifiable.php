<?php

namespace Kreatif\QueueWatchdog;

use Illuminate\Notifications\Notifiable;

class AnonymousNotifiable
{
    use Notifiable;

    public function getKey()
    {
        return 'queue-watchdog-anonymous';
    }

    public function routeNotificationForMail()
    {
        return config('queue-watchdog.notifications.mail.to');
    }

    public function routeNotificationForSlack()
    {
        return config('queue-watchdog.notifications.slack.webhook_url');
    }
}
