<?php

namespace App\Http\Controllers;

use App\Models\AuthorAlias;
use App\Support\Seo;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class AuthorController extends Controller
{
    public function index(Request $request): View
    {
        $query = trim((string) $request->get('q', ''));

        $authors = AuthorAlias::query()
            ->active()
            ->listedInDirectory()
            ->withCount(['posts' => fn ($builder) => $builder->visibleOnAuthorProfile()])
            ->matchingSearch($query)
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return view('authors.index', compact('authors', 'query'));
    }

    public function show(AuthorAlias $alias): View
    {
        if (! $alias) {
            abort(404);
        }

        $account = $alias->user;

        if (! $account && ! $alias->isRetired()) {
            abort(404);
        }

        $page = max(1, (int) request()->query('page', 1));
        $viewer = auth()->user();

        if ($alias->isRetired()) {
            return view('authors.show', [
                'author' => $alias,
                'account' => $account,
                'isRetired' => true,
                'posts' => new LengthAwarePaginator([], 0, 12, $page, ['path' => request()->url()]),
                'articlesCount' => 0,
                'followersCount' => 0,
                'followingCount' => 0,
                'isFollowing' => false,
                'isSubscribed' => false,
                'subscriptionDelivery' => null,
                'isBlocked' => false,
                'viewerIsBlocked' => false,
            ]);
        }

        $stats = $alias->cachedAuthorStats();
        $isBlocked = $viewer && $viewer->id !== $account->id && $account->hasBlocked($viewer);

        return view('authors.show', [
            'author' => $alias,
            'account' => $account,
            'isRetired' => false,
            'seo' => Seo::forAuthor($alias),
            'posts' => $isBlocked
                ? new LengthAwarePaginator([], 0, 12, $page, ['path' => request()->url()])
                : $alias->cachedPublishedPosts($page, 12),
            'articlesCount' => $isBlocked ? 0 : $stats['articles'],
            'followersCount' => $stats['followers'],
            'followingCount' => $stats['following'],
            'isFollowing' => $viewer && $viewer->following()->where('following_id', $account->id)->exists(),
            'isSubscribed' => $viewer && $viewer->storySubscriptions()->where('author_id', $account->id)->exists(),
            'subscriptionDelivery' => $viewer
                ? $viewer->storySubscriptions()->where('author_id', $account->id)->value('email_delivery')
                : null,
            'isBlocked' => $viewer && $viewer->hasBlocked($account),
            'viewerIsBlocked' => $isBlocked,
        ]);
    }
}
