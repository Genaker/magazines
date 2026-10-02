<?php

namespace Tests\Feature;

use App\Enums\PostStatus;
use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostPublishShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_seeded_style_published_post_by_url(): void
    {
        $author = User::factory()->create(['username' => 'demoauthor']);
        $category = Category::query()->create(['name' => 'Writing', 'slug' => 'writing']);
        $post = Post::factory()->for($author)->for($category)->create([
            'title' => 'Why Writing Matters',
            'slug' => 'why-writing-matters',
            'subtitle' => 'Sharing ideas in public',
            'body' => '<p>Writing online helps you clarify your thinking.</p>',
        ]);

        $url = route('posts.show', [$author->username, $post->slug]);

        $this->get($url)
            ->assertOk()
            ->assertSee('Why Writing Matters');

        $this->get($url)->assertOk();
    }

    public function test_publish_redirect_url_is_accessible_for_guests(): void
    {
        $author = User::factory()->create(['username' => 'demoauthor']);
        $category = Category::query()->create(['name' => 'Tech', 'slug' => 'tech']);

        $response = $this->actingAs($author)->post(route('posts.store'), [
            'title' => 'E2E Published Story',
            'subtitle' => 'Visible after publish',
            'body' => '<p>Published body content.</p>',
            'category_id' => $category->id,
            'status' => 'published',
        ]);

        $response->assertRedirect();

        $post = Post::query()->where('user_id', $author->id)->latest('id')->first();

        $this->assertNotNull($post);
        $this->assertSame(PostStatus::Published, $post->status);
        $this->assertNotNull($post->published_at);
        $response->assertRedirect(route('posts.show', [$author->username, $post->slug]));

        $this->get(route('posts.show', [$author->username, $post->slug]))
            ->assertOk()
            ->assertSee('E2E Published Story');
    }

    public function test_publish_after_autosave_draft_uses_current_slug_in_show_url(): void
    {
        $author = User::factory()->create(['username' => 'demoauthor']);
        $category = Category::query()->create(['name' => 'Tech', 'slug' => 'tech']);

        $this->actingAs($author)->postJson(route('posts.autosave'), [
            'title' => 'E2E Published 1780900079370',
            'body' => '<p>Autosaved body</p>',
            'category_id' => $category->id,
        ])->assertOk();

        $draft = Post::query()->where('user_id', $author->id)->firstOrFail();

        $response = $this->actingAs($author)->post(route('posts.store'), [
            'post_id' => $draft->id,
            'title' => 'E2E Published 1780900079370',
            'subtitle' => 'Subtitle',
            'body' => '<p>Autosaved body</p>',
            'category_id' => $category->id,
            'status' => 'published',
        ]);

        $draft->refresh();

        $this->assertSame('e2e-published-1780900079370', $draft->slug);
        $response->assertRedirect(route('posts.show', [$author->username, $draft->slug]));

        $this->get(route('posts.show', [$author->username, 'e2e-published-1780900079370']))
            ->assertOk()
            ->assertSee('E2E Published 1780900079370');
    }

    public function test_post_show_lists_other_stories_from_same_author(): void
    {
        $author = User::factory()->create(['username' => 'serialauthor']);
        $category = Category::query()->create(['name' => 'Writing', 'slug' => 'writing']);
        $alias = $author->primaryAlias();

        $current = Post::factory()->for($author)->for($category)->create([
            'author_alias_id' => $alias->id,
            'title' => 'Current Story',
            'slug' => 'current-story',
            'body' => '<p>Current body text.</p>',
        ]);

        $older = Post::factory()->for($author)->for($category)->create([
            'author_alias_id' => $alias->id,
            'title' => 'Older Story',
            'slug' => 'older-story',
            'subtitle' => 'An older take on the topic',
            'published_at' => now()->subDays(2),
        ]);

        $this->get(route('posts.show', [$alias, $current->slug]))
            ->assertOk()
            ->assertSee('More from', false)
            ->assertSee($alias->name, false)
            ->assertSee('Older Story', false)
            ->assertSee('An older take on the topic', false)
            ->assertSee(route('posts.show', [$alias, $older->slug]), false);
    }

    public function test_show_url_tolerates_trailing_dot_in_slug(): void
    {
        $author = User::factory()->create(['username' => 'demoauthor']);
        $category = Category::query()->create(['name' => 'Tech', 'slug' => 'tech']);
        $post = Post::factory()->for($author)->for($category)->create([
            'title' => 'Visible Story',
            'slug' => 'visible-story',
        ]);

        $this->get(route('posts.show', [$author->username, $post->slug.'.']))
            ->assertOk()
            ->assertSee('Visible Story');
    }
}
