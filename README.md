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

## Usage

The package automatically listens for the `Illuminate\Queue\Events\JobFailed` event and tracks failures in your cache. No additional setup is required beyond configuration.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
