<?php

namespace Tests\Feature;

use App\Enums\PostStatus;
use App\Models\Category;
use App\Models\Post;
use App\Services\Search\PostSearchDocumentBuilder;
use App\Services\Search\RedisSearchClient;
use App\Services\Search\RedisSearchDriver;
use App\Support\SearchRedis;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('redis-search')]
class RedisSearchIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private RedisSearchClient $redis;

    private string $postsIndex;

    private string $keyPrefix;

    protected function setUp(): void
    {
        parent::setUp();

        if (! extension_loaded('redis')) {
            $this->markTestSkipped('phpredis extension is not loaded.');
        }

        $this->configureSearchRedisFromEnv();

        $this->redis = app(RedisSearchClient::class);

        if (! $this->redis->moduleAvailable()) {
            $this->markTestSkipped(
                'RediSearch module is not available. Use Redis Stack (redis/redis-stack-server), not plain Redis.',
            );
        }

        $suffix = bin2hex(random_bytes(4));
        $this->postsIndex = 'test_posts_'.$suffix;
        $this->keyPrefix = 'test_search_'.$suffix.':';

        config([
            'search.driver' => 'redis',
            'search.redis.prefix' => $this->keyPrefix,
            'search.indexes.posts' => $this->postsIndex,
            'search.indexes.authors' => 'test_authors_'.$suffix,
            'search.embeddings.provider' => 'none',
            'search.post.fulltext.fields' => ['title', 'subtitle', 'body', 'tags', 'author_name', 'category_name'],
            'search.post.fulltext.body_max_words' => 100,
            'search.preprocessing.remove_stop_words.fulltext_query' => true,
        ]);
    }

    /** Point the search Redis connection at REDIS_* / REDIS_SEARCH_* when running outside Docker defaults. */
    private function configureSearchRedisFromEnv(): void
    {
        $host = env('REDIS_SEARCH_HOST') ?: env('REDIS_HOST');
        $port = env('REDIS_SEARCH_PORT') ?: env('REDIS_PORT');

        if ($host !== null) {
            config(['database.redis.search.host' => $host]);
        }

        if ($port !== null) {
            config(['database.redis.search.port' => $port]);
        }

        // RediSearch indexes must live on database 0 (Redis Stack limitation).
        config(['database.redis.search.database' => (int) env('REDIS_SEARCH_DB', 0)]);
    }

    protected function tearDown(): void
    {
        if (isset($this->postsIndex, $this->redis) && $this->redis->moduleAvailable()) {
            $this->redis->dropIndex($this->postsIndex);
        }

        parent::tearDown();
    }

    public function test_indexes_and_finds_post_by_title(): void
    {
        $category = Category::query()->create(['name' => 'Tech', 'slug' => 'tech']);
        $post = Post::factory()->create([
            'category_id' => $category->id,
            'title' => 'RediSearch integration guide',
            'status' => PostStatus::Published,
            'published_at' => now(),
        ]);

        app(RedisSearchDriver::class)->indexPost($post);

        $results = app(RedisSearchDriver::class)->searchPosts('RediSearch');

        $this->assertTrue($results->pluck('id')->contains($post->id));
    }

    public function test_excludes_feed_hidden_posts_from_redis_search(): void
    {
        $category = Category::query()->create(['name' => 'News', 'slug' => 'news']);
        $post = Post::factory()->create([
            'category_id' => $category->id,
            'title' => 'Hidden redis integration story',
            'status' => PostStatus::Published,
            'published_at' => now(),
            'feed_hidden_at' => now(),
        ]);

        app(RedisSearchDriver::class)->indexPost($post);

        $results = app(RedisSearchDriver::class)->searchPosts('integration');

        $this->assertFalse($results->pluck('id')->contains($post->id));
    }

    public function test_body_truncation_limits_full_text_index(): void
    {
        config(['search.post.fulltext.body_max_words' => 10]);

        $category = Category::query()->create(['name' => 'Writing', 'slug' => 'writing']);
        $early = implode(' ', array_fill(0, 8, 'filler')).' earlytoken';
        $late = implode(' ', array_fill(0, 20, 'padding')).' latetoken';
        $post = Post::factory()->create([
            'category_id' => $category->id,
            'title' => 'Truncation test',
            'body' => '<p>'.$early.' '.$late.'</p>',
            'status' => PostStatus::Published,
            'published_at' => now(),
        ]);

        app(RedisSearchDriver::class)->indexPost($post);

        $driver = app(RedisSearchDriver::class);

        $this->assertTrue($driver->searchPosts('earlytoken')->pluck('id')->contains($post->id));
        $this->assertFalse($driver->searchPosts('latetoken')->pluck('id')->contains($post->id));
    }

    public function test_finds_post_by_category_name(): void
    {
        $category = Category::query()->create(['name' => 'Quantum Computing', 'slug' => 'quantum']);
        $post = Post::factory()->create([
            'category_id' => $category->id,
            'title' => 'Unrelated headline',
            'status' => PostStatus::Published,
            'published_at' => now(),
        ]);

        app(RedisSearchDriver::class)->indexPost($post);

        $results = app(RedisSearchDriver::class)->searchPosts('Quantum');

        $this->assertTrue($results->pluck('id')->contains($post->id));
    }

    public function test_respects_fulltext_field_configuration(): void
    {
        config(['search.post.fulltext.fields' => ['title']]);

        $category = Category::query()->create(['name' => 'Misc', 'slug' => 'misc']);
        $post = Post::factory()->create([
            'category_id' => $category->id,
            'title' => 'Visible title token',
            'body' => '<p>bodyonlytoken should not match</p>',
            'status' => PostStatus::Published,
            'published_at' => now(),
        ]);

        app(RedisSearchDriver::class)->indexPost($post);

        $driver = app(RedisSearchDriver::class);

        $this->assertTrue($driver->searchPosts('Visible')->pluck('id')->contains($post->id));
        $this->assertFalse($driver->searchPosts('bodyonlytoken')->pluck('id')->contains($post->id));
    }

    public function test_document_builder_stores_preprocessed_body_in_redis_hash(): void
    {
        config(['search.post.fulltext.body_max_words' => 3]);

        $category = Category::query()->create(['name' => 'Hash', 'slug' => 'hash']);
        $post = Post::factory()->create([
            'category_id' => $category->id,
            'body' => '<p>one two three four five</p>',
            'status' => PostStatus::Published,
            'published_at' => now(),
        ]);

        $builder = app(PostSearchDocumentBuilder::class);
        $key = $builder->key($post);
        $this->redis->hashSet($key, $builder->build($post));

        $raw = SearchRedis::command(['HGET', $key, 'body_text']);

        $this->assertSame('one two three', $raw);
    }
}
