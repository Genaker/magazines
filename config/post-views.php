<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Post view count mode
    |--------------------------------------------------------------------------
    |
    | sync  — count in-process after the HTTP response (default, no queue)
    | async — dispatch RecordPostView to Laravel's queue (config queue.default);
    |         use QUEUE_CONNECTION=redis or database and run a queue worker
    |
    */

    'count_mode' => env('POST_VIEW_COUNT_MODE', 'sync'),

];
