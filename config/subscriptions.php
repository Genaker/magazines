<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Story subscription emails
    |--------------------------------------------------------------------------
    */

    'email_enabled' => env('SUBSCRIPTION_EMAIL_ENABLED', true),

    'default_delivery' => env('SUBSCRIPTION_DEFAULT_DELIVERY', 'instant'),

    'digest_time' => env('SUBSCRIPTION_DIGEST_TIME', '08:00'),

];
