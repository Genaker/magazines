<?php

namespace App\Support;

use App\Models\Post;

/** Public post URLs — magazine subdomain when the post is an approved magazine submission. */
final class PostUrl
{
    public static function for(Post $post): string
    {
        if (MagazineSubdomain::servesPost($post)) {
            return MagazineSubdomain::postUrl($post);
        }

        if (AuthorSubdomain::enabled()) {
            return AuthorSubdomain::postUrl($post);
        }

        return route('posts.show', [$post->authorAlias ?? $post->user->primaryAlias(), $post->slug]);
    }

    public static function canonical(Post $post): string
    {
        if (MagazineSubdomain::servesPost($post)) {
            return MagazineSubdomain::postUrl($post);
        }

        return AuthorSubdomain::canonicalPostUrl($post);
    }
}
