# Laravel Queue Watchdog

A Laravel package to monitor queue failures and send notifications based on thresholds and time windows.

## Features
- **Threshold Monitoring**: Alert when X jobs fail within Y minutes.
- **Aggregation Strategies**: 
    - `all`: Count all failures.
    - `unique_jobs`: Count failures per job class.
    - `unique_exceptions`: Count failures per exception type.
- **Cooldown**: Prevent notification spam with a configurable cooldown period.
- **Multi-channel Notifications**: Support for Mail, Slack, and any other Laravel notification channel.

## Installation

```bash
composer require kreatif/laravel-queue-watchdog
```

## Configuration

Publish the config file:

```bash
php artisan vendor:publish --tag="laravel-queue-watchdog-config"
```

The configuration allows you to define thresholds, aggregation strategies, and notification channels.

### Queue Filtering

You can precisely control which queues are monitored using the `queues` array in the config file. It supports wildcards and exclusions:

- `*`: Monitor all queues.
- `default`: Monitor only the "default" queue.
- `!ignored`: Exclude the "ignored" queue.
- `sync*`: Monitor any queue starting with "sync" (e.g., `sync-users`, `sync-orders`).

Example:
```php
'queues' => ['*', '!update', 'report', 'sync*'],
```

## Usage

The package automatically listens for the `Illuminate\Queue\Events\JobFailed` event and tracks failures in your cache. No additional setup is required beyond configuration.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
