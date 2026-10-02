<?php

namespace App\Services;

use App\Models\Post;
use App\Models\PostAutosaveSnapshot;
use App\Models\User;
use Illuminate\Http\UploadedFile;

/** Persists editor autosave snapshots and applies uploaded cover/share images. */
class AutosaveService
{
    public function __construct(private ImageService $images) {}

    /** Return the most recent autosave snapshot for a post, if any. */
    public function latestSnapshot(Post $post): ?PostAutosaveSnapshot
    {
        return PostAutosaveSnapshot::query()
            ->where('post_id', $post->id)
            ->latest('id')
            ->first();
    }

    /** Compare snapshot payload fields against the live post (title, body, category, tags). */
    public function differsFromPost(PostAutosaveSnapshot $snapshot, Post $post): bool
    {
        $payload = $snapshot->payload; // JSON editor fields stored on autosave

        // Text fields
        if ((string) ($payload['title'] ?? '') !== (string) $post->title) {
            return true;
        }

        if ((string) ($payload['subtitle'] ?? '') !== (string) ($post->subtitle ?? '')) {
            return true;
        }

        if ((string) ($payload['body'] ?? '') !== (string) ($post->body ?? '')) {
            return true;
        }

        if ((int) ($payload['category_id'] ?? 0) !== (int) $post->category_id) {
            return true;
        }

        // Tags from the autosave payload (comma-separated string)
        $savedTags = collect(explode(',', (string) ($payload['tags'] ?? '')))
            ->map(fn (string $tag) => trim($tag))
            ->filter()
            ->values()
            ->all();

        // Tags currently on the published post (avoid N+1 when relation is loaded)
        $publishedTags = $post->relationLoaded('tags')
            ? $post->tags->pluck('name')->all()
            : $post->tags()->orderBy('name')->pluck('name')->all();

        sort($savedTags);
        sort($publishedTags);

        return $savedTags !== $publishedTags;
    }

    /** True when a published post has autosaved edits not yet applied to the live version. */
    public function hasPendingPublishedDraft(Post $post): bool
    {
        if (! $post->isPublished()) {
            return false;
        }

        $snapshot = $this->latestSnapshot($post); // nil when nothing autosaved yet

        return $snapshot !== null && $this->differsFromPost($snapshot, $post);
    }

    /**
     * Editor field values: latest snapshot when present, otherwise the published post.
     *
     * @return array{title: string, subtitle: ?string, body: ?string, category_id: int, tags: string}
     */
    public function editorState(Post $post): array
    {
        $snapshot = $this->latestSnapshot($post); // nil when nothing autosaved yet

        if ($snapshot === null) {
            return [
                'title' => (string) $post->title,
                'subtitle' => $post->subtitle,
                'body' => (string) $post->body,
                'category_id' => (int) $post->category_id,
                'tags' => $post->tags->pluck('name')->join(', '),
            ];
        }

        $payload = $snapshot->payload; // JSON editor fields stored on autosave

        return [
            'title' => (string) ($payload['title'] ?? $post->title),
            'subtitle' => $payload['subtitle'] ?? $post->subtitle,
            'body' => (string) ($payload['body'] ?? $post->body),
            'category_id' => (int) ($payload['category_id'] ?? $post->category_id),
            'tags' => (string) ($payload['tags'] ?? $post->tags->pluck('name')->join(', ')),
        ];
    }

    /**
     * Store autosave payload separately from published post content.
     *
     * @param  array<string, mixed>  $payload
     */
    public function persistRevision(Post $post, User $user, array $payload): Post
    {
        $revision = $post->autosave_revision + 1; // monotonic counter for pending publish detection

        // Snapshot stores editor payload; revision counter tracks pending publish
        $this->storeSnapshot($post, $user, [
            ...$payload,
            'revision' => $revision,
        ]);

        $post->updateQuietly(['autosave_revision' => $revision]);

        return $post->fresh();
    }

    /** Remove all autosave snapshots for a post (e.g. after publish). */
    public function clearSnapshots(Post $post): void
    {
        PostAutosaveSnapshot::query()->where('post_id', $post->id)->delete();
    }

    /**
     * Store a draft payload and prune older snapshots beyond the configured limit.
     *
     * @param  array<string, mixed>  $payload
     */
    public function storeSnapshot(Post $post, User $user, array $payload): void
    {
        PostAutosaveSnapshot::query()->create([
            'post_id' => $post->id,
            'user_id' => $user->id,
            'payload' => $payload,
        ]);

        // Keep only the N most recent snapshots per post
        $keep = config('media.autosave_snapshots_keep', 10); // max snapshots retained per post
        $idsToKeep = PostAutosaveSnapshot::query() // newest N snapshot primary keys
            ->where('post_id', $post->id)
            ->orderByDesc('id')
            ->limit($keep)
            ->pluck('id');

        PostAutosaveSnapshot::query()
            ->where('post_id', $post->id)
            ->when($idsToKeep->isNotEmpty(), fn ($query) => $query->whereNotIn('id', $idsToKeep))
            ->delete();
    }

    /** Replace the post cover image and regenerate responsive variants. */
    public function applyCover(Post $post, UploadedFile $file): void
    {
        $this->images->deleteVariants($post->cover_variants);

        $processed = $this->images->processUpload($file, 'covers'); // path + responsive variant map

        $post->update([
            'cover_image' => $processed['path'],
            'cover_variants' => $processed['variants'],
        ]);
    }

    /** Replace the Open Graph / share image and regenerate responsive variants. */
    public function applyShareImage(Post $post, UploadedFile $file): void
    {
        $this->images->deleteVariants($post->share_image_variants);

        $processed = $this->images->processUpload($file, 'share-images'); // path + responsive variant map

        $post->update([
            'share_image' => $processed['path'],
            'share_image_variants' => $processed['variants'],
        ]);
    }
}
