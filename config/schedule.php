<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Scheduler
    |--------------------------------------------------------------------------
    |
    | Register recurring Artisan commands here. A single system cron entry runs
    | all of them via `php artisan schedule:run` (see doc/21-scheduler.md).
    |
    | frequency: named preset (every_minute, hourly, daily_at, …) or a cron
    | expression such as "* * * * *" or "0 8 * * *".
    |
    */

    'enabled' => env('SCHEDULER_ENABLED', true),

    'tasks' => [
        [
            'name' => 'subscription-digest',
            'command' => 'subscriptions:send-digest',
            'frequency' => 'daily_at',
            'time' => env('SUBSCRIPTION_DIGEST_TIME', '08:00'),
            'enabled' => env('SUBSCRIPTION_EMAIL_ENABLED', true),
        ],
        [
            'name' => 'stats-snapshot',
            'command' => 'stats:snapshot',
            'frequency' => 'daily_at',
            'time' => '01:00',
        ],
        [
            'name' => 'publications-notify-due',
            'command' => 'publications:notify-due',
            'frequency' => 'every_minute',
        ],
        [
            'name' => 'notification-mail-queue',
            'command' => 'queue:work',
            'parameters' => [
                '--stop-when-empty',
                '--queue' => env('NOTIFICATION_EMAIL_QUEUE', 'mail'),
                '--max-jobs' => 50,
            ],
            'frequency' => 'every_minute',
            'when' => [
                'config' => 'notifications.email_delivery',
                'equals' => 'queue',
            ],
        ],
    ],

];
