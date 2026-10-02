<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\ReadingList;
use App\Services\ActivityNotifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReadingListController extends Controller
{
    public function index(Request $request): View
    {
        $lists = $request->user()
            ->readingLists()
            ->withCount('posts')
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        return view('reading-lists.index', compact('lists'));
    }

    public function show(Request $request, ReadingList $readingList): View
    {
        $this->authorize('view', $readingList);

        $posts = $readingList->posts()
            ->with(['user', 'category'])
            ->paginate(15);

        return view('reading-lists.show', compact('readingList', 'posts'));
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'description' => ['nullable', 'string', 'max:280'],
            'is_private' => ['sometimes', 'boolean'],
            'post_id' => ['nullable', 'integer', 'exists:posts,id'],
        ]);

        $list = $request->user()->readingLists()->create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'is_private' => $data['is_private'] ?? false,
        ]);

        $post = isset($data['post_id']) ? Post::query()->find($data['post_id']) : null;
        $wasSaved = $post && $request->user()->hasPostInAnyList($post);

        if ($post) {
            $list->posts()->syncWithoutDetaching([$post->id]);
            $this->notifyBookmarkIfNeeded($request, $post, $wasSaved);
        }

        if ($request->expectsJson()) {
            $defaultList = ReadingList::defaultForUser($request->user());

            return response()->json([
                'list' => [
                    'id' => $list->id,
                    'name' => $list->name,
                    'is_private' => $list->is_private,
                    'has_post' => (bool) $post,
                ],
                ...($post ? $this->saveState($request, $post, $defaultList) : []),
            ]);
        }

        return redirect()->route('reading-lists.show', $list);
    }

    public function update(Request $request, ReadingList $readingList): RedirectResponse
    {
        $this->authorize('update', $readingList);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'description' => ['nullable', 'string', 'max:280'],
            'is_private' => ['sometimes', 'boolean'],
        ]);

        $readingList->update($data);

        return redirect()->route('reading-lists.show', $readingList);
    }

    public function destroy(Request $request, ReadingList $readingList): RedirectResponse
    {
        $this->authorize('delete', $readingList);

        $readingList->delete();

        return redirect()->route('reading-lists.index');
    }

    public function toggleDefault(Request $request, Post $post): JsonResponse
    {
        $list = ReadingList::defaultForUser($request->user());
        $wasSaved = $request->user()->hasPostInAnyList($post);

        if ($list->posts()->where('posts.id', $post->id)->exists()) {
            $list->posts()->detach($post->id);

            return response()->json($this->saveState($request, $post, $list));
        }

        $list->posts()->attach($post->id);
        $this->notifyBookmarkIfNeeded($request, $post, $wasSaved);

        return response()->json($this->saveState($request, $post, $list));
    }

    public function togglePost(Request $request, ReadingList $readingList, Post $post): JsonResponse
    {
        $this->authorize('update', $readingList);

        $wasSaved = $request->user()->hasPostInAnyList($post);

        if ($readingList->posts()->where('posts.id', $post->id)->exists()) {
            $readingList->posts()->detach($post->id);

            return response()->json([
                'saved' => false,
                ...$this->saveState($request, $post, ReadingList::defaultForUser($request->user())),
            ]);
        }

        $readingList->posts()->attach($post->id);
        $this->notifyBookmarkIfNeeded($request, $post, $wasSaved);

        return response()->json([
            'saved' => true,
            ...$this->saveState($request, $post, ReadingList::defaultForUser($request->user())),
        ]);
    }

    /**
     * @return array{bookmarked: bool, in_default_list: bool}
     */
    private function saveState(Request $request, Post $post, ReadingList $defaultList): array
    {
        return [
            'bookmarked' => $request->user()->hasPostInAnyList($post),
            'in_default_list' => $defaultList->posts()->where('posts.id', $post->id)->exists(),
        ];
    }

    private function notifyBookmarkIfNeeded(Request $request, Post $post, bool $wasSaved): void
    {
        if ($wasSaved || $post->user_id === $request->user()->id) {
            return;
        }

        $post->load('user');
        ActivityNotifier::notify($post->user, $request->user(), 'bookmark', $post);
    }
}
