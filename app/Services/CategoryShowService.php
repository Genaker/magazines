<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Post;
use Illuminate\Support\Collection;

/** Editorial section front data for a site category hub. */
class CategoryShowService
{
    public function __construct(private FeedService $feedService) {}

    /**
     * Trending sections scoped to the category subtree.
     *
     * @return array{trendingWeek: Collection<int, Post>, trendingHour: Collection<int, Post>, trendingDay: Collection<int, Post>}
     */
    public function sections(Category $category): array
    {
        return $this->feedService->trendingSectionsForCategory($category->id);
    }
}
