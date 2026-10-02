<?php

namespace App\Support;

use App\Models\AuthorAlias;
use App\Models\Category;
use App\Models\Magazine;
use App\Models\Post;
use App\Models\SiteSetting;
use App\Models\Tag;
use App\Support\Features;
use App\Services\ImageService;
use Illuminate\Support\Collection;

/** RSS feeds, sitemap entries, and syndication metadata. */
class Syndication
{
    /** Site display name from settings or app config. */
    public static function siteName(): string
    {
        return SiteSetting::getValue('site_name', config('app.name'));
    }

    /** Site tagline / description for RSS and meta. */
    public static function siteDescription(): string
    {
        return SiteSetting::getValue(
            'site_tagline',
            'Local communities, publishers, and bloggers — with integrated AI writing assistants.',
        );
    }

    /** @return Collection<int, Post> */
    public static function feedPosts(int $limit): Collection
    {
        return Post::query()
            ->with(['authorAlias', 'category', 'user'])
            ->published()
            ->visibleInFeeds()
            ->orderByDesc('published_at')
            ->limit($limit)
            ->get();
    }

    /** @return Collection<int, Post> */
    public static function feedPostsForCategory(Category $category, int $limit): Collection
    {
        $categoryIds = Category::descendantIdsFor($category->id);

        return Post::query()
            ->with(['authorAlias', 'category', 'user'])
            ->published()
            ->visibleInFeeds()
            ->whereIn('category_id', $categoryIds)
            ->orderByDesc('published_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Build sitemap URL entries for static pages, taxonomy, authors, and posts.
     *
     * @return array<int, array{loc: string, lastmod?: string, changefreq?: string, priority?: string}>
     */
    public static function sitemapEntries(): array
    {
        $entries = [
            [
                'loc' => route('home'),
                'changefreq' => 'hourly',
                'priority' => '1.0',
            ],
            [
                'loc' => route('authors.index'),
                'changefreq' => 'daily',
                'priority' => '0.7',
            ],
            [
                'loc' => route('search'),
                'changefreq' => 'weekly',
                'priority' => '0.5',
            ],
        ];

        if (Features::enabled('magazines')) {
            $entries[] = [
                'loc' => route('magazines.index'),
                'changefreq' => 'daily',
                'priority' => '0.8',
            ];
        }

        foreach (Category::query()->orderBy('sort_order')->orderBy('name')->get(['slug', 'updated_at']) as $category) {
            $entries[] = [
                'loc' => route('categories.show', $category),
                'lastmod' => $category->updated_at?->toAtomString(),
                'changefreq' => 'daily',
                'priority' => '0.7',
            ];
        }

        foreach (Tag::query()->orderBy('name')->get(['slug', 'updated_at']) as $tag) {
            $entries[] = [
                'loc' => route('tags.show', $tag),
                'lastmod' => $tag->updated_at?->toAtomString(),
                'changefreq' => 'weekly',
                'priority' => '0.5',
            ];
        }

        if (Features::enabled('magazines')) {
            foreach (Magazine::query()->orderBy('name')->get(['slug', 'updated_at']) as $magazine) {
                $entries[] = [
                    'loc' => route('magazines.show', $magazine),
                    'lastmod' => $magazine->updated_at?->toAtomString(),
                    'changefreq' => 'daily',
                    'priority' => '0.7',
                ];
            }
        }

        foreach (AuthorAlias::query()->active()->listedInDirectory()->orderBy('name')->get(['username', 'updated_at']) as $alias) {
            $entries[] = [
                'loc' => route('authors.show', $alias),
                'lastmod' => $alias->updated_at?->toAtomString(),
                'changefreq' => 'weekly',
                'priority' => '0.6',
            ];
        }

        $limit = config('syndication.sitemap_posts_limit', 2000);

        Post::query()
            ->with('authorAlias')
            ->published()
            ->orderByDesc('published_at')
            ->limit($limit)
            ->get(['slug', 'author_alias_id', 'updated_at', 'published_at'])
            ->each(function (Post $post) use (&$entries): void {
                if (! $post->authorAlias) {
                    return;
                }

                $entries[] = [
                    'loc' => route('posts.show', [$post->authorAlias, $post->slug]),
                    'lastmod' => ($post->updated_at ?? $post->published_at)?->toAtomString(),
                    'changefreq' => 'monthly',
                    'priority' => '0.8',
                ];
            });

        return $entries;
    }

    /** Resolve the best share/cover image URL for RSS item enclosures. */
    public static function postImageUrl(Post $post): ?string
    {
        $imageService = app(ImageService::class);

        return Seo::postShareImageUrl($post, $imageService);
    }
}
