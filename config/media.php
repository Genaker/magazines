<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Media Disk
    |--------------------------------------------------------------------------
    |
    | Use "public" for local storage. Switch to "s3" when ready — no code
    | changes needed beyond this env var and AWS credentials.
    |
    */

    // Always use the public disk for web-visible uploads (covers, editor images).
    // Do not fall back to FILESYSTEM_DISK — that is often "local" (private storage).
    'disk' => env('MEDIA_DISK', 'public'),

    'path_prefix' => env('MEDIA_PATH_PREFIX', 'media'),

    'jpeg_quality' => (int) env('MEDIA_JPEG_QUALITY', 85),

    'variants' => [
        'sm' => (int) env('MEDIA_WIDTH_SM', 480),
        'md' => (int) env('MEDIA_WIDTH_MD', 800),
        'lg' => (int) env('MEDIA_WIDTH_LG', 1500),
    ],

    'post' => [
        'resize' => filter_var(env('MEDIA_POST_RESIZE', 'true'), FILTER_VALIDATE_BOOLEAN),
        'max_width' => (int) env('MEDIA_POST_MAX_WIDTH', 1500),
        'max_height' => (int) env('MEDIA_POST_MAX_HEIGHT', 1500),
        'jpeg_quality' => (int) env('MEDIA_POST_JPEG_QUALITY', env('MEDIA_JPEG_QUALITY', 85)),
        'variants' => [
            'sm' => (int) env('MEDIA_POST_WIDTH_SM', env('MEDIA_WIDTH_SM', 480)),
            'md' => (int) env('MEDIA_POST_WIDTH_MD', env('MEDIA_WIDTH_MD', 800)),
            'lg' => (int) env('MEDIA_POST_WIDTH_LG', env('MEDIA_WIDTH_LG', 1500)),
        ],
    ],

    'gallery' => [
        'resize' => filter_var(env('MEDIA_GALLERY_RESIZE', 'true'), FILTER_VALIDATE_BOOLEAN),
        'max_width' => (int) env('MEDIA_GALLERY_MAX_WIDTH', 1500),
        'max_height' => (int) env('MEDIA_GALLERY_MAX_HEIGHT', 1500),
        'jpeg_quality' => (int) env('MEDIA_GALLERY_JPEG_QUALITY', env('MEDIA_JPEG_QUALITY', 85)),
        'variants' => [
            'sm' => (int) env('MEDIA_GALLERY_WIDTH_SM', env('MEDIA_WIDTH_SM', 480)),
            'md' => (int) env('MEDIA_GALLERY_WIDTH_MD', env('MEDIA_WIDTH_MD', 800)),
            'lg' => (int) env('MEDIA_GALLERY_WIDTH_LG', env('MEDIA_WIDTH_LG', 1500)),
        ],
    ],

    'max_upload_kb' => (int) env('MEDIA_MAX_UPLOAD_KB', 5120),

    'avatar' => [
        'size' => (int) env('MEDIA_AVATAR_SIZE', 100),
        'jpeg_quality' => (int) env('MEDIA_AVATAR_JPEG_QUALITY', 85),
    ],

    'autosave_snapshots_keep' => (int) env('AUTOSAVE_SNAPSHOTS_KEEP', 10),

    'max_tags_per_post' => (int) env('MAX_TAGS_PER_POST', 10),

    'min_gallery_photos' => (int) env('MIN_GALLERY_PHOTOS', 2),

    'max_gallery_photos' => (int) env('MAX_GALLERY_PHOTOS', 100),

];
