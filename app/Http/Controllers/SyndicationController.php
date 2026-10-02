<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\CategoryRedirect;
use App\Support\Syndication;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

class SyndicationController extends Controller
{
    public function rss(): Response
    {
        return $this->xmlResponse('syndication.rss', [
            'posts' => Syndication::feedPosts(config('syndication.feed_limit', 50)),
        ]);
    }

    public function atom(): Response
    {
        return $this->xmlResponse('syndication.atom', [
            'posts' => Syndication::feedPosts(config('syndication.feed_limit', 50)),
        ]);
    }

    public function categoryRss(string $slug): Response|RedirectResponse
    {
        $redirect = CategoryRedirect::query()
            ->where('slug', $slug)
            ->with('category')
            ->first();

        if ($redirect?->category) {
            return redirect()->route('categories.rss', $redirect->category->slug, 301);
        }

        $category = Category::query()
            ->where('slug', $slug)
            ->whereNull('magazine_id')
            ->firstOrFail();

        return $this->xmlResponse('syndication.category-rss', [
            'category' => $category,
            'posts' => Syndication::feedPostsForCategory($category, config('syndication.feed_limit', 50)),
        ]);
    }

    public function sitemap(): Response
    {
        return $this->xmlResponse('syndication.sitemap', [
            'entries' => Syndication::sitemapEntries(),
        ]);
    }

    public function robots(): Response
    {
        $lines = [
            'User-agent: *',
            'Allow: /',
            'Sitemap: '.route('syndication.sitemap'),
        ];

        return response(implode("\n", $lines)."\n", 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }

    private function xmlResponse(string $view, array $data): Response
    {
        return response()
            ->view($view, $data)
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
