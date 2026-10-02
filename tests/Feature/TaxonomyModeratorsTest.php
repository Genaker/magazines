<?php

namespace Tests\Feature;

use App\Enums\PostStatus;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use App\Services\FeedService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaxonomyModeratorsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_assigns_category_moderator(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $moderator = User::factory()->create(['email' => 'mod@example.com']);
        $category = Category::query()->create(['name' => 'Technology', 'slug' => 'technology']);

        $this->actingAs($admin)->put(route('admin.categories.update', $category), [
            'name' => 'Technology',
            'moderator_emails' => "mod@example.com\n",
        ])->assertRedirect(route('admin.categories.index'));

        $this->assertTrue($moderator->fresh()->moderatedCategories()->whereKey($category->id)->exists());
    }

    public function test_category_moderator_can_hide_post_from_feeds(): void
    {
        $moderator = User::factory()->create();
        $category = Category::query()->create(['name' => 'Culture', 'slug' => 'culture']);
        $category->moderators()->attach($moderator);

        $post = Post::factory()->create([
            'category_id' => $category->id,
            'title' => 'Off-topic story',
            'status' => PostStatus::Published,
            'published_at' => now(),
        ]);

        $this->actingAs($moderator)
            ->post(route('moderation.posts.hide-feed', $post))
            ->assertRedirect();

        $post->refresh();
        $this->assertNotNull($post->feed_hidden_at);
    }

    public function test_hidden_post_is_excluded_from_home_trending(): void
    {
        $category = Category::query()->create(['name' => 'News', 'slug' => 'news']);
        Post::factory()->create([
            'category_id' => $category->id,
            'title' => 'Visible story',
            'status' => PostStatus::Published,
            'published_at' => now(),
            'views_count' => 100,
        ]);
        Post::factory()->create([
            'category_id' => $category->id,
            'title' => 'Hidden story',
            'status' => PostStatus::Published,
            'published_at' => now(),
            'views_count' => 200,
            'feed_hidden_at' => now(),
        ]);

        $sections = app(FeedService::class)->discoverSections();
        $titles = $sections['popularWeek']->pluck('title');

        $this->assertTrue($titles->contains('Visible story'));
        $this->assertFalse($titles->contains('Hidden story'));
    }

    public function test_tag_moderator_can_update_tags(): void
    {
        $moderator = User::factory()->create();
        $tag = Tag::query()->create(['name' => 'discuss', 'slug' => 'discuss']);
        $tag->moderators()->attach($moderator);

        $post = Post::factory()->create([
            'title' => 'Mis-tagged post',
            'status' => PostStatus::Published,
            'published_at' => now(),
        ]);
        $post->tags()->attach($tag);

        $this->actingAs($moderator)
            ->put(route('moderation.posts.tags', $post), ['tags' => 'help, discuss'])
            ->assertRedirect();

        $post->refresh();
        $names = $post->tags()->pluck('name')->sort()->values()->all();

        $this->assertContains('help', $names);
        $this->assertContains('discuss', $names);
    }

    public function test_non_moderator_cannot_access_moderation_queue(): void
    {
        $user = User::factory()->create();
        $category = Category::query()->create(['name' => 'Sports', 'slug' => 'sports']);

        $this->actingAs($user)
            ->get(route('moderation.categories.show', $category))
            ->assertForbidden();
    }

    public function test_moderator_sees_moderation_link_in_profile_sidebar(): void
    {
        $moderator = User::factory()->create();
        $category = Category::query()->create(['name' => 'Art', 'slug' => 'art']);
        $category->moderators()->attach($moderator);

        $this->actingAs($moderator)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Moderation');
    }

    public function test_category_moderator_can_move_post_to_another_site_category(): void
    {
        $moderator = User::factory()->create();
        $from = Category::query()->create(['name' => 'From', 'slug' => 'from']);
        $to = Category::query()->create(['name' => 'To', 'slug' => 'to']);
        $from->moderators()->attach($moderator);

        $post = Post::factory()->create([
            'category_id' => $from->id,
            'status' => PostStatus::Published,
            'published_at' => now(),
        ]);

        $this->actingAs($moderator)
            ->put(route('moderation.posts.category', $post), ['category_id' => $to->id])
            ->assertRedirect();

        $this->assertSame($to->id, $post->fresh()->category_id);
    }
}
