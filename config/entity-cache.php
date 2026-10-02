<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Entity Cache
    |--------------------------------------------------------------------------
    |
    | Redis-backed query cache for Eloquent reads (feeds, posts, categories,
    | settings, etc.). Set ENTITY_CACHE_ENABLED=false to bypass cache and
    | hit the database on every read — useful for debugging or local dev.
    |
    */

    'enabled' => env('ENTITY_CACHE_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Entity Cache Store
    |--------------------------------------------------------------------------
    |
    | Dedicated cache store for application entities. Use "redis" in production
    | to reduce database load. Falls back to CACHE_STORE when not set.
    |
    */

    'store' => env('ENTITY_CACHE_STORE', env('CACHE_STORE', 'redis')),

    /*
    |--------------------------------------------------------------------------
    | Time To Live (seconds)
    |--------------------------------------------------------------------------
    |
    | null = forever (settings only). Paginated lists use per-page keys.
    |
    */

    'ttl' => [
        'settings' => null,
        'categories' => (int) env('ENTITY_CACHE_TTL_CATEGORIES', 3600),
        'feeds' => (int) env('ENTITY_CACHE_TTL_FEEDS', 300),
        'posts' => (int) env('ENTITY_CACHE_TTL_POSTS', 600),
        'authors' => (int) env('ENTITY_CACHE_TTL_AUTHORS', 600),
        'tags' => (int) env('ENTITY_CACHE_TTL_TAGS', 3600),
        'magazines' => (int) env('ENTITY_CACHE_TTL_MAGAZINES', 3600),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Tags
    |--------------------------------------------------------------------------
    |
    | Tags require redis/memcached/dynamodb. When unsupported (e.g. array in
    | tests), tag flush is a no-op; entries expire via TTL or explicit forget.
    |
    */

    'tags' => [
        'settings' => 'settings',
        'categories' => 'categories',
        'feeds' => 'feeds',
        'posts' => 'posts',
        'authors' => 'authors',
        'tags' => 'tags',
        'magazines' => 'magazines',
    ],

    /*
    |--------------------------------------------------------------------------
    | Volatile model attributes
    |--------------------------------------------------------------------------
    |
    | Stripped before writing to cache and re-loaded via a separate SQL query
    | on read so counters stay live while post content remains cached.
    |
    */

    'volatile_attributes' => [
        \App\Models\Post::class => ['views_count', 'likes_count'],
    ],

    'models' => [
        'Category' => ['tag' => 'categories', 'ttl_key' => 'categories'],
        'Magazine' => ['tag' => 'magazines', 'ttl_key' => 'magazines'],
        'Tag' => ['tag' => 'tags', 'ttl_key' => 'tags'],
        'Post' => ['tag' => 'posts', 'ttl_key' => 'posts'],
        'User' => ['tag' => 'authors', 'ttl_key' => 'authors'],
    ],

];
