<?php

namespace Tests\Feature;

use App\Enums\PostStatus;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\CategoryRedirect;
use App\Models\Magazine;
use App\Models\Post;
use App\Models\PostView;
use App\Models\User;
use App\Services\FeedService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryEditorialTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_trending_includes_hour_section(): void
    {
        $category = Category::query()->create(['name' => 'Science', 'slug' => 'science']);

        $sections = app(FeedService::class)->trendingSectionsForCategory($category->id);

        $this->assertArrayHasKey('trendingHour', $sections);
        $this->assertArrayHasKey('trendingWeek', $sections);
        $this->assertArrayHasKey('trendingDay', $sections);
    }

    public function test_category_front_shows_trending_sections_and_rss_link(): void
    {
        $category = Category::query()->create(['name' => 'Technology', 'slug' => 'technology']);
        $post = Post::factory()->create([
            'category_id' => $category->id,
            'title' => 'Trending tech story',
            'status' => PostStatus::Published,
            'published_at' => now()->subDay(),
        ]);

        PostView::query()->create([
            'post_id' => $post->id,
            'ip_address' => '127.0.0.1',
            'viewed_on' => now()->subDay()->startOfDay(),
            'created_at' => now()->subDay(),
        ]);

        $this->get(route('categories.show', $category))
            ->assertOk()
            ->assertSee('Trending tech story')
            ->assertSee(__('app.top_stories_week'))
            ->assertSee(__('app.trending_last_hour'))
            ->assertSee(__('app.trending_today'))
            ->assertSee(__('app.latest'))
            ->assertSee('RSS');
    }

    public function test_category_rss_includes_posts_from_subcategories(): void
    {
        $parent = Category::query()->create(['name' => 'Science', 'slug' => 'science']);
        $child = Category::query()->create([
            'name' => 'Physics',
            'slug' => 'physics',
            'parent_id' => $parent->id,
        ]);

        Post::factory()->create([
            'category_id' => $child->id,
            'title' => 'Quantum notes',
            'status' => PostStatus::Published,
            'published_at' => now(),
        ]);

        $this->get(route('categories.rss', $parent))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee('Quantum notes');
    }

    public function test_old_category_slug_redirects_to_target_on_show_and_rss(): void
    {
        $target = Category::query()->create(['name' => 'Merged Into', 'slug' => 'merged-into']);
        CategoryRedirect::register('old-section', $target);

        $this->get('/category/old-section')
            ->assertRedirect(route('categories.show', $target));

        $this->get('/category/old-section/feed.rss')
            ->assertRedirect(route('categories.rss', $target));
    }

    public function test_old_slug_redirect_is_permanent(): void
    {
        $target = Category::query()->create(['name' => 'Target', 'slug' => 'target']);
        CategoryRedirect::register('legacy-slug', $target);

        $this->get('/category/legacy-slug')
            ->assertStatus(301);
    }

    public function test_category_rss_channel_uses_category_name(): void
    {
        $category = Category::query()->create([
            'name' => 'Climate',
            'slug' => 'climate',
            'description' => 'Stories about climate change.',
        ]);

        Post::factory()->create([
            'category_id' => $category->id,
            'title' => 'Climate report',
            'status' => PostStatus::Published,
            'published_at' => now(),
        ]);

        $this->get(route('categories.rss', $category))
            ->assertOk()
            ->assertSee('Climate ·')
            ->assertSee('Stories about climate change.');
    }

    public function test_magazine_category_url_redirects_to_magazine_page(): void
    {
        $owner = User::factory()->create();
        $magazine = Magazine::query()->create([
            'owner_id' => $owner->id,
            'name' => 'City Paper',
            'slug' => 'city-paper',
        ]);
        $beat = Category::query()->create([
            'magazine_id' => $magazine->id,
            'name' => 'Local News',
            'slug' => 'city-paper-local-news',
        ]);

        $this->get(route('categories.show', $beat))
            ->assertRedirect(route('magazines.show', [
                'magazine' => $magazine,
                'category' => $beat->slug,
            ]));
    }

    public function test_magazine_category_rss_is_not_available_on_site_route(): void
    {
        $owner = User::factory()->create();
        $magazine = Magazine::query()->create([
            'owner_id' => $owner->id,
            'name' => 'City Paper',
            'slug' => 'city-paper',
        ]);
        $beat = Category::query()->create([
            'magazine_id' => $magazine->id,
            'name' => 'Local News',
            'slug' => 'city-paper-local-news',
        ]);

        $this->get(route('categories.rss', $beat))
            ->assertNotFound();
    }

    public function test_renamed_category_slug_redirects_to_new_url(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $category = Category::query()->create(['name' => 'Climate Change', 'slug' => 'climate-change']);

        $this->actingAs($admin)->put(route('admin.categories.update', $category), [
            'name' => 'Climate Crisis',
        ])->assertRedirect(route('admin.categories.index'));

        $category->refresh();
        $this->assertSame('climate-crisis', $category->slug);

        $this->get('/category/climate-change')
            ->assertRedirect(route('categories.show', $category));

        $this->assertDatabaseHas('category_redirects', [
            'slug' => 'climate-change',
            'category_id' => $category->id,
        ]);
    }
}
