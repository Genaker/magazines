<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use App\Support\EntityCache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/** Builds cached home and discover feeds from published posts. */
class FeedService
{
    private const int SECTION_LIMIT = 5;

    private const int TAG_CLOUD_LIMIT = 20;

    /**
     * Cached tag cloud ordered by published post count.
     *
     * @return Collection<int, Tag>
     */
    public function popularTagCloud(int $limit = self::TAG_CLOUD_LIMIT): Collection
    {
        return EntityCache::remember(
            EntityCache::key('feeds', 'tags', 'cloud', "l{$limit}", 'v1'),
            (int) config('entity-cache.ttl.feeds'),
            fn () => Tag::query()
                ->whereHas('posts', fn (Builder $query) => $query->published()->visibleInFeeds())
                ->withCount(['posts as published_posts_count' => fn (Builder $query) => $query->published()->visibleInFeeds()])
                ->orderByDesc('published_posts_count')
                ->orderBy('name')
                ->limit($limit)
                ->get(),
            config('entity-cache.tags.feeds'),
        );
    }

    /** @return Collection<int, Post> */
    public function latestPosts(int $limit = 20): Collection
    {
        return $this->loadPostsByIds($this->latestPostIds($limit));
    }

    /**
     * Trending sections for the public trending layout.
     *
     * @return array{popularWeek: Collection, popularHour: Collection, popularDay: Collection}
     */
    public function trendingSections(): array
    {
        // Cache post IDs only; models loaded separately to preserve sort order
        $sectionIds = EntityCache::remember(
            EntityCache::key('feeds', 'trending', 'sections', 'v1'),
            (int) config('entity-cache.ttl.feeds'),
            fn () => [
                'popularWeek' => $this->trendingPostIds(
                    now()->subWeek()->startOfDay(),
                    self::SECTION_LIMIT,
                    'viewed_on',
                    true,
                ),
                'popularHour' => $this->trendingPostIds(now()->subHour(), self::SECTION_LIMIT, 'created_at', true),
                'popularDay' => $this->trendingPostIds(now()->subDay(), self::SECTION_LIMIT, 'created_at', true),
            ],
            config('entity-cache.tags.feeds'),
        );

        // Hydrate full Post models in cached ID order
        return $this->hydrateSectionMap($sectionIds);
    }

    /**
     * Multi-section discover feed (week, hour, day, latest).
     *
     * @return array{popularWeek: Collection, popularHour: Collection, popularDay: Collection, latest: Collection}
     */
    public function discoverSections(): array
    {
        // Cache post IDs only; models loaded separately to preserve sort order
        $sectionIds = EntityCache::remember(
            EntityCache::key('feeds', 'discover', 'sections', 'v8'),
            (int) config('entity-cache.ttl.feeds'),
            fn () => [
                'popularWeek' => $this->trendingPostIds(
                    now()->subWeek()->startOfDay(),
                    self::SECTION_LIMIT,
                    'viewed_on',
                    true,
                ),
                'popularHour' => $this->trendingPostIds(now()->subHour(), self::SECTION_LIMIT, 'created_at', true),
                'popularDay' => $this->trendingPostIds(now()->subDay(), self::SECTION_LIMIT, 'created_at', true),
                'latest' => $this->latestPostIds(self::SECTION_LIMIT),
            ],
            config('entity-cache.tags.feeds'),
        );

        return $this->hydrateSectionMap($sectionIds);
    }

    /**
     * Personalized home sections from followed authors, categories, and tags.
     *
     * @return array{latest: Collection, popular: Collection}
     */
    public function personalizedFeedSections(User $user): array
    {
        // Per-user cache: latest + popular from followed authors/categories/tags
        $sectionIds = EntityCache::remember(
            EntityCache::key('feeds', 'home', 'user:'.$user->id, 'sections', 'v2'),
            (int) config('entity-cache.ttl.feeds'),
            fn () => [
                'latest' => $this->followedPostsQuery($user)
                    ->orderByDesc('published_at')
                    ->limit(self::SECTION_LIMIT)
                    ->pluck('id')
                    ->all(),
                'popular' => $this->followedPostsQuery($user)
                    ->where('published_at', '>=', now()->subWeek())
                    ->orderByDesc('views_count')
                    ->orderByDesc('likes_count')
                    ->orderByDesc('published_at')
                    ->limit(self::SECTION_LIMIT)
                    ->pluck('id')
                    ->all(),
            ],
            config('entity-cache.tags.feeds'),
        );

        return $this->hydrateSectionMap($sectionIds);
    }

    /**
     * Trending sections for a category hub (week, hour, day), scoped to the subtree.
     *
     * @return array{trendingWeek: Collection<int, Post>, trendingHour: Collection<int, Post>, trendingDay: Collection<int, Post>}
     */
    public function trendingSectionsForCategory(int $categoryId): array
    {
        $categoryIds = Category::descendantIdsFor($categoryId);

        $sectionIds = EntityCache::remember(
            EntityCache::key('feeds', 'category', $categoryId, 'trending', 'v2'),
            (int) config('entity-cache.ttl.feeds'),
            fn () => [
                'trendingWeek' => $this->trendingPostIds(
                    now()->subWeek()->startOfDay(),
                    self::SECTION_LIMIT,
                    'viewed_on',
                    true,
                    $categoryIds,
                ),
                'trendingHour' => $this->trendingPostIds(
                    now()->subHour(),
                    self::SECTION_LIMIT,
                    'created_at',
                    true,
                    $categoryIds,
                ),
                'trendingDay' => $this->trendingPostIds(
                    now()->subDay(),
                    self::SECTION_LIMIT,
                    'created_at',
                    true,
                    $categoryIds,
                ),
            ],
            config('entity-cache.tags.feeds'),
        );

        return $this->hydrateSectionMap($sectionIds);
    }

