<?php

return [

    'driver' => env('SEARCH_DRIVER', 'database'),

    'limits' => [
        'posts' => (int) env('SEARCH_LIMIT_POSTS', 20),
        'authors' => (int) env('SEARCH_LIMIT_AUTHORS', 10),
        'magazines' => (int) env('SEARCH_LIMIT_MAGAZINES', 10),
        'related' => (int) env('SEARCH_LIMIT_RELATED', 6),
    ],

    'redis' => [
        'connection' => env('REDIS_SEARCH_CONNECTION', 'search'),
        'prefix' => env('REDIS_SEARCH_KEY_PREFIX', 'search:'),
    ],

    'indexes' => [
        'posts' => env('REDIS_SEARCH_POSTS_INDEX', 'posts_idx'),
        'authors' => env('REDIS_SEARCH_AUTHORS_INDEX', 'authors_idx'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Post fields indexed for full-text vs embeddings
    |--------------------------------------------------------------------------
    |
    | Allowed field keys: title, subtitle, body, tags, author_name, category_name
    |
    | body_max_words: truncate body plain text (0 = no limit). Default 100.
    |
    */

    'post' => [
        'fulltext' => [
            'fields' => array_values(array_filter(array_map(
                'trim',
                explode(',', env('SEARCH_FULLTEXT_FIELDS', 'title,subtitle,body,tags,author_name,category_name')),
            ))),
            'body_max_words' => (int) env('SEARCH_FULLTEXT_BODY_MAX_WORDS', env('SEARCH_POST_BODY_MAX_WORDS', 100)),
            'weights' => [
                'title' => (float) env('SEARCH_WEIGHT_TITLE', 5.0),
                'subtitle' => (float) env('SEARCH_WEIGHT_SUBTITLE', 3.0),
                'body' => (float) env('SEARCH_WEIGHT_BODY', 1.0),
                'author_name' => (float) env('SEARCH_WEIGHT_AUTHOR', 2.0),
                'category_name' => (float) env('SEARCH_WEIGHT_CATEGORY', 1.5),
            ],
        ],

        'embedding' => [
            'fields' => array_values(array_filter(array_map(
                'trim',
                explode(',', env('SEARCH_EMBEDDING_FIELDS', 'title,subtitle,body,tags')),
            ))),
            'body_max_words' => (int) env('SEARCH_EMBEDDING_BODY_MAX_WORDS', env('SEARCH_POST_BODY_MAX_WORDS', 100)),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Text preprocessing
    |--------------------------------------------------------------------------
    */

    'preprocessing' => [
        'strip_html' => filter_var(env('SEARCH_STRIP_HTML', 'true'), FILTER_VALIDATE_BOOLEAN),
        'collapse_whitespace' => true,
        'lowercase_for_embedding' => filter_var(env('SEARCH_LOWERCASE_EMBEDDING', 'true'), FILTER_VALIDATE_BOOLEAN),
        'min_token_length' => (int) env('SEARCH_MIN_TOKEN_LENGTH', 2),
        'remove_stop_words' => [
            'embedding' => filter_var(env('SEARCH_REMOVE_STOP_WORDS_EMBEDDING', 'true'), FILTER_VALIDATE_BOOLEAN),
            'fulltext_index' => filter_var(env('SEARCH_REMOVE_STOP_WORDS_FULLTEXT', 'false'), FILTER_VALIDATE_BOOLEAN),
            'fulltext_query' => filter_var(env('SEARCH_REMOVE_STOP_WORDS_QUERY', 'true'), FILTER_VALIDATE_BOOLEAN),
        ],
        'stop_words' => require __DIR__.'/search-stop-words-en.php',
    ],

    'embeddings' => [
        'provider' => env('SEARCH_EMBEDDING_PROVIDER', 'none'),
        'dimensions' => (int) env('SEARCH_EMBEDDING_DIMENSIONS', 768),
        'query_cache_ttl' => (int) env('SEARCH_EMBEDDING_QUERY_CACHE_TTL', 900),

        'gemini' => [
            'api_key' => env('GEMINI_API_KEY'),
            'model' => env('GEMINI_EMBEDDING_MODEL', 'text-embedding-004'),
            'base_url' => env('GEMINI_API_BASE', 'https://generativelanguage.googleapis.com/v1beta'),
        ],

        'openai' => [
            'api_key' => env('OPENAI_API_KEY'),
            'model' => env('OPENAI_EMBEDDING_MODEL', 'text-embedding-3-small'),
            'base_url' => env('OPENAI_API_BASE', 'https://api.openai.com/v1'),
            'dimensions' => env('OPENAI_EMBEDDING_DIMENSIONS'),
        ],
    ],

    'semantic' => [
        'enabled' => env('SEARCH_SEMANTIC_ENABLED'),
        'hybrid_text_weight' => (float) env('SEARCH_HYBRID_TEXT_WEIGHT', 0.4),
        'hybrid_vector_weight' => (float) env('SEARCH_HYBRID_VECTOR_WEIGHT', 0.6),
    ],

];
