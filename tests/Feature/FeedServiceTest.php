<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\PostView;
use App\Models\Tag;
use App\Models\User;
use App\Services\FeedService;
use App\Support\EntityCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeedServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        EntityCache::flushStore();
    }

    public function test_personalized_feed_is_empty_when_user_follows_nothing(): void
    {
        Post::factory()->count(2)->create();
        $user = User::factory()->create();

        $sections = app(FeedService::class)->personalizedFeedSections($user);

        $this->assertCount(0, $sections['latest']);
        $this->assertCount(0, $sections['popular']);
    }

    public function test_personalized_feed_includes_posts_from_followed_tags(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $tag = Tag::query()->create(['name' => 'PHP', 'slug' => 'php']);

        $matchingPost = Post::factory()->for($otherUser)->create();
        $matchingPost->tags()->attach($tag);

        Post::factory()->for(User::factory()->create())->create();

        $user->followedTags()->attach($tag);

        $sections = app(FeedService::class)->personalizedFeedSections($user);

        $this->assertTrue($sections['latest']->pluck('id')->contains($matchingPost->id));
        $this->assertCount(1, $sections['latest']);
    }

    public function test_personalized_feed_includes_posts_from_followed_categories(): void
    {
        $user = User::factory()->create();
        $followedCategory = Category::query()->create(['name' => 'Tech', 'slug' => 'tech']);
        $otherCategory = Category::query()->create(['name' => 'Culture', 'slug' => 'culture']);

        $matchingPost = Post::factory()->create(['category_id' => $followedCategory->id]);
        Post::factory()->create(['category_id' => $otherCategory->id]);

        $user->followedCategories()->attach($followedCategory);

        $sections = app(FeedService::class)->personalizedFeedSections($user);

        $this->assertTrue($sections['latest']->pluck('id')->contains($matchingPost->id));
        $this->assertCount(1, $sections['latest']);
    }

    public function test_discover_sections_return_limited_story_lists(): void
    {
        $posts = Post::factory()->count(7)->create();

        foreach ($posts as $post) {
            PostView::query()->create([
                'post_id' => $post->id,
                'ip_address' => '10.0.0.'.$post->id,
                'viewed_on' => now()->startOfDay(),
                'created_at' => now(),
            ]);
        }

        $sections = app(FeedService::class)->discoverSections();

        $this->assertCount(5, $sections['popularWeek']);
        $this->assertCount(5, $sections['popularHour']);
        $this->assertCount(5, $sections['popularDay']);
        $this->assertCount(5, $sections['latest']);
    }

    public function test_trending_hour_and_day_fall_back_to_all_time_popular_without_view_records(): void
    {
        Post::factory()->count(2)->create([
            'published_at' => now()->subDays(10),
            'views_count' => 50,
        ]);

        $sections = app(FeedService::class)->discoverSections();

        $this->assertCount(2, $sections['popularHour']);
        $this->assertCount(2, $sections['popularDay']);
    }

    public function test_trending_week_falls_back_to_all_time_popular_without_view_records(): void
    {
        $popularOld = Post::factory()->create([
            'published_at' => now()->subDays(30),
            'views_count' => 200,
        ]);
        Post::factory()->count(2)->create([
            'published_at' => now()->subDays(2),
            'views_count' => 10,
        ]);

        $sections = app(FeedService::class)->discoverSections();

        $this->assertCount(3, $sections['popularWeek']);
        $this->assertSame($popularOld->id, $sections['popularWeek']->first()->id);
    }

    public function test_trending_today_uses_rolling_twenty_four_hour_window(): void
    {
        $olderPost = Post::factory()->create([
            'published_at' => now()->subDays(5),
            'views_count' => 3,
        ]);
        $newerPost = Post::factory()->create([
            'published_at' => now()->subHours(2),
            'views_count' => 0,
        ]);
        $expiredPost = Post::factory()->create([
            'published_at' => now()->subDays(2),
            'views_count' => 1,
        ]);

        PostView::query()->create([
            'post_id' => $olderPost->id,
            'ip_address' => '127.0.0.1',
            'viewed_on' => now()->startOfDay(),
            'created_at' => now()->subHours(3),
        ]);

        PostView::query()->create([
            'post_id' => $expiredPost->id,
            'ip_address' => '127.0.0.3',
            'viewed_on' => now()->subDay()->startOfDay(),
            'created_at' => now()->subHours(25),
        ]);

        $sections = app(FeedService::class)->discoverSections();

        $this->assertTrue($sections['popularDay']->pluck('id')->contains($olderPost->id));
        $this->assertTrue($sections['popularDay']->pluck('id')->contains($newerPost->id));
        $this->assertFalse($sections['popularDay']->pluck('id')->contains($expiredPost->id));
    }

    public function test_trending_last_hour_only_includes_recent_views(): void
    {
        $recentPost = Post::factory()->create(['published_at' => now()->subDays(3)]);
        $stalePost = Post::factory()->create(['published_at' => now()->subDays(3)]);

        PostView::query()->create([
            'post_id' => $recentPost->id,
            'ip_address' => '127.0.0.1',
            'viewed_on' => now()->startOfDay(),
            'created_at' => now()->subMinutes(10),
        ]);

        PostView::query()->create([
            'post_id' => $stalePost->id,
            'ip_address' => '127.0.0.2',
            'viewed_on' => now()->startOfDay(),
            'created_at' => now()->subHours(2),
        ]);

        $sections = app(FeedService::class)->discoverSections();

        $this->assertTrue($sections['popularHour']->pluck('id')->contains($recentPost->id));
        $this->assertFalse($sections['popularHour']->pluck('id')->contains($stalePost->id));
        $this->assertTrue($sections['popularDay']->pluck('id')->contains($stalePost->id));
    }
}
