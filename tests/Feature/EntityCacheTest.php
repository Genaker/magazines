<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\SiteSetting;
use App\Support\EntityCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EntityCacheTest extends TestCase
{
    use RefreshDatabase;

    public function test_site_setting_uses_remember_forever_and_invalidates_on_update(): void
    {
        SiteSetting::setValue('site_name', 'Alpha');

        $this->assertSame('Alpha', SiteSetting::getValue('site_name'));

        SiteSetting::query()->where('key', 'site_name')->update(['value' => 'Stale']);

        $this->assertSame('Alpha', SiteSetting::getValue('site_name'));

        SiteSetting::setValue('site_name', 'Beta');

        $this->assertSame('Beta', SiteSetting::getValue('site_name'));
    }

    public function test_category_navigation_cache_refreshes_after_update(): void
    {
        $category = Category::query()->create(['name' => 'Old', 'slug' => 'old', 'sort_order' => 1]);

        $this->assertSame('Old', Category::cachedForNavigation()->first()->name);

        $category->update(['name' => 'New']);

        $this->assertSame('New', Category::cachedForNavigation()->first()->name);
    }

    public function test_post_show_cache_refreshes_after_update(): void
    {
        $post = Post::factory()->create(['title' => 'First title']);

        $this->assertSame('First title', Post::cachedForShow($post->author_alias_id, $post->slug)->title);

        $post->update(['title' => 'Updated title']);

        $this->assertSame('Updated title', Post::cachedForShow($post->author_alias_id, $post->slug)->title);
    }

    public function test_post_show_serves_fresh_view_counts_from_separate_query(): void
    {
        $post = Post::factory()->create([
            'title' => 'Cached title',
            'views_count' => 10,
            'likes_count' => 3,
        ]);

        Post::cachedForShow($post->author_alias_id, $post->slug);

        Post::query()->whereKey($post->id)->update([
            'views_count' => 42,
            'likes_count' => 7,
        ]);

        $cached = Post::cachedForShow($post->author_alias_id, $post->slug);

        $this->assertSame('Cached title', $cached->title);
        $this->assertSame(42, $cached->views_count);
        $this->assertSame(7, $cached->likes_count);
    }

    public function test_entity_cache_remembers_values(): void
    {
        $calls = 0;

        $first = EntityCache::remember('test:key', 60, function () use (&$calls) {
            $calls++;

            return 'value';
        });

        $second = EntityCache::remember('test:key', 60, function () use (&$calls) {
            $calls++;

            return 'value';
        });

        $this->assertSame('value', $first);
        $this->assertSame('value', $second);
        $this->assertSame(1, $calls);
    }

    public function test_entity_cache_can_be_disabled_via_config(): void
    {
        config(['entity-cache.enabled' => false]);

        $calls = 0;

        EntityCache::remember('test:disabled', 60, function () use (&$calls) {
            $calls++;

            return 'live';
        });

        EntityCache::remember('test:disabled', 60, function () use (&$calls) {
            $calls++;

            return 'live';
        });

        $this->assertSame(2, $calls);
        $this->assertFalse(EntityCache::enabled());
    }

    public function test_site_setting_reads_database_when_cache_disabled(): void
    {
        config(['entity-cache.enabled' => false]);

        SiteSetting::setValue('site_name', 'Cached name');

        SiteSetting::query()->where('key', 'site_name')->update(['value' => 'Direct read']);

        $this->assertSame('Direct read', SiteSetting::getValue('site_name'));
    }
}
