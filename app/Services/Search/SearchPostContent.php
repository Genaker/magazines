<?php

namespace App\Services\Search;

use App\Models\Post;
use App\Support\SearchTextPreprocessor;
use Illuminate\Support\Collection;

/** Extracts configured post fields for full-text indexing and embeddings. */
class SearchPostContent
{
    private const ALLOWED_FIELDS = [
        'title',
        'subtitle',
        'body',
        'tags',
        'author_name',
        'category_name',
    ];

    public function __construct(private SearchTextPreprocessor $preprocessor) {}

    /**
     * RediSearch hash field values for a post.
     *
     * @return array{title: string, subtitle: string, body_text: string, author_name: string, category_name: string, tag_slugs: string, tag_names: string}
     */
    public function fullTextFields(Post $post): array
    {
        $post->loadMissing(['authorAlias', 'category', 'tags']);
        $enabled = $this->enabledFullTextFields();
        $bodyMaxWords = (int) config('search.post.fulltext.body_max_words', 100);

        $raw = $this->rawFieldValues($post);

        return [
            'title' => in_array('title', $enabled, true)
                ? $this->preprocessor->forFullTextIndex((string) $raw['title'])
                : '',
            'subtitle' => in_array('subtitle', $enabled, true)
                ? $this->preprocessor->forFullTextIndex((string) $raw['subtitle'])
                : '',
            'body_text' => in_array('body', $enabled, true)
                ? $this->preprocessor->forFullTextIndex((string) $raw['body'], $bodyMaxWords)
                : '',
            'author_name' => in_array('author_name', $enabled, true)
                ? $this->preprocessor->forFullTextIndex((string) $raw['author_name'])
                : '',
            'category_name' => in_array('category_name', $enabled, true)
                ? $this->preprocessor->forFullTextIndex((string) $raw['category_name'])
                : '',
            'tag_slugs' => in_array('tags', $enabled, true)
                ? $post->tags->pluck('slug')->implode(',')
                : '',
            'tag_names' => in_array('tags', $enabled, true)
                ? $post->tags->pluck('name')->implode(',')
                : '',
        ];
    }

    public function embeddingText(Post $post): string
    {
        $post->loadMissing(['authorAlias', 'category', 'tags']);
        $enabled = $this->enabledEmbeddingFields();
        $bodyMaxWords = (int) config('search.post.embedding.body_max_words', 100);
        $raw = $this->rawFieldValues($post);

        $parts = [];

        foreach ($enabled as $field) {
            $value = match ($field) {
                'body' => $this->preprocessor->forEmbedding((string) $raw['body'], $bodyMaxWords),
                'tags' => $this->preprocessor->forEmbedding($post->tags->pluck('name')->implode(' ')),
                default => $this->preprocessor->forEmbedding((string) ($raw[$field] ?? '')),
            };

            if ($value !== '') {
                $parts[] = $value;
            }
        }

        return implode("\n\n", $parts);
    }

    /** @return list<string> */
    public function enabledFullTextFields(): array
    {
        return $this->filterAllowed(config('search.post.fulltext.fields', self::ALLOWED_FIELDS));
    }

    /** @return list<string> */
    public function enabledEmbeddingFields(): array
    {
        return $this->filterAllowed(config('search.post.embedding.fields', ['title', 'subtitle', 'body', 'tags']));
    }

    /** @return list<string> */
    public function fullTextQueryFieldClauses(string $escapedQuery): array
    {
        if ($escapedQuery === '') {
            return [];
        }

        $clauses = [];
        $enabled = $this->enabledFullTextFields();

        foreach ([
            'title' => 'title',
            'subtitle' => 'subtitle',
            'body' => 'body_text',
            'author_name' => 'author_name',
            'category_name' => 'category_name',
            'tags' => 'tag_slugs',
        ] as $configKey => $redisField) {
            if (in_array($configKey, $enabled, true)) {
                $clauses[] = "@{$redisField}:({$escapedQuery})";
            }
        }

        if (in_array('tags', $enabled, true)) {
            $clauses[] = "@tag_names:({$escapedQuery})";
        }

        return $clauses;
    }

    /** @return array<string, string> */
    private function rawFieldValues(Post $post): array
    {
        return [
            'title' => (string) $post->title,
            'subtitle' => (string) ($post->subtitle ?? ''),
            'body' => (string) $post->body,
            'author_name' => (string) ($post->authorAlias?->name ?? ''),
            'category_name' => (string) ($post->category?->name ?? ''),
        ];
    }

    /** @param  list<string>  $fields */
    private function filterAllowed(array $fields): array
    {
        return Collection::make($fields)
            ->map(fn (string $field) => trim($field))
            ->filter(fn (string $field) => in_array($field, self::ALLOWED_FIELDS, true))
            ->values()
            ->all();
    }
}
