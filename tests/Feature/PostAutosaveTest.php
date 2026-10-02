<?php

namespace Tests\Feature;

use App\Enums\PostStatus;
use App\Models\Category;
use App\Models\Post;
use App\Models\PostAutosaveSnapshot;
use App\Models\User;
use App\Support\EntityCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostAutosaveTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        EntityCache::flushStore();
    }

    public function test_autosave_creates_draft_post_shell_and_snapshot(): void
    {
        $user = User::factory()->create();
        $category = Category::query()->create(['name' => 'Tech', 'slug' => 'tech']);

        $response = $this->actingAs($user)->postJson(route('posts.autosave'), [
            'title' => 'Autosaved title',
            'body' => '<p>Draft body</p>',
            'category_id' => $category->id,
        ]);

        $response->assertOk()->assertJsonStructure(['post_id', 'share_url', 'edit_url']);

        $post = Post::query()->where('user_id', $user->id)->firstOrFail();

        $this->assertSame('Autosaved title', $post->title);
        $this->assertSame('', $post->body);
        $this->assertSame(PostStatus::Draft, $post->status);
        $this->assertDatabaseHas('post_autosave_snapshots', [
            'post_id' => $post->id,
            'user_id' => $user->id,
        ]);

        $snapshot = PostAutosaveSnapshot::query()->where('post_id', $post->id)->firstOrFail();
        $this->assertSame('<p>Draft body</p>', $snapshot->payload['body']);
    }

    public function test_autosave_on_published_post_does_not_change_published_content(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->for($user)->create([
            'title' => 'Published title',
            'body' => '<p>Published body</p>',
            'status' => PostStatus::Published,
        ]);

        $this->actingAs($user)->postJson(route('posts.autosave'), [
            'post_id' => $post->id,
            'title' => 'Draft title',
            'body' => '<p>Draft body</p>',
        ])->assertOk();

        $post->refresh();

        $this->assertSame('Published title', $post->title);
        $this->assertSame('<p>Published body</p>', $post->body);
        $this->assertSame(1, $post->autosave_revision);
    }

    public function test_edit_page_shows_autosave_warning_for_published_post(): void
    {
        $user = User::factory()->create(['username' => 'autosaveauthor']);
        $category = Category::query()->create(['name' => 'Essays', 'slug' => 'essays']);
        $post = Post::factory()->for($user)->for($category)->create([
            'slug' => 'published-story',
            'title' => 'Published title',
            'body' => '<p>Published body</p>',
            'status' => PostStatus::Published,
        ]);

        $this->actingAs($user)->postJson(route('posts.autosave'), [
            'post_id' => $post->id,
            'title' => 'Draft title',
            'body' => '<p>Draft body</p>',
            'category_id' => $category->id,
        ])->assertOk();

        $this->get(route('posts.edit', $post))
            ->assertOk()
            ->assertSee(__('app.autosave_pending_published_draft'), false)
            ->assertSee('Draft title', false)
            ->assertSee('Draft body', false);
    }

    public function test_autosave_does_not_flush_post_show_cache(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->for($user)->create([
            'title' => 'Cached title',
            'body' => '<p>Cached body</p>',
            'status' => PostStatus::Published,
        ]);

        Post::cachedForShow($post->author_alias_id, $post->slug);

        $this->actingAs($user)->postJson(route('posts.autosave'), [
            'post_id' => $post->id,
            'title' => 'Draft title',
            'body' => '<p>Draft body</p>',
        ])->assertOk();

        $this->assertSame('Cached title', Post::cachedForShow($post->author_alias_id, $post->slug)->title);
    }
}
