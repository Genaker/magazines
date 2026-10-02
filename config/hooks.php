<?php

/**
 * Blade hook slots. Register views from AppServiceProvider or a listener:
 *
 *   config()->push('hooks.post.after_content', 'partials.my-banner');
 *
 * Each slot accepts an array of view names rendered in registration order.
 * Pass context from the template: <x-hook name="post.after_content" :data="['post' => $post]" />
 */
return [
    'head.after_meta' => [],
    'body.start' => [],
    'nav.after' => [],
    'main.before' => [],
    'main.after' => [],
    'footer.before' => [],
    'body.end' => [],

    'post.before_content' => [],
    'post.after_content' => [],
    'post.after_comments' => [],

    'profile.sidebar.after' => [],

    'admin.content.before' => [],
];
