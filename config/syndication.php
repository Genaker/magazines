<?php

return [

    'feed_limit' => (int) env('SYNDICATION_FEED_LIMIT', 50),

    'sitemap_posts_limit' => (int) env('SYNDICATION_SITEMAP_POSTS_LIMIT', 2000),

    'twitter_site' => env('SYNDICATION_TWITTER_SITE'),

];
