<?php

namespace App\Enums;

enum PostType: string
{
    case Article = 'article';
    case Gallery = 'gallery';
    case Video = 'video';

    public function label(): string
    {
        return match ($this) {
            self::Article => __('app.post_type_article'),
            self::Gallery => __('app.post_type_gallery'),
            self::Video => __('app.post_type_video'),
        };
    }
}
