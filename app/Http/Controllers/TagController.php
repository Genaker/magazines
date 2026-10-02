<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TagController extends Controller
{
    public function suggest(Request $request): JsonResponse
    {
        $query = trim((string) $request->query('q', ''));

        if ($query === '') {
            return response()->json([]);
        }

        $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $query).'%';

        $tags = Tag::query()
            ->where('name', 'like', $like)
            ->withCount(['posts as posts_count' => fn ($q) => $q->published()])
            ->orderByDesc('posts_count')
            ->orderBy('name')
            ->limit(10)
            ->pluck('name');

        return response()->json($tags);
    }

    public function show(Tag $tag): View
    {
        $page = max(1, (int) request()->query('page', 1));
        $posts = \App\Models\Post::cachedPublishedForTag($tag->id, $page);

        return view('tags.show', [
            'tag' => $tag,
            'posts' => $posts,
            'isFollowing' => auth()->check() && auth()->user()->followedTags()->where('tag_id', $tag->id)->exists(),
        ]);
    }
}
