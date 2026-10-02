<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use App\Support\EntityCache;
use App\Support\HomeLayout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsAdminSettingsPayload;
use Tests\TestCase;

class HomeLayoutTest extends TestCase
{
    use BuildsAdminSettingsPayload;
    use RefreshDatabase;

    public function test_home_shows_category_like_sections_with_popular_tags(): void
    {
        HomeLayout::set(HomeLayout::Discover);

        $tag = Tag::query()->create(['name' => 'tech', 'slug' => 'tech']);
        $posts = Post::factory()->count(3)->create();
        $posts->each(fn (Post $post) => $post->tags()->attach($tag));
        EntityCache::flushTag(config('entity-cache.tags.feeds'));

        $this->get(route('home'))
            ->assertOk()
            ->assertSee(__('app.top_stories_week'), false)
            ->assertSee(__('app.trending_last_hour'), false)
            ->assertSee(__('app.trending_today'), false)
            ->assertSee(__('app.latest'), false)
            ->assertSee(__('app.popular_tags'), false);
    }

    public function test_super_admin_can_set_home_layout(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);

        $this->actingAs($admin)
            ->put(route('admin.settings.update'), $this->adminSettingsPayload([
                'site_name' => 'Magazines',
                'home_layout' => HomeLayout::Latest->value,
            ]))
            ->assertRedirect(route('admin.settings.edit'));

        $this->assertSame(HomeLayout::Latest, HomeLayout::current());
    }
}
