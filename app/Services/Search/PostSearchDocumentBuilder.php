<?php

namespace App\Services\Search;

use App\Models\Post;
use App\Services\Embeddings\EmbeddingService;

/** Builds RediSearch hash fields for a post document. */
class PostSearchDocumentBuilder
{
    public function __construct(
        private EmbeddingService $embeddings,
        private SearchPostContent $content,
    ) {}

    /** @return array<string, string|int|float> */
    public function build(Post $post): array
    {
        $post->loadMissing(['authorAlias', 'category', 'tags']);
        $text = $this->content->fullTextFields($post);

        $fields = [
            'post_id' => $post->id,
            'title' => $text['title'],
            'subtitle' => $text['subtitle'],
            'body_text' => $text['body_text'],
            'author_username' => (string) ($post->authorAlias?->username ?? ''),
            'author_name' => $text['author_name'],
            'category_name' => $text['category_name'],
            'category_id' => (int) ($post->category_id ?? 0),
            'category_slug' => (string) ($post->category?->slug ?? ''),
            'tag_slugs' => $text['tag_slugs'],
            'tag_names' => $text['tag_names'],
            'magazine_id' => (int) ($post->magazine_id ?? 0),
            'published_at' => $post->published_at?->getTimestamp() ?? 0,
            'views_count' => (int) $post->views_count,
            'likes_count' => (int) $post->likes_count,
            'feed_hidden' => $post->feed_hidden_at ? 1 : 0,
        ];

        $embeddingText = $this->content->embeddingText($post);
        $vector = $this->embeddings->embedForIndex($embeddingText);

        if ($vector !== []) {
            $fields['embedding'] = EmbeddingVector::pack($vector);
        }

        return $fields;
    }

    public function embeddingText(Post $post): string
    {
        return $this->content->embeddingText($post);
    }

    public function key(Post $post): string
    {
        return config('search.redis.prefix', 'search:').'post:'.$post->id;
    }

    public function isSearchable(Post $post): bool
    {
        return $post->isPublished();
    }
}
