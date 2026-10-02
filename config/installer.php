<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Enforce installer middleware in PHPUnit
    |--------------------------------------------------------------------------
    |
    | When false (default), feature tests skip the "app must be installed" gate
    | so RefreshDatabase suites keep working. InstallerTest sets this to true.
    |
    */
    'enforce_in_tests' => env('INSTALLER_ENFORCE_IN_TESTS', false),

];
