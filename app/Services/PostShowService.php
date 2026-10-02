<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\CommentLike;
use App\Models\Post;
use App\Models\ReadingList;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/** Batches post-show viewer data (comments, likes, reading lists) in minimal queries. */
class PostShowService
{
    /**
     * Load everything the post show page needs for the current viewer.
     *
     * When Disqus is enabled, skips built-in comment and bookmark queries entirely.
     *
     * @return array{
     *     comments: Collection,
     *     commentCount: int,
     *     likedCommentIds: list<int>,
     *     readingLists: Collection,
     *     savedListIds: Collection,
     *     bookmarked: bool,
     * }
     */
    public function viewerData(Post $post, ?User $user, string $commentSort, bool $useDisqus): array
    {
        if ($useDisqus) {
            // External comments: skip all built-in comment/bookmark queries
            return [
                'comments' => collect(),
                'commentCount' => 0,
                'likedCommentIds' => [],
                'readingLists' => collect(),
                'savedListIds' => collect(),
                'bookmarked' => false,
            ];
        }

        // tree, flat id list, and total count — one query for all comment UI needs
        $commentMeta = Comment::treeForPostWithMeta($post, $commentSort);

        if ($user === null) {
            // Guest: comments only, no personalized lists or likes
            return [
                'comments' => $commentMeta['tree'],
                'commentCount' => $commentMeta['count'],
                'likedCommentIds' => [],
                'readingLists' => collect(),
                'savedListIds' => collect(),
                'bookmarked' => false,
            ];
        }

        // Ensure the default "Saved" list exists before rendering save UI
        ReadingList::defaultForUser($user);

        // Single query for likes across all comment IDs from the tree query above
        $likedCommentIds = $commentMeta['ids'] === []
            ? []
            : CommentLike::query()
                ->where('user_id', $user->id)
                ->whereIn('comment_id', $commentMeta['ids'])
                ->pluck('comment_id')
                ->all();

        // All lists owned by the viewer (default list first)
        $readingLists = $user->readingLists()
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        // List IDs that already contain this post (may span multiple lists)
        $savedListIds = DB::table('reading_list_post')
            ->join('reading_lists', 'reading_lists.id', '=', 'reading_list_post.reading_list_id')
            ->where('reading_lists.user_id', $user->id)
            ->where('reading_list_post.post_id', $post->id)
            ->pluck('reading_list_post.reading_list_id');

        return [
            'comments' => $commentMeta['tree'],
            'commentCount' => $commentMeta['count'],
            'likedCommentIds' => $likedCommentIds,
            'readingLists' => $readingLists,
            'savedListIds' => $savedListIds,
            'bookmarked' => $savedListIds->isNotEmpty(),
        ];
    }
}
