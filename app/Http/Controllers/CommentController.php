<?php

namespace App\Http\Controllers;

use App\Events\CommentCreated;
use App\Models\Comment;
use App\Models\Post;
use App\Support\CommentSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function store(Request $request, Post $post): RedirectResponse
    {
        abort_if(CommentSettings::useDisqus(), 404);

        $this->authorize('create', [Comment::class, $post]);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
            'parent_id' => ['nullable', 'integer', 'exists:comments,id'],
        ]);

        if (! empty($data['parent_id'])) {
            $parent = Comment::query()
                ->where('post_id', $post->id)
                ->whereKey($data['parent_id'])
                ->firstOrFail();

            abort_if(
                $parent->depth() >= Comment::MAX_DEPTH - 1,
                422,
                'Maximum reply depth reached.'
            );
        }

        $comment = Comment::query()->create([
            'post_id' => $post->id,
            'user_id' => $request->user()->id,
            'parent_id' => $data['parent_id'] ?? null,
            'body' => $data['body'],
        ]);

        CommentCreated::dispatch($comment);

        return back()->withFragment('comments')->with('status', 'comment-posted');
    }

    public function destroy(Comment $comment): RedirectResponse
    {
        abort_if(CommentSettings::useDisqus(), 404);

        $this->authorize('delete', $comment);

        $post = $comment->post()->with('authorAlias')->first();
        $comment->delete();

        return redirect()
            ->route('posts.show', [$post->authorAlias, $post->slug])
            ->withFragment('comments')
            ->with('status', 'comment-deleted');
    }

    public function update(Request $request, Comment $comment): RedirectResponse
    {
        abort_if(CommentSettings::useDisqus(), 404);

        $this->authorize('update', $comment);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $comment->update([
            'body' => $data['body'],
            'edited_at' => now(),
        ]);

        $post = $comment->post()->with('authorAlias')->first();

        return redirect()
            ->route('posts.show', [$post->authorAlias, $post->slug])
            ->withFragment('comment-'.$comment->id)
            ->with('status', 'comment-updated');
    }
}
