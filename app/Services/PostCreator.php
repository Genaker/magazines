<?php

namespace App\Services;

use App\Enums\MagazineSubmissionStatus;
use App\Enums\PostStatus;
use App\Enums\PostType;
use App\Models\AuthorAlias;
use App\Models\Category;
use App\Models\Magazine;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use App\Support\Features;
use App\Support\MagazineMembership;
use App\Support\MagazinePostSubmission;
use App\Support\Slugger;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/** Creates posts for the web UI and API (article type first; extend for gallery/video). */
class PostCreator
{
    /** @param  array{
     *     title: string,
     *     body: string,
     *     category_id?: int|null,
     *     status: string,
     *     subtitle?: string|null,
     *     author_alias_id?: int|null,
     *     magazine_id?: int|null,
     *     publish_at?: string|null,
     *     tags?: list<string>,
     *     custom_fields?: array<string, mixed>|null
     * }  $data
     */
    public function createArticle(User $user, array $data): Post
    {
        Gate::authorize('create', Post::class);

        $alias = $this->resolveAuthorAlias($user, $data['author_alias_id'] ?? null);
        $status = PostStatus::from($data['status']);
        $publishedAt = $this->resolvePublishedAt($status, $data['publish_at'] ?? null);
        $magazineId = isset($data['magazine_id']) ? (int) $data['magazine_id'] : null;

        $this->assertWritableMagazine($user, $magazineId);

        if (isset($data['category_id'])) {
            $this->assertCategoryAllowedForPost((int) $data['category_id'], $magazineId);
        }

        $magazineFields = $this->magazineFieldsForSave($user, $magazineId, $status);

        $post = DB::transaction(function () use ($user, $data, $alias, $status, $publishedAt, $magazineFields) {
            $post = Post::query()->create([
                'user_id' => $user->id,
                'author_alias_id' => $alias->id,
                'category_id' => $data['category_id'] ?? null,
                'magazine_id' => $magazineFields['magazine_id'],
                'magazine_submission_status' => $magazineFields['magazine_submission_status'],
                'title' => $data['title'],
                'subtitle' => $data['subtitle'] ?? null,
                'slug' => Post::uniqueSlugForAlias($data['title'], $alias->id),
                'type' => PostType::Article,
                'body' => $data['body'],
                'status' => $status,
                'published_at' => $publishedAt,
                'custom_fields' => $data['custom_fields'] ?? null,
            ]);

            $this->syncTags($post, $data['tags'] ?? []);

            return $post;
        });

        return $post->load(['authorAlias', 'category', 'tags']);
    }

    /** @param  list<string>  $tags */
    private function syncTags(Post $post, array $tags): void
    {
        $names = collect($tags)
            ->map(fn ($tag) => is_string($tag) ? trim($tag) : '')
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

    private function resolveAuthorAlias(User $user, ?int $aliasId): AuthorAlias
    {
        if ($aliasId) {
            return $user->authorAliases()->whereKey($aliasId)->firstOrFail();
        }

        return $user->primaryAlias();
    }

    private function resolvePublishedAt(PostStatus $status, ?string $publishAt): ?Carbon
    {
        if ($status === PostStatus::Draft) {
            return null;
        }

        if ($publishAt) {
            return Carbon::parse($publishAt);
        }

        return now();
    }

    /** @return array{magazine_id: ?int, magazine_submission_status: ?MagazineSubmissionStatus} */
    private function magazineFieldsForSave(User $user, ?int $magazineId, PostStatus $status): array
    {
        if ($magazineId === null) {
            return [
                'magazine_id' => null,
                'magazine_submission_status' => null,
            ];
        }

        $magazine = Magazine::query()->findOrFail($magazineId);
        MagazineMembership::assertCanSubmit($user, $magazine);

        return [
            'magazine_id' => $magazineId,
            'magazine_submission_status' => MagazinePostSubmission::statusOnSave($user, $magazine, $status),
        ];
    }

    private function assertWritableMagazine(User $user, ?int $magazineId): void
    {
        if ($magazineId === null || ! Features::enabled('magazines')) {
            return;
        }

        $magazine = Magazine::query()->find($magazineId);

        if ($magazine === null) {
            throw ValidationException::withMessages([
                'magazine_id' => __('message.magazine_not_writable'),
            ]);
        }

        MagazineMembership::assertCanSubmit($user, $magazine);
    }

    private function assertCategoryAllowedForPost(int $categoryId, ?int $magazineId): void
    {
        $category = Category::query()->find($categoryId);

        if (! $category || ! $category->isAllowedForPost($magazineId)) {
            throw ValidationException::withMessages([
                'category_id' => 'Choose a valid category for the selected magazine.',
            ]);
        }
    }
}
