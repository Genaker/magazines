<?php

namespace App\Services;

use App\Models\Post;
use App\Support\MediaSettings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/** Manages filesystem-backed photo items for gallery posts. */
class GalleryService
{
    public function __construct(private ImageService $images) {}

    /**
     * Append uploaded images to a gallery post.
     *
     * @param  list<UploadedFile>  $files
     * @param  list<string|null>  $captions
     */
    public function addItems(Post $post, array $files, array $captions = []): void
    {
        if ($files === []) {
            return;
        }

        $nextOrder = (int) $post->galleryItems()->max('sort_order') + 1;

        foreach ($files as $index => $file) {
            $processed = $this->images->processUpload($file, 'galleries/'.$post->id, MediaSettings::PRESET_GALLERY);
            $caption = trim((string) ($captions[$index] ?? ''));

            $post->galleryItems()->create([
                'path' => $processed['path'],
                'variants' => $processed['variants'],
                'sort_order' => $nextOrder++,
                'caption' => $caption !== '' ? $caption : null,
            ]);
        }

        $this->syncCoverFromFirstItem($post);
    }

    /** @param  array<int|string, string|null>  $captionsById */
    public function updateCaptions(Post $post, array $captionsById): void
    {
        foreach ($captionsById as $id => $caption) {
            $item = $post->galleryItems()->whereKey((int) $id)->first();

            if (! $item) {
                continue;
            }

            $text = trim((string) $caption);
            $item->update(['caption' => $text !== '' ? $text : null]);
        }
    }

    /** Remove selected items and delete their files from storage. */
    public function removeItems(Post $post, array $itemIds): void
    {
        if ($itemIds === []) {
            return;
        }

        $items = $post->galleryItems()->whereIn('id', $itemIds)->get();

        foreach ($items as $item) {
            $this->images->deleteVariants($item->variants);
            $item->delete();
        }

        $this->reindexSortOrder($post);
        $this->syncCoverFromFirstItem($post);
    }

    /** Delete every gallery item and its stored variants. */
    public function deleteAllItems(Post $post): void
    {
        foreach ($post->galleryItems as $item) {
            $this->images->deleteVariants($item->variants);
        }

        $post->galleryItems()->delete();
    }

    /** Use the first gallery photo as cover when the post has no custom cover. */
    public function syncCoverFromFirstItem(Post $post): void
    {
        $first = $post->galleryItems()->orderBy('sort_order')->first();

        if (! $first) {
            return;
        }

        if ($post->cover_image) {
            return;
        }

        $post->update([
            'cover_image' => $first->path,
            'cover_variants' => $first->variants,
        ]);
    }

    /** Ensure a published gallery has the minimum number of photos. */
    public function assertPublishable(Post $post, int $minItems): void
    {
        $count = $post->galleryItems()->count();

        if ($count < $minItems) {
            throw new InvalidArgumentException(
                "Gallery posts need at least {$minItems} photos ({$count} uploaded).",
            );
        }
    }

    /** Renumber sort_order sequentially after removals. */
    private function reindexSortOrder(Post $post): void
    {
        DB::transaction(function () use ($post): void {
            foreach ($post->galleryItems()->orderBy('sort_order')->get() as $index => $item) {
                $item->update(['sort_order' => $index + 1]);
            }
        });
    }
}
