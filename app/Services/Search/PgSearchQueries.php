<?php

namespace App\Services\Search;

use App\Enums\PostStatus;
use App\Support\PgSearchSupport;
use Illuminate\Support\Facades\DB;

/** BM25 queries via pg_search (ParadeDB). */
class PgSearchQueries
{
    public function __construct(
        private SearchTextPreprocessor $preprocessor,
    ) {}

    /**
     * @return list<int>
     */
    public function searchPostIds(string $query, int $limit): array
    {
        if (! PgSearchSupport::isReady() || $limit < 1) {
            return [];
        }

        $query = $this->preprocessor->forFullTextQuery(trim($query));

        if ($query === '') {
            return [];
        }

        $enabled = config('search.post.fulltext.fields', ['title', 'subtitle', 'body']);
        $weights = config('search.post.fulltext.weights', []);
        $matchClauses = [];
        $bindings = [];

        foreach (['title', 'subtitle', 'body'] as $field) {
            if (! in_array($field, $enabled, true)) {
                continue;
            }

            $matchClauses[] = PgSearchSupport::matchExpression($field, (float) ($weights[$field] ?? 1.0));
            $bindings[] = $query;
        }

        if (in_array('author_name', $enabled, true)) {
            $authorWeight = (float) ($weights['author_name'] ?? 2.0);
            $nameMatch = PgSearchSupport::matchExpression('name', $authorWeight);
            $usernameMatch = PgSearchSupport::matchExpression('username', $authorWeight);
            $bioMatch = PgSearchSupport::matchExpression('bio', $authorWeight);

            $matchClauses[] = "posts.author_alias_id IN (
                SELECT id FROM author_aliases
                WHERE deleted_at IS NULL
                  AND ({$nameMatch} OR {$usernameMatch} OR {$bioMatch})
            )";
            $bindings[] = $query;
            $bindings[] = $query;
            $bindings[] = $query;
        }

        if (in_array('category_name', $enabled, true)) {
            $categoryWeight = (float) ($weights['category_name'] ?? 1.5);
            $categoryMatch = PgSearchSupport::matchExpression('name', $categoryWeight);

            $matchClauses[] = "posts.category_id IN (
                SELECT id FROM categories WHERE {$categoryMatch}
            )";
            $bindings[] = $query;
        }

        if (in_array('tags', $enabled, true)) {
            $tagWeight = (float) ($weights['tags'] ?? 1.0);
            $tagNameMatch = PgSearchSupport::matchExpression('name', $tagWeight);
            $tagSlugMatch = PgSearchSupport::matchExpression('slug', $tagWeight);

            $matchClauses[] = "posts.id IN (
                SELECT post_tag.post_id
                FROM post_tag
                INNER JOIN tags ON tags.id = post_tag.tag_id
                WHERE {$tagNameMatch} OR {$tagSlugMatch}
            )";
            $bindings[] = $query;
            $bindings[] = $query;
        }

        if ($matchClauses === []) {
            return [];
        }

        $published = PostStatus::Published->value;
        $now = now()->toDateTimeString();

        $sql = '
            SELECT posts.id
            FROM posts
            WHERE ('.implode(' OR ', $matchClauses).")
              AND posts.status = ?
              AND posts.published_at IS NOT NULL
              AND posts.feed_hidden_at IS NULL
              AND posts.deleted_at IS NULL
              AND posts.published_at <= ?
            ORDER BY pdb.score(posts.id) DESC
            LIMIT ?
        ";

        $bindings[] = $published;
        $bindings[] = $now;
        $bindings[] = $limit;

        return collect(DB::select($sql, $bindings))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * @return list<int>
     */
    public function searchAuthorIds(string $query, int $limit): array
    {
        if (! PgSearchSupport::isReady() || $limit < 1) {
            return [];
        }

        $query = $this->preprocessor->forFullTextQuery(trim($query));

        if ($query === '') {
            return [];
        }

        $nameMatch = PgSearchSupport::matchExpression('name');
        $usernameMatch = PgSearchSupport::matchExpression('username');
        $bioMatch = PgSearchSupport::matchExpression('bio');

        $sql = "
            SELECT id
            FROM author_aliases
            WHERE deleted_at IS NULL
              AND ({$nameMatch} OR {$usernameMatch} OR {$bioMatch})
            ORDER BY pdb.score(id) DESC
            LIMIT ?
        ";

        return collect(DB::select($sql, [$query, $query, $query, $limit]))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * @return list<int>
     */
    public function searchMagazineIds(string $query, int $limit): array
    {
        if (! PgSearchSupport::isReady() || $limit < 1 || ! PgSearchSupport::indexExists(PgSearchSupport::magazinesIndexName())) {
            return [];
        }

        $query = $this->preprocessor->forFullTextQuery(trim($query));

        if ($query === '') {
            return [];
        }

        $nameMatch = PgSearchSupport::matchExpression('name');
        $descriptionMatch = PgSearchSupport::matchExpression('description');
        $slugMatch = PgSearchSupport::matchExpression('slug');

        $sql = "
            SELECT id
            FROM magazines
            WHERE {$nameMatch} OR {$descriptionMatch} OR {$slugMatch}
            ORDER BY pdb.score(id) DESC
            LIMIT ?
        ";

        return collect(DB::select($sql, [$query, $query, $query, $limit]))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }
}
