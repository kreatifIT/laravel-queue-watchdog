<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Watchdog Enabled
    |--------------------------------------------------------------------------
    */
    'enabled' => env('QUEUE_WATCHDOG_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Notification Thresholds (Digest Mode)
    |--------------------------------------------------------------------------
    |
    | The watchdog uses a "Time Bucket" model:
    | 1. First failure starts a collection window of 'window_minutes'.
    | 2. All failures during this window are collected.
    | 3. At the end of the window, if failures >= limit, a summary is sent.
    | 4. Then a 'cooldown_minutes' period starts where no new windows can begin.
    |
    | Note: If using the 'sync' driver, the "End of Window" job runs IMMEDIATELY
    | after the first failure, effectively reporting instantly.
    |
    */
    'thresholds' => [
        'default' => [
            'window_minutes' => 5,    // Duration to collect failures before reporting
            'failure_limit' => 5,     // Minimum failures to trigger a report
            'cooldown_minutes' => 30, // Time to wait AFTER a report before monitoring again
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Monitored Queues
    |--------------------------------------------------------------------------
    | '*' for all, '!queue_name' to exclude, 'wildcard*' for patterns.
    */
    'queues' => ['*'],

    /*
    |--------------------------------------------------------------------------
    | Notification Channels
    |--------------------------------------------------------------------------
    */
    'notifications' => [
        /*
        | Note: Ensure your mail driver is correctly configured and reachable.
        | If using Docker, ensure the mail container (e.g., mailpit) is in the same network.
        */
        'mail' => [
            'to' => env('QUEUE_WATCHDOG_MAIL_TO', 'admin@example.com'),
        ],
        /*
        | Note: Using the 'sync' queue driver will trigger these notifications immediately
        | during the request/command execution as the "queue" name will be reported as 'sync'.
        */
        // 'slack' => [
        //     'webhook_url' => env('QUEUE_WATCHDOG_SLACK_WEBHOOK'),
        // ],
    ],

    'cache_prefix' => 'queue_watchdog_',
];