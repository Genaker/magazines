<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PostStatus;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Post;
use App\Support\AdminGrid;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PostController extends Controller
{
    public function index(Request $request): View
    {
        ['sort' => $sort, 'dir' => $dir, 'search' => $search] = AdminGrid::params(
            $request,
            ['title', 'status', 'views_count', 'created_at'],
            'created_at',
        );

        $status = $request->query('status');

        $posts = Post::query()
            ->with(['user', 'category'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('title', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($user) => $user->where('name', 'like', "%{$search}%")
                            ->orWhere('username', 'like', "%{$search}%"));
                });
            })
            ->when(in_array($status, ['draft', 'published', 'unlisted'], true), fn ($query) => $query->where('status', $status))
            ->orderBy($sort, $dir)
            ->paginate(20)
            ->withQueryString();

        return view('admin.posts.index', compact('posts', 'search', 'sort', 'dir', 'status'));
    }

    public function edit(Post $post): View
    {
        return view('admin.posts.edit', [
            'post' => $post->load(['user', 'category']),
            'categories' => Category::cachedForSelect(),
        ]);
    }

    public function update(Request $request, Post $post): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'status' => ['required', 'in:draft,published,unlisted'],
            'category_id' => ['nullable', 'exists:categories,id'],
        ]);

        $wasPublished = $post->status === PostStatus::Published;
        $slug = $data['title'] !== $post->title
            ? Post::uniqueSlugForAlias($data['title'], $post->author_alias_id, $post->id)
            : $post->slug;

        $categoryId = filled($data['category_id'] ?? null) ? (int) $data['category_id'] : null;

        $post->update([
            'title' => $data['title'],
            'slug' => $slug,
            'status' => $data['status'],
            'category_id' => $categoryId,
            'published_at' => $data['status'] === PostStatus::Published->value && ! $wasPublished
                ? now()
                : ($data['status'] === PostStatus::Published->value ? $post->published_at : null),
        ]);

        return redirect()->route('admin.posts.index');
    }

    public function destroy(Post $post): RedirectResponse
    {
        $post->delete();

        return back();
    }
}