    /**
     * Post IDs ranked by views in the given period, with optional all-time fallback.
     *
     * @param  list<int>|null  $categoryIds
     * @return list<int>
     */
    private function trendingPostIds(
        \DateTimeInterface $since,
        int $limit,
        string $viewTimestampColumn = 'viewed_on',
        bool $fallbackToAllTimePopular = false,
        ?array $categoryIds = null,
    ): array {
        // Posts with views in the window, ranked by period view count
        $ids = Post::query()
            ->published()
            ->visibleInFeeds()
            ->when($categoryIds !== null, fn (Builder $query) => $query->whereIn('category_id', $categoryIds))
            ->whereHas('views', fn (Builder $query) => $query->where($viewTimestampColumn, '>=', $since))
            ->withCount(['views as period_views_count' => fn (Builder $query) => $query->where($viewTimestampColumn, '>=', $since)])
            ->orderByDesc('period_views_count')
            ->orderByDesc('likes_count')
            ->orderByDesc('published_at')
            ->limit($limit)
            ->pluck('id')
            ->all();

        if (count($ids) >= $limit) {
            return $ids;
        }

        // When the period has no activity, fall back to all-time popular posts.
        $useAllTimePopular = $fallbackToAllTimePopular && $ids === [];

        // Fill remaining slots when the period returned fewer than $limit posts
        $fallback = Post::query()
            ->published()
            ->visibleInFeeds()
            ->when($categoryIds !== null, fn (Builder $query) => $query->whereIn('category_id', $categoryIds))
            ->when($ids !== [], fn (Builder $query) => $query->whereNotIn('id', $ids))
            ->when(! $useAllTimePopular, fn (Builder $query) => $query->where('published_at', '>=', $since))
            ->orderByDesc('views_count')
            ->orderByDesc('likes_count')
            ->orderByDesc('published_at')
            ->limit($limit - count($ids))
            ->pluck('id')
            ->all();

        return array_merge($ids, $fallback);
    }

    /** @return list<int> */
    private function latestPostIds(int $limit): array
    {
        return Post::query()
            ->published()
            ->visibleInFeeds()
            ->orderByDesc('published_at')
            ->limit($limit)
            ->pluck('id')
            ->all();
    }

    /**
     * Load posts by ID preserving the caller's sort order.
     *
     * @param  list<int>  $ids
     * @return Collection<int, Post>
     */
    private function loadPostsByIds(array $ids): Collection
    {
        if ($ids === []) {
            return collect();
        }

        // whereIn does not preserve order — re-sort by the cached ID list
        $posts = Post::query()
            ->with(['user', 'authorAlias', 'category', 'tags', 'galleryItems' => fn ($query) => $query->orderBy('sort_order')->limit(1)])
            ->withCount('galleryItems')
            ->published()
            ->visibleInFeeds()
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        return collect($ids)
            ->filter(fn (int $id) => $posts->has($id))
            ->map(fn (int $id) => $posts->get($id))
            ->values();
    }

    /**
     * Hydrate multiple feed sections with one post query.
     *
     * @param  array<string, list<int>>  $sectionIds
     * @return array<string, Collection<int, Post>>
     */
    private function hydrateSectionMap(array $sectionIds): array
    {
        $uniqueIds = [];

        foreach ($sectionIds as $ids) {
            foreach ($ids as $id) {
                $uniqueIds[$id] = true;
            }
        }

        $posts = $this->loadPostsByIds(array_keys($uniqueIds))->keyBy('id');
        $sections = [];

        foreach ($sectionIds as $name => $ids) {
            $sections[$name] = collect($ids)
                ->filter(fn (int $id) => $posts->has($id))
                ->map(fn (int $id) => $posts->get($id))
                ->values();
        }

        return $sections;
    }

    /** Posts from followed authors, categories, and tags; empty match when nothing is followed. */
    private function followedPostsQuery(User $user): Builder
    {
        // Union of followed authors, categories, and tags (OR semantics)
        $followingIds = $user->following()->pluck('users.id');
        $categoryIds = $user->followedCategories()->pluck('categories.id');
        $tagIds = $user->followedTags()->pluck('tags.id');

        $query = Post::query()->published()->visibleInFeeds();

        if ($followingIds->isEmpty() && $categoryIds->isEmpty() && $tagIds->isEmpty()) {
            return $query->whereRaw('0 = 1');
        }

        return $query->where(function (Builder $builder) use ($followingIds, $categoryIds, $tagIds) {
            if ($followingIds->isNotEmpty()) {
                $builder->orWhereIn('user_id', $followingIds);
            }

            if ($categoryIds->isNotEmpty()) {
                $builder->orWhereIn('category_id', $categoryIds);
            }

            if ($tagIds->isNotEmpty()) {
                $builder->orWhereHas('tags', fn (Builder $tagQuery) => $tagQuery->whereIn('tags.id', $tagIds));
            }
        });
    }
}
