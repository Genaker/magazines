<?php

namespace Tests\Feature;

use App\Enums\PostStatus;
use App\Models\Category;
use App\Models\Post;
use App\Services\Search\SearchService;
use App\Support\PgSearchSupport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/** @group postgres-search */
class PostgresPgSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Requires PostgreSQL.');
        }

        if (! PgSearchSupport::extensionInstalled()) {
            $this->markTestSkipped('Requires pg_search (ParadeDB PostgreSQL image).');
        }

        config(['search.postgres.pg_search.enabled' => true]);
        config(['search.driver' => 'postgres']);

        PgSearchSupport::installIndexes();
    }

    public function test_pg_search_finds_posts_by_title_with_bm25_ranking(): void
    {
        $category = Category::query()->create(['name' => 'Tech', 'slug' => 'tech']);

        Post::factory()->create([
            'category_id' => $category->id,
            'title' => 'Redis Search guide',
            'status' => PostStatus::Published,
            'published_at' => now(),
        ]);

        Post::factory()->create([
            'category_id' => $category->id,
            'title' => 'Unrelated cooking tips',
            'status' => PostStatus::Published,
            'published_at' => now(),
        ]);

        $results = app(SearchService::class)->searchPosts('Redis');

        $this->assertCount(1, $results);
        $this->assertSame('Redis Search guide', $results->first()->title);
    }

    public function test_pg_search_excludes_feed_hidden_posts(): void
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
}
