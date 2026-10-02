<?php

namespace Tests\Feature;

use App\Enums\PostStatus;
use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Services\Search\DatabaseSearchDriver;
use App\Services\Search\RecommendationService;
use App\Services\Search\SearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_driver_finds_posts_by_title(): void
    {
        $category = Category::query()->create(['name' => 'Tech', 'slug' => 'tech']);
        Post::factory()->create([
            'category_id' => $category->id,
            'title' => 'Redis Search guide',
            'status' => PostStatus::Published,
            'published_at' => now(),
        ]);

        $results = app(SearchService::class)->searchPosts('Redis');

        $this->assertCount(1, $results);
        $this->assertSame('Redis Search guide', $results->first()->title);
    }

    public function test_database_driver_excludes_feed_hidden_posts(): void
    {
        $category = Category::query()->create(['name' => 'News', 'slug' => 'news']);
        Post::factory()->create([
            'category_id' => $category->id,
            'title' => 'Hidden redis story',
            'status' => PostStatus::Published,
            'published_at' => now(),
            'feed_hidden_at' => now(),
        ]);

        $results = app(SearchService::class)->searchPosts('redis');

        $this->assertCount(0, $results);
    }

    public function test_recommendation_service_finds_posts_with_shared_tags(): void
    {
        $category = Category::query()->create(['name' => 'Culture', 'slug' => 'culture']);
        $tag = Tag::query()->create(['name' => 'laravel', 'slug' => 'laravel']);

        $source = Post::factory()->create([
            'category_id' => $category->id,
            'title' => 'Source post',
            'status' => PostStatus::Published,
            'published_at' => now()->subDay(),
        ]);
        $source->tags()->attach($tag);

        $related = Post::factory()->create([
            'category_id' => $category->id,
            'title' => 'Related laravel tips',
            'status' => PostStatus::Published,
            'published_at' => now(),
        ]);
        $related->tags()->attach($tag);

        Post::factory()->create([
            'category_id' => $category->id,
            'title' => 'Unrelated cooking',
            'status' => PostStatus::Published,
            'published_at' => now(),
        ]);

        $results = app(RecommendationService::class)->relatedByTaxonomy($source, 3);

        $this->assertTrue($results->pluck('id')->contains($related->id));
        $this->assertFalse($results->pluck('id')->contains($source->id));
    }

    public function test_search_controller_uses_search_service(): void
    {
        $category = Category::query()->create(['name' => 'Writing', 'slug' => 'writing']);
        Post::factory()->create([
            'category_id' => $category->id,
            'title' => 'Why Writing Matters',
            'status' => PostStatus::Published,
            'published_at' => now(),
        ]);

        $this->get('/search?q=writing')
            ->assertOk()
            ->assertSee('Why Writing Matters');
    }

    public function test_search_service_falls_back_to_database_when_redis_unconfigured(): void
    {
        config(['search.driver' => 'redis']);

        $this->mock(\App\Services\Search\RedisSearchDriver::class, function ($mock): void {
            $mock->shouldReceive('isAvailable')->andReturn(false);
        });

        $this->assertInstanceOf(DatabaseSearchDriver::class, app(SearchService::class)->driver());
    }

    public function test_database_driver_respects_configured_fulltext_fields(): void
    {
        config(['search.post.fulltext.fields' => ['title']]);

        $category = Category::query()->create(['name' => 'Tech', 'slug' => 'tech']);
        Post::factory()->create([
            'category_id' => $category->id,
            'title' => 'Title only match',
            'body' => '<p>uniquebodytoken xyz</p>',
            'status' => PostStatus::Published,
            'published_at' => now(),
        ]);

        $service = app(SearchService::class);

        $this->assertCount(1, $service->searchPosts('Title'));
        $this->assertCount(0, $service->searchPosts('uniquebodytoken'));
    }

    public function test_database_driver_finds_posts_by_category_when_configured(): void
    {
        config(['search.post.fulltext.fields' => ['category_name']]);

        $category = Category::query()->create(['name' => 'Astronomy', 'slug' => 'astronomy']);
        Post::factory()->create([
            'category_id' => $category->id,
            'title' => 'Plain headline',
            'body' => '<p>nothing special here</p>',
            'status' => PostStatus::Published,
            'published_at' => now(),
        ]);

        $results = app(SearchService::class)->searchPosts('Astronomy');

        $this->assertCount(1, $results);
    }
}
