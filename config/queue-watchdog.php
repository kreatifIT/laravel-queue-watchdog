<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Watchdog Enabled
    |--------------------------------------------------------------------------
    |
    | Set this to false to disable the queue failure monitoring.
    |
    */
    'enabled' => env('QUEUE_WATCHDOG_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Notification Thresholds
    |--------------------------------------------------------------------------
    |
    | Define the rules for triggering an alert.
    |
    */
    'thresholds' => [
        'default' => [
            'window_minutes' => 10,
            'failure_limit' => 5,
            'cooldown_minutes' => 30, // How long to wait before sending the same notification again
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Monitored Queues
    |--------------------------------------------------------------------------
    |
    | Specify which queues should be monitored. 
    | '*' for all, '!queue_name' to exclude, 'wildcard*' for patterns.
    |
    */
    'queues' => ['*'],

    /*
    |--------------------------------------------------------------------------
    | Aggregation Strategy
    |--------------------------------------------------------------------------
    |
    | 'all' - Counts every failed job.
    | 'unique_jobs' - Counts failures per job class.
    | 'unique_exceptions' - Counts failures per exception type.
    |
    */
    'aggregation' => 'all',

    /*
    |--------------------------------------------------------------------------
    | Notification Channels
    |--------------------------------------------------------------------------
    |
    | Specify the channels and the recipients for the alerts.
    |
    */
    'notifications' => [
        'mail' => [
            'to' => env('QUEUE_WATCHDOG_MAIL_TO', 'admin@example.com'),
        ],
        // 'slack' => [
        //     'webhook_url' => env('QUEUE_WATCHDOG_SLACK_WEBHOOK'),
        // ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | The watchdog uses the cache to store failure counts and timestamps.
    |
    */
    'cache_prefix' => 'queue_watchdog_',
    'cache_driver' => env('QUEUE_WATCHDOG_CACHE_DRIVER', null),
];
