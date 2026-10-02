<?php

namespace Tests\Feature;

use App\Enums\PostStatus;
use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SyndicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_rss_feed_lists_published_posts(): void
    {
        $user = User::factory()->create(['name' => 'Feed Author']);
        $alias = $user->primaryAlias();
        $category = Category::query()->create(['name' => 'News', 'slug' => 'news']);

        Post::factory()->create([
            'user_id' => $user->id,
            'author_alias_id' => $alias->id,
            'category_id' => $category->id,
            'title' => 'Feed Story Alpha',
            'slug' => 'feed-story-alpha',
            'status' => PostStatus::Published,
            'published_at' => now()->subHour(),
        ]);

        Post::factory()->draft()->create([
            'user_id' => $user->id,
            'author_alias_id' => $alias->id,
            'category_id' => $category->id,
            'title' => 'Hidden Draft',
        ]);

        $response = $this->get(route('syndication.rss'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
        $response->assertSee('Feed Story Alpha', false);
        $response->assertSee('<rss version="2.0"', false);
        $response->assertDontSee('Hidden Draft');
    }

    public function test_atom_feed_lists_published_posts(): void
    {
        $user = User::factory()->create();
        $alias = $user->primaryAlias();
        $category = Category::query()->create(['name' => 'Tech', 'slug' => 'tech']);

        Post::factory()->create([
            'user_id' => $user->id,
            'author_alias_id' => $alias->id,
            'category_id' => $category->id,
            'title' => 'Atom Story',
            'slug' => 'atom-story',
            'status' => PostStatus::Published,
            'published_at' => now(),
        ]);

        $response = $this->get(route('syndication.atom'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
        $response->assertSee('<feed xmlns="http://www.w3.org/2005/Atom">', false);
        $response->assertSee('Atom Story', false);
    }

    public function test_sitemap_includes_home_and_published_post_urls(): void
    {
        $user = User::factory()->create();
        $alias = $user->primaryAlias();
        $category = Category::query()->create(['name' => 'Culture', 'slug' => 'culture']);

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'author_alias_id' => $alias->id,
            'category_id' => $category->id,
            'title' => 'Sitemap Story',
            'slug' => 'sitemap-story',
            'status' => PostStatus::Published,
            'published_at' => now(),
        ]);

        $response = $this->get(route('syndication.sitemap'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
        $response->assertSee('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">', false);
        $response->assertSee(route('home'), false);
        $response->assertSee(route('posts.show', [$alias, $post->slug]), false);
        $response->assertSee(route('categories.show', $category), false);
    }

    public function test_robots_txt_points_to_sitemap(): void
    {
        $this->get(route('syndication.robots'))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->assertSee('Sitemap: '.route('syndication.sitemap'), false);
    }
}
