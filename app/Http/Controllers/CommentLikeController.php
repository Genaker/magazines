<?php

namespace App\Http\Controllers;

use App\Events\CommentLiked;
use App\Models\Comment;
use App\Models\CommentLike;
use App\Support\CommentSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommentLikeController extends Controller
{
    public function toggle(Request $request, Comment $comment): JsonResponse
    {
        abort_if(CommentSettings::useDisqus(), 404);

        $user = $request->user();
        abort_unless($user, 401);

        $this->authorize('view', $comment->post);

        $like = CommentLike::query()
            ->where('comment_id', $comment->id)
            ->where('user_id', $user->id)
            ->first();

        if ($like) {
            $like->delete();
            $comment->decrement('likes_count');

            return response()->json([
                'liked' => false,
                'likes_count' => $comment->fresh()->likes_count,
            ]);
        }

        CommentLike::query()->create([
            'comment_id' => $comment->id,
            'user_id' => $user->id,
        ]);
        $comment->increment('likes_count');
        $comment->load('user', 'post.user');
        CommentLiked::dispatch($comment, $user);

        return response()->json([
            'liked' => true,
            'likes_count' => $comment->fresh()->likes_count,
        ]);
    }
}
