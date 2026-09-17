<?php

use App\Channels\FinMailChannel;
use App\Notifications\CustomCheckFailedNotification;
use App\Notifications\HealthNotifiable;
use Spatie\Health\Models\HealthCheckResultHistoryItem;
use Spatie\Health\Notifications\CheckFailedNotification;
use Spatie\Health\ResultStores\EloquentHealthResultStore;

return [
    /*
     * A result store is responsible for saving the results of the checks. The
     * `EloquentHealthResultStore` will save results in the database. You
     * can use multiple stores at the same time.
     */
    'result_stores' => [
        EloquentHealthResultStore::class => [
            'connection' => env('HEALTH_DB_CONNECTION', env('DB_CONNECTION')),
            // 'model' => HealthCheckResultHistoryItem::class,
            'model' => App\Models\HealthCheckResultHistoryItem::class,
            'keep_history_for_days' => 5,
        ],

        /*
        Spatie\Health\ResultStores\CacheHealthResultStore::class => [
            'store' => 'file',
        ],

        Spatie\Health\ResultStores\JsonFileHealthResultStore::class => [
            'disk' => 's3',
            'path' => 'health.json',
        ],

        Spatie\Health\ResultStores\InMemoryHealthResultStore::class,
        */
    ],

    /*
     * You can get notified when specific events occur. Out of the box you can use 'mail' and 'slack'.
     * For Slack you need to install laravel/slack-notification-channel.
     */
    'notifications' => [
        /*
         * Notifications will only get sent if this option is set to `true`.
         */
        'enabled' => env('HEALTH_NOTIFICATIONS_ENABLED', true),

        'notifications' => [
            // CheckFailedNotification::class => ['mail'],
            CustomCheckFailedNotification::class => ['mail', FinMailChannel::class],
        ],

        /*
         * Here you can specify the notifiable to which the notifications should be sent.
         * VExim uses the system administrator accounts by default, with
         * HEALTH_TO_ADDRESS available as an optional override.
         */
        'notifiable' => HealthNotifiable::class,

        /*
         * When checks start failing, you could potentially end up getting
         * a notification every minute.
         *
         * With this setting, notifications are throttled. By default, you'll
         * only get one notification per hour.
         */
        'throttle_notifications_for_minutes' => 60,
        'throttle_notifications_key' => 'health:latestNotificationSentAt:',

        /*
         * When set to true, notifications will only be sent when at least one
         * check has a 'failed' status. Warnings will be ignored.
         */
        'only_on_failure' => false,

        'mail' => [
            // Optional override. Leave empty to notify users with the system_admin role.
            'to' => env('HEALTH_TO_ADDRESS', ''),

            'from' => [
                'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
                'name' => env('MAIL_FROM_NAME', 'Example'),
            ],
        ],

        'slack' => [
            'webhook_url' => env('HEALTH_SLACK_WEBHOOK_URL', ''),

            /*
             * If this is set to null the default channel of the webhook will be used.
             */
            'channel' => null,

            'username' => null,

            'icon' => null,
        ],
    ],

    /*
     * You can let Oh Dear monitor the results of all health checks. This way, you'll
     * get notified of any problems even if your application goes totally down. Via
     * Oh Dear, you can also have access to more advanced notification options.
     */
    'oh_dear_endpoint' => [
        'enabled' => false,

        /*
         * When this option is enabled, the checks will run before sending a response.
         * Otherwise, we'll send the results from the last time the checks have run.
         */
        'always_send_fresh_results' => true,

        /*
         * The secret that is displayed at the Application Health settings at Oh Dear.
         */
        'secret' => env('OH_DEAR_HEALTH_CHECK_SECRET'),

        /*
         * The URL that should be configured in the Application health settings at Oh Dear.
         */
        'url' => '/oh-dear-health-check-results',
    ],

    /*
     * You can specify a heartbeat URL for the Horizon check.
     * This URL will be pinged if the Horizon check is successful.
     * This way you can get notified if Horizon goes down.
     */
    'horizon' => [
        'heartbeat_url' => env('HORIZON_HEARTBEAT_URL'),
    ],

    /*
     * You can specify a heartbeat URL for the Schedule check.
     * This URL will be pinged if the Schedule check is successful.
     * This way you can get notified if the schedule fails to run.
     */
    'schedule' => [
        'heartbeat_url' => env('SCHEDULE_HEARTBEAT_URL'),
    ],

    /*
     * VExim defaults for the built-in health checks. Queue and Redis checks
     * automatically enable when those services are actually configured, but
     * can be explicitly enabled or disabled using the environment overrides.
     */
    'vexim' => [
        'expected_environment' => env('HEALTH_EXPECTED_ENVIRONMENT', 'production'),

        'disk' => [
            'warning' => (int) env('HEALTH_DISK_WARNING_PERCENT', 80),
            'failure' => (int) env('HEALTH_DISK_FAILURE_PERCENT', 90),
        ],

        'database_connections' => [
            'warning' => (int) env('HEALTH_DB_CONNECTION_WARNING', 40),
            'failure' => (int) env('HEALTH_DB_CONNECTION_FAILURE', 50),
        ],

        'schedule' => [
            'max_age_minutes' => (int) env('HEALTH_SCHEDULE_MAX_AGE_MINUTES', 2),
        ],

        'queue' => [
            'enabled' => env('HEALTH_QUEUE_ENABLED'),
            'max_age_minutes' => (int) env('HEALTH_QUEUE_MAX_AGE_MINUTES', 5),
        ],

        'redis' => [
            'enabled' => env('HEALTH_REDIS_ENABLED'),
            'memory_warning_mb' => (float) env('HEALTH_REDIS_MEMORY_WARNING_MB', 256),
            'memory_failure_mb' => (float) env('HEALTH_REDIS_MEMORY_FAILURE_MB', 500),
        ],
    ],

    /*
     * You can set a theme for the local results page
     *
     * - light: light mode
     * - dark: dark mode
     */
    'theme' => 'light',

    /*
     * When enabled, completed `HealthQueueJob`s will be displayed
     * in Horizon's silenced jobs screen.
     */
    'silence_health_queue_job' => true,

    /*
     * The response code to use for HealthCheckJsonResultsController when a health
     * check has failed
     */
    'json_results_failure_status' => 200,

    /*
     * You can specify a secret token that needs to be sent in the X-Secret-Token for secured access.
     */
    'secret_token' => env('HEALTH_SECRET_TOKEN'),

    /**
     * By default, conditionally skipped health checks are treated as failures.
     * You can override this behavior by uncommenting the configuration below.
     *
     * @link https://spatie.be/docs/laravel-health/v1/basic-usage/conditionally-running-or-modifying-checks
     */
    'treat_skipped_as_failure' => false,
];
