<?php

namespace App\Services\Search;

use App\Contracts\Search\SearchDriver;
use App\Models\AuthorAlias;
use App\Models\Post;
use App\Support\DatabaseFullTextSearch;
use App\Support\SearchTextPreprocessor;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

/** MySQL FULLTEXT search — default database driver. */
class DatabaseSearchDriver implements SearchDriver
{
    /** @var list<string> */
    private const POST_FULLTEXT_COLUMNS = ['title', 'subtitle', 'body'];

    public function __construct(
        private RecommendationService $recommendations,
        private SearchTextPreprocessor $preprocessor,
    ) {}

    public function isAvailable(): bool
    {
        return true;
    }

    public function searchPosts(string $query, int $limit = 20): Collection
    {
        $query = $this->preprocessor->forFullTextQuery($query);

        if ($query === '') {
            return collect();
        }

        $enabled = config('search.post.fulltext.fields', ['title', 'subtitle', 'body']);

        $enabledPostColumns = array_values(array_filter([
            in_array('title', $enabled, true) ? 'title' : null,
            in_array('subtitle', $enabled, true) ? 'subtitle' : null,
            in_array('body', $enabled, true) ? 'body' : null,
        ]));

        return Post::query()
            ->with(['user', 'authorAlias', 'category', 'tags'])
            ->published()
            ->visibleInFeeds()
            ->where(function ($builder) use ($query, $enabled, $enabledPostColumns) {
                $matched = false;

                if ($enabledPostColumns !== []) {
                    if (
                        $enabledPostColumns === self::POST_FULLTEXT_COLUMNS
                        || Schema::getConnection()->getDriverName() === 'pgsql'
                    ) {
                        DatabaseFullTextSearch::matchAny($builder, $enabledPostColumns, $query);
                    } else {
                        $builder->where(function ($inner) use ($enabledPostColumns, $query): void {
                            foreach ($enabledPostColumns as $index => $column) {
                                $inner->{$index === 0 ? 'where' : 'orWhere'}($column, 'like', "%{$query}%");
                            }
                        });
                    }
                    $matched = true;
                }

                if (in_array('author_name', $enabled, true)) {
                    $method = $matched ? 'orWhereHas' : 'whereHas';
                    $builder->{$method}('authorAlias', fn ($alias) => DatabaseFullTextSearch::matchAny(
                        $alias,
                        ['name', 'username', 'bio'],
                        $query,
                    ));
                    $matched = true;
                }

                if (in_array('category_name', $enabled, true)) {
                    $method = $matched ? 'orWhereHas' : 'whereHas';
                    $builder->{$method}('category', fn ($category) => DatabaseFullTextSearch::matchAny(
                        $category,
                        ['name'],
                        $query,
                    ));
                    $matched = true;
                }

                if (in_array('tags', $enabled, true)) {
                    $method = $matched ? 'orWhereHas' : 'whereHas';
                    $builder->{$method}('tags', fn ($tag) => DatabaseFullTextSearch::matchAny(
                        $tag,
                        ['name', 'slug'],
                        $query,
                    ));
                }
            })
            ->latest('published_at')
            ->limit($limit)
            ->get();
    }

    public function searchAuthors(string $query, int $limit = 10): Collection
    {
        $query = $this->preprocessor->forFullTextQuery($query);

        if ($query === '') {
            return collect();
        }

        return AuthorAlias::query()
            ->active()
            ->listedInDirectory()
            ->matchingSearch($query)
            ->limit($limit)
            ->get();
    }

    public function relatedPosts(Post $post, int $limit = 6): Collection
    {
        return $this->recommendations->relatedByTaxonomy($post, $limit);
    }

    public function indexPost(Post $post): void {}

    public function removePost(Post $post): void {}

    public function indexAuthor(AuthorAlias $alias): void {}

    public function ensureIndexes(): void {}

    public function reindexAll(): void {}
}
