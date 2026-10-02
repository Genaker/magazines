<?php

return [

    /** Failed magic-link code/token attempts before the account is locked. */
    'max_failed_attempts' => (int) env('MAGIC_LOGIN_MAX_ATTEMPTS', 5),

    /** Login link and code lifetime in minutes. */
    'expires_minutes' => (int) env('MAGIC_LOGIN_EXPIRES_MINUTES', 15),

];
