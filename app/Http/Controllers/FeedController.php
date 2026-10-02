<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Services\FeedService;
use App\Support\HomeLayout;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class FeedController extends Controller
{
    public function __construct(private FeedService $feedService) {}

    public function home(): View
    {
        $page = max(1, (int) request()->query('page', 1));
        $layout = HomeLayout::current();

        $data = [
            'layout' => $layout,
            'tagCloud' => $this->feedService->popularTagCloud(15),
        ];

        if ($layout === HomeLayout::Latest) {
            $data['posts'] = Post::cachedPublishedForFeed($page);
        } elseif ($layout === HomeLayout::Trending) {
            $data['sections'] = $this->feedService->trendingSections();
        } else {
            $data['sections'] = $this->feedService->discoverSections();
            $data['posts'] = Post::cachedPublishedForFeed($page);

            if (auth()->check()) {
                $data['personalized'] = $this->feedService->personalizedFeedSections(auth()->user());
            }
        }

        return view('feed.home', $data);
    }

    public function discover(): RedirectResponse
    {
        return redirect()->route('home');
    }
}
