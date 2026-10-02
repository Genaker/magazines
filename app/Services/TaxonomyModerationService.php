<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use App\Support\Slugger;
use App\Support\TaxonomyModerator;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/** Feed and taxonomy actions for delegated moderators. */
class TaxonomyModerationService
{
    public function hideFromFeed(Post $post, User $actor): void
    {
        $this->authorizePost($actor, $post);

        $post->update(['feed_hidden_at' => now()]);
    }

    public function showInFeed(Post $post, User $actor): void
    {
        $this->authorizePost($actor, $post);

        $post->update(['feed_hidden_at' => null]);
    }

    public function moveCategory(Post $post, Category $category, User $actor): void
    {
        if ($category->magazine_id !== null) {
            throw ValidationException::withMessages([
                'category_id' => 'Posts can only be moved to site categories.',
            ]);
        }

        if (! $actor->isAdmin()) {
            $scope = TaxonomyModerator::moderatedCategoryScopeIds($actor);

            if (! $post->category_id || ! in_array($post->category_id, $scope, true)) {
                abort(403);
            }
        }

        $post->update(['category_id' => $category->id]);
    }

    /** @param  list<string>  $tagNames */
    public function syncTags(Post $post, array $tagNames, User $actor): void
    {
        $this->authorizePost($actor, $post);

        $names = collect($tagNames)
            ->map(fn (string $tag) => trim($tag))
            ->filter()
            ->unique()
            ->take(config('media.max_tags_per_post', 10))
            ->values();

        $tagIds = $names->map(function (string $name) {
            return Tag::query()->firstOrCreate(
                ['slug' => Slugger::unique($name, new Tag, 'slug')],
                ['name' => $name],
            )->id;
        });

        $post->tags()->sync($tagIds);
    }

    /** @return Collection<int, Post> */
    public function postsForCategory(Category $category, int $limit = 50): Collection
    {
        $categoryIds = Category::descendantIdsFor($category->id);

        return Post::query()
            ->with(['user', 'authorAlias', 'category', 'tags'])
            ->published()
            ->whereIn('category_id', $categoryIds)
            ->orderByDesc('published_at')
            ->limit($limit)
            ->get();
    }

    /** @return Collection<int, Post> */
    public function postsForTag(Tag $tag, int $limit = 50): Collection
    {
        return Post::query()
            ->with(['user', 'authorAlias', 'category', 'tags'])
            ->published()
            ->whereHas('tags', fn ($query) => $query->where('tags.id', $tag->id))
            ->orderByDesc('published_at')
            ->limit($limit)
            ->get();
    }

    private function authorizePost(User $actor, Post $post): void
    {
        if (! TaxonomyModerator::canModeratePost($actor, $post)) {
            abort(403);
        }
    }
}
