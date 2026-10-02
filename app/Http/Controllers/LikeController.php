<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\PostLike;
use App\Services\ActivityNotifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LikeController extends Controller
{
    public function toggle(Request $request, Post $post): JsonResponse
    {
        $user = $request->user();
        $ip = $request->ip();

        if ($user) {
            $like = PostLike::query()->where('post_id', $post->id)->where('user_id', $user->id)->first();

            if ($like) {
                $like->delete();
                $post->decrement('likes_count');

                return response()->json(['liked' => false, 'likes_count' => $post->fresh()->likes_count]);
            }

            PostLike::query()->create([
                'post_id' => $post->id,
                'user_id' => $user->id,
            ]);
            $post->increment('likes_count');
            $post->load('user');
            ActivityNotifier::notify($post->user, $user, 'like', $post);

            return response()->json(['liked' => true, 'likes_count' => $post->fresh()->likes_count]);
        }

        $like = PostLike::query()
            ->where('post_id', $post->id)
            ->where('ip_address', $ip)
            ->first();

        if ($like) {
            $like->delete();
            $post->decrement('likes_count');

            return response()->json(['liked' => false, 'likes_count' => $post->fresh()->likes_count]);
        }

        DB::transaction(function () use ($post, $ip) {
            PostLike::query()->create([
                'post_id' => $post->id,
                'ip_address' => $ip,
            ]);
            $post->increment('likes_count');
        });

        return response()->json(['liked' => true, 'likes_count' => $post->fresh()->likes_count]);
    }
}
