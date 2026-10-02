<?php

namespace Tests\Feature\Integration;

use App\Enums\PostStatus;
use App\Models\AuthorAlias;
use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use App\Support\Features;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Authors directory and pen-name publishing flow.
 */
class AuthorsDirectoryFlowIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Features::seedDefaults();
    }

    public function test_bootstrap_admin_alias_appears_after_publishing_story(): void
    {
        $category = Category::query()->create(['name' => 'News', 'slug' => 'news']);
        $user = User::factory()->create([
            'role' => \App\Enums\UserRole::SuperAdmin,
            'username' => 'admin',
            'name' => 'Site Admin',
        ]);
        $alias = $user->primaryAlias();

        $this->get(route('authors.index'))
            ->assertOk()
            ->assertDontSee('Site Admin');

        Post::factory()->create([
            'user_id' => $user->id,
            'author_alias_id' => $alias->id,
            'category_id' => $category->id,
            'title' => 'Admin First Story',
            'slug' => 'admin-first-story',
            'status' => PostStatus::Published,
            'published_at' => now(),
        ]);

        $this->get(route('authors.index'))
            ->assertOk()
            ->assertSee('Site Admin');
    }

    public function test_pen_name_alias_lists_on_authors_index_after_publish(): void
    {
        $user = User::factory()->create(['username' => 'mainaccount']);
        $category = Category::query()->create(['name' => 'Culture', 'slug' => 'culture']);

        $this->actingAs($user)->post(route('profile.aliases.store'), [
            'name' => 'Directory Pen Name',
            'username' => 'directorypen',
            'bio' => 'Listed after publishing.',
        ])->assertRedirect(route('profile.aliases'));

        $alias = AuthorAlias::query()->where('username', 'directorypen')->firstOrFail();

        $this->actingAs($user)->post(route('posts.store'), [
            'title' => 'Directory Pen Story',
            'body' => '<p>Should list the pen name.</p>',
            'category_id' => $category->id,
            'status' => 'published',
        ])->assertRedirect();

        $this->post(route('logout'));

        $this->get(route('authors.index'))
            ->assertOk()
            ->assertSee('Directory Pen Name')
            ->assertSee(route('authors.show', 'directorypen'), false);
    }
}
