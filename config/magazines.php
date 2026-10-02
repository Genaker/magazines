<?php

return [

    'nav' => [
        'limit' => (int) env('MAGAZINES_NAV_LIMIT', 10),
        'mode' => env('MAGAZINES_NAV_MODE', 'auto'),
        'activity_days' => (int) env('MAGAZINES_NAV_ACTIVITY_DAYS', 7),
        'auto_fill' => filter_var(env('MAGAZINES_NAV_AUTO_FILL', 'true'), FILTER_VALIDATE_BOOLEAN),
    ],

];
