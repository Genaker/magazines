<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Notification email delivery
    |--------------------------------------------------------------------------
    |
    | sync  — send mail immediately in the request (default)
    | queue — dispatch mail notifications to Laravel's queue (config queue.default);
    |         run `queue:work` or the scheduler task when using redis/database
    |
    | System mail (magic link, verification, password reset) is always sync.
    |
    */

    'email_delivery' => env('NOTIFICATION_EMAIL_DELIVERY', 'sync'),

    'queue' => env('NOTIFICATION_EMAIL_QUEUE', 'mail'),

    /*
    |--------------------------------------------------------------------------
    | Always-sync notification classes
    |--------------------------------------------------------------------------
    */

    'sync_notifications' => [
        \App\Notifications\MagicLoginLink::class,
    ],

];
