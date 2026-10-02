<?php

namespace Tests\Feature\Integration;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use App\Support\Features;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * User block: follow → block → post forbidden and follow removed.
 */
class UserBlockFlowIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Features::seedDefaults();
    }

    public function test_block_removes_follow_and_hides_posts_from_viewer(): void
    {
        $author = User::factory()->create(['username' => 'blockhost']);
        $viewer = User::factory()->create();
        $category = Category::query()->create(['name' => 'Culture', 'slug' => 'culture']);

        $this->actingAs($viewer)->postJson(route('users.follow', $author))
            ->assertOk()
            ->assertJson(['following' => true]);

        $this->actingAs($author)->post(route('posts.store'), [
            'title' => 'Block Integration Story',
            'body' => '<p>Will be hidden after block.</p>',
            'category_id' => $category->id,
            'status' => 'published',
        ])->assertRedirect();

        $post = Post::query()->where('title', 'Block Integration Story')->firstOrFail();

        $this->actingAs($viewer)->get(route('posts.show', [$author->primaryAlias(), $post->slug]))
            ->assertOk();

        $this->actingAs($author)->post(route('users.block', $viewer))
            ->assertRedirect();

        $viewer->refresh();
        $this->assertFalse($viewer->following()->where('following_id', $author->id)->exists());

        $this->actingAs($viewer->fresh())->get(route('posts.show', [$author->primaryAlias(), $post->slug]))
            ->assertForbidden();
    }
}
