<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\CategoryRedirect;
use App\Models\Post;
use App\Services\CategoryShowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function __construct(private CategoryShowService $categoryShow) {}

    public function show(string $slug): View|RedirectResponse
    {
        $redirect = CategoryRedirect::query()
            ->where('slug', $slug)
            ->with('category')
            ->first();

        if ($redirect?->category) {
            return redirect()->route('categories.show', $redirect->category->slug, 301);
        }

        $category = Category::query()->where('slug', $slug)->firstOrFail();

        if ($category->magazine_id !== null) {
            $category->loadMissing('magazine');

            return redirect()->route('magazines.show', [
                $category->magazine,
                'category' => $category->slug,
            ]);
        }

        $page = max(1, (int) request()->query('page', 1));
        $category->load('parent');
        $childCategories = Category::query()
            ->where('parent_id', $category->id)
            ->whereNull('magazine_id')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('categories.show', [
            'category' => $category,
            'childCategories' => $childCategories,
            'sections' => $this->categoryShow->sections($category),
            'posts' => Post::cachedPublishedForCategory($category->id, $page),
            'isFollowing' => auth()->check() && auth()->user()->followedCategories()->where('category_id', $category->id)->exists(),
        ]);
    }
}
