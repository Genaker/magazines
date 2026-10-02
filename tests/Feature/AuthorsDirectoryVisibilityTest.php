<?php

namespace Tests\Feature;

use App\Enums\PostStatus;
use App\Enums\UserRole;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorsDirectoryVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_bootstrap_admin_username_is_hidden_from_authors_index_without_posts(): void
    {
        User::factory()->create([
            'role' => UserRole::SuperAdmin,
            'username' => 'admin',
            'name' => 'Admin',
        ]);

        $this->get(route('authors.index'))
            ->assertOk()
            ->assertDontSee('>Admin</h2>', false);
    }

    public function test_bootstrap_super_admin_is_hidden_from_authors_index_without_posts(): void
    {
        User::factory()->create([
            'role' => UserRole::SuperAdmin,
            'username' => 'superadmin',
            'name' => 'Super Admin',
        ]);

        $this->get(route('authors.index'))
            ->assertOk()
            ->assertDontSee('Super Admin');
    }

    public function test_super_admin_with_published_story_appears_on_authors_index(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::SuperAdmin,
            'username' => 'writingadmin',
            'name' => 'Writing Admin',
        ]);
        $alias = $user->primaryAlias();

        Post::factory()->create([
            'user_id' => $user->id,
            'author_alias_id' => $alias->id,
            'status' => PostStatus::Published,
            'published_at' => now(),
        ]);

        $this->get(route('authors.index'))
            ->assertOk()
            ->assertSee('Writing Admin');
    }

    public function test_regular_user_without_posts_is_listed_on_authors_index(): void
    {
        User::factory()->create([
            'username' => 'newwriter',
            'name' => 'New Writer',
        ]);

        $this->get(route('authors.index'))
            ->assertOk()
            ->assertSee('New Writer');
    }

    public function test_bootstrap_username_is_hidden_from_search_without_posts(): void
    {
        User::factory()->create([
            'role' => UserRole::SuperAdmin,
            'username' => 'administrator',
            'name' => 'Site Administrator',
        ]);

        $this->get(route('search', ['q' => 'Administrator']))
            ->assertOk()
            ->assertDontSee('Site Administrator');
    }

    public function test_public_alias_is_findable_in_search(): void
    {
        User::factory()->create([
            'username' => 'searchablepersona',
            'name' => 'Searchable Persona',
        ]);

        $this->get(route('search', ['q' => 'searchablepersona']))
            ->assertOk()
            ->assertSee('Searchable Persona');
    }

    public function test_admin_update_changes_role_and_ban_state(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $user = User::factory()->create(['role' => UserRole::User]);

        $this->actingAs($admin)
            ->put(route('admin.users.update', $user), [
                'role' => 'admin',
                'is_banned' => '1',
            ])
            ->assertRedirect(route('admin.users.index'));

        $user->refresh();
        $this->assertSame(UserRole::Admin, $user->role);
        $this->assertTrue($user->is_banned);
    }
}
