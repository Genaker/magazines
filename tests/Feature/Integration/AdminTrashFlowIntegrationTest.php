<?php

namespace Tests\Feature\Integration;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use App\Support\Features;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Admin trash: publish → soft-delete → hidden → restore → public again.
 */
class AdminTrashFlowIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Features::seedDefaults();
    }

    public function test_admin_trash_restore_returns_post_to_public(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $author = User::factory()->create(['username' => 'trashauthor']);
        $category = Category::query()->create(['name' => 'Culture', 'slug' => 'culture']);

        $this->actingAs($author)->post(route('posts.store'), [
            'title' => 'Trash Integration Story',
            'body' => '<p>Will be trashed and restored.</p>',
            'category_id' => $category->id,
            'status' => 'published',
        ])->assertRedirect();

        $post = Post::query()->where('title', 'Trash Integration Story')->firstOrFail();
        $showUrl = route('posts.show', [$author->primaryAlias(), $post->slug]);

        $this->post(route('logout'));
        $this->get($showUrl)->assertOk();

        $this->actingAs($admin)->from(route('admin.posts.index'))
            ->delete(route('admin.posts.destroy', $post))
            ->assertRedirect(route('admin.posts.index'));

        $this->assertSoftDeleted('posts', ['id' => $post->id]);
        $this->get($showUrl)->assertNotFound();

        $this->actingAs($admin)->from(route('admin.trash.index'))
            ->post(route('admin.trash.posts.restore', $post->id))
            ->assertRedirect(route('admin.trash.index'));

        $this->assertNull($post->fresh()->deleted_at);

        $this->get($showUrl)
            ->assertOk()
            ->assertSee('Trash Integration Story');
    }
}
