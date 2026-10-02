<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Site branding defaults
    |--------------------------------------------------------------------------
    |
    | Used as fallbacks when site_settings rows are missing. Override via .env
    | before first install, Docker boot, or the /install wizard.
    |
    */
    'defaults' => [
        'name' => env('SITE_NAME', env('APP_NAME', 'Magazines')),
        'tagline' => env('SITE_TAGLINE', 'Local communities, publishers, and bloggers — with integrated AI writing assistants.'),
        'footer_tagline' => env('SITE_FOOTER_TAGLINE', 'Platform for a free society'),
        'footer_rights' => env('SITE_FOOTER_RIGHTS', 'All rights reserved.'),
        'copyright_start_year' => env('SITE_COPYRIGHT_START', '2014'),
        'theme_color' => env('SITE_THEME_COLOR', '#111827'),
        'background_color' => env('SITE_BACKGROUND_COLOR', '#f3f4f6'),
    ],

    /*
    | Maps logical keys → site_settings storage keys and config defaults path.
    |
    | @var array<string, array{setting: string, default: string}>
    */
    'keys' => [
        'name' => ['setting' => 'site_name', 'default' => 'defaults.name'],
        'tagline' => ['setting' => 'site_tagline', 'default' => 'defaults.tagline'],
        'footer_tagline' => ['setting' => 'site_footer_tagline', 'default' => 'defaults.footer_tagline'],
        'footer_rights' => ['setting' => 'site_footer_rights', 'default' => 'defaults.footer_rights'],
        'copyright_start_year' => ['setting' => 'site_copyright_start_year', 'default' => 'defaults.copyright_start_year'],
        'theme_color' => ['setting' => 'site_theme_color', 'default' => 'defaults.theme_color'],
        'background_color' => ['setting' => 'site_background_color', 'default' => 'defaults.background_color'],
    ],

    'home_layout' => env('SITE_HOME_LAYOUT', 'discover'),

    /** URL segment for the admin interface (default: admin). Set ADMIN_PATH in .env or admin_path via site:setting. */
    'admin_path' => env('ADMIN_PATH', 'admin'),

    'default_locale' => env('SITE_DEFAULT_LOCALE', env('APP_LOCALE', 'en')),

    /** Comma-separated locale codes, e.g. en,ua */
    'enabled_locales' => array_values(array_filter(array_map(
        fn (string $code): string => trim($code),
        explode(',', env('SITE_ENABLED_LOCALES', 'en,ua')),
    ))),

];
