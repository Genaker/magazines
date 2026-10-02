<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Services\TaxonomyModerationService;
use App\Support\TaxonomyModerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ModerationController extends Controller
{
    public function __construct(private TaxonomyModerationService $moderation) {}

    public function index(Request $request): View
    {
        $user = $request->user();

        abort_unless(
            TaxonomyModerator::isTaxonomyModerator($user) || $user->isAdmin(),
            403,
        );

        $categories = $user->moderatedCategories()
            ->whereNull('magazine_id')
            ->orderBy('name')
            ->get();

        $tags = $user->moderatedTags()->orderBy('name')->get();

        return view('moderation.index', compact('categories', 'tags'));
    }

    public function category(Request $request, Category $category): View
    {
        abort_unless(TaxonomyModerator::canModerateCategory($request->user(), $category), 403);
        abort_if($category->magazine_id !== null, 404);

        $posts = $this->moderation->postsForCategory($category);
        $moveTargets = Category::query()
            ->whereNull('magazine_id')
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('moderation.category', compact('category', 'posts', 'moveTargets'));
    }

    public function tag(Request $request, Tag $tag): View
    {
        abort_unless(TaxonomyModerator::canModerateTag($request->user(), $tag), 403);

        $posts = $this->moderation->postsForTag($tag);

        return view('moderation.tag', compact('tag', 'posts'));
    }

    public function hideFromFeed(Request $request, Post $post): RedirectResponse
    {
        $this->moderation->hideFromFeed($post, $request->user());

        return back()->with('status', 'Post hidden from feeds.');
    }

    public function showInFeed(Request $request, Post $post): RedirectResponse
    {
        $this->moderation->showInFeed($post, $request->user());

        return back()->with('status', 'Post restored to feeds.');
    }

    public function updateCategory(Request $request, Post $post): RedirectResponse
    {
        $data = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
        ]);

        $category = Category::query()->findOrFail($data['category_id']);
        $this->moderation->moveCategory($post, $category, $request->user());

        return back()->with('status', 'Category updated.');
    }

    public function updateTags(Request $request, Post $post): RedirectResponse
    {
        $data = $request->validate([
            'tags' => ['required', 'string', 'max:500'],
        ]);

        $tagNames = array_map('trim', explode(',', $data['tags']));
        $this->moderation->syncTags($post, $tagNames, $request->user());

        return back()->with('status', 'Tags updated.');
    }
}
