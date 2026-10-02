<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Magazine;
use App\Models\Post;
use App\Models\User;
use App\Support\AdminGrid;
use App\Support\EntityCache;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TrashController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        $trashedUsers = User::onlyTrashed()
            ->when($search !== '', fn ($query) => AdminGrid::applySearch($query, $search, ['name', 'email', 'username']))
            ->latest('deleted_at')
            ->paginate(15, ['*'], 'users_page')
            ->withQueryString();

        $trashedPosts = Post::onlyTrashed()
            ->with(['user', 'category'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('title', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($user) => $user->where('name', 'like', "%{$search}%"));
                });
            })
            ->latest('deleted_at')
            ->paginate(15, ['*'], 'posts_page')
            ->withQueryString();

        $trashedMagazines = Magazine::onlyTrashed()
            ->with('owner')
            ->when($search !== '', fn ($query) => AdminGrid::applySearch($query, $search, ['name', 'slug']))
            ->latest('deleted_at')
            ->paginate(15, ['*'], 'magazines_page')
            ->withQueryString();

        return view('admin.trash.index', compact('trashedUsers', 'trashedPosts', 'trashedMagazines', 'search'));
    }

    public function restoreUser(int $userId): RedirectResponse
    {
        $user = User::onlyTrashed()->findOrFail($userId);
        $user->restore();

        return back()->with('status', 'user-restored');
    }

    public function forceDeleteUser(int $userId): RedirectResponse
    {
        $user = User::onlyTrashed()->findOrFail($userId);
        $user->forceDelete();

        return back()->with('status', 'user-permanently-deleted');
    }

    public function restorePost(int $postId): RedirectResponse
    {
        $post = Post::onlyTrashed()->findOrFail($postId);
        $post->restore();

        return back()->with('status', 'post-restored');
    }

    public function forceDeletePost(int $postId): RedirectResponse
    {
        $post = Post::onlyTrashed()->findOrFail($postId);
        $post->forceDelete();

        return back()->with('status', 'post-permanently-deleted');
    }

    public function restoreMagazine(int $magazineId): RedirectResponse
    {
        $magazine = Magazine::onlyTrashed()->findOrFail($magazineId);
        $magazine->restore();

        EntityCache::flushTag(config('entity-cache.tags.magazines'));

        return back()->with('status', 'magazine-restored');
    }

    public function forceDeleteMagazine(int $magazineId): RedirectResponse
    {
        $magazine = Magazine::onlyTrashed()->findOrFail($magazineId);
        $magazine->forceDelete();

        EntityCache::flushTag(config('entity-cache.tags.magazines'));

        return back()->with('status', 'magazine-permanently-deleted');
    }
}
