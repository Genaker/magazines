<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Schema version (V-prefixed migrations)
    |--------------------------------------------------------------------------
    |
    | All migrations live in database/migrations/ as sequential files:
    |
    |   V00001_create_users_table.php
    |   V00002_create_cache_table.php
    |   ...
    |
    | Create:  php artisan schema:make add_example_column
    | Status:  php artisan schema:version
    | Reinstall: php artisan schema:reinstall --seed
    |
    */

    'prefix' => 'V',

    'pad' => 5,

];
