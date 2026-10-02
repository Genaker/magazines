<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeedPostLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_discover_feed_links_resolve_to_visible_articles(): void
    {
        $author = User::factory()->create(['username' => 'demoauthor']);
        $category = Category::query()->create(['name' => 'Tech', 'slug' => 'tech']);
        $post = Post::factory()->for($author)->for($category)->create([
            'title' => 'E2E Published 1780902642117',
            'slug' => 'e2e-published-1780902642117',
            'body' => '<p>Feed link body content for readers.</p>',
        ]);

        $discover = $this->get(route('home'))->assertOk();

        $url = route('posts.show', [$author->username, $post->slug]);
        $discover->assertSee($url, false);

        $this->get($url)
            ->assertOk()
            ->assertSee('E2E Published 1780902642117')
            ->assertSee('Feed link body content for readers.', false);
    }

    public function test_discover_feed_does_not_link_to_removed_posts_from_stale_cache(): void
    {
        $author = User::factory()->create(['username' => 'demoauthor']);
        $category = Category::query()->create(['name' => 'Tech', 'slug' => 'tech']);
        $post = Post::factory()->for($author)->for($category)->create([
            'title' => 'Soon removed',
            'slug' => 'soon-removed',
        ]);

        $sections = app(\App\Services\FeedService::class)->discoverSections();
        $this->assertTrue($sections['latest']->pluck('id')->contains($post->id));

        $post->delete();

        $sections = app(\App\Services\FeedService::class)->discoverSections();
        $this->assertFalse($sections['latest']->pluck('id')->contains($post->id));
    }
}
