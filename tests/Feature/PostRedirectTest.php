<?php

namespace Tests\Feature;

use App\Enums\PostStatus;
use App\Enums\UserRole;
use App\Models\AuthorAlias;
use App\Models\Category;
use App\Models\Post;
use App\Models\PostRedirect;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_old_post_url_redirects_after_title_rename(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $alias = $user->authorAliases()->first();
        $category = Category::query()->create(['name' => 'Tech', 'slug' => 'tech']);

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'author_alias_id' => $alias->id,
            'category_id' => $category->id,
            'title' => 'Original Title',
            'slug' => 'original-title',
            'status' => PostStatus::Published,
            'published_at' => now(),
        ]);

        $this->actingAs($user)->put(route('posts.update', $post), [
            'title' => 'Renamed Title',
            'body' => $post->body,
            'category_id' => $category->id,
            'status' => 'published',
        ])->assertRedirect();

        $post->refresh();
        $this->assertSame('renamed-title', $post->slug);

        $this->get(route('posts.show', [$alias, 'original-title']))
            ->assertRedirect(route('posts.show', [$alias, 'renamed-title']));

        $this->assertDatabaseHas('post_redirects', [
            'author_username' => $alias->username,
            'slug' => 'original-title',
            'post_id' => $post->id,
        ]);
    }

    public function test_post_redirect_is_permanent(): void
    {
        $user = User::factory()->create();
        $alias = $user->authorAliases()->first();
        $category = Category::query()->create(['name' => 'Tech', 'slug' => 'tech']);
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'author_alias_id' => $alias->id,
            'category_id' => $category->id,
            'slug' => 'new-slug',
            'status' => PostStatus::Published,
            'published_at' => now(),
        ]);

        PostRedirect::register($alias->username, 'legacy-slug', $post);

        $this->get(route('posts.show', [$alias, 'legacy-slug']))
            ->assertStatus(301);
    }

    public function test_old_post_url_redirects_after_author_alias_change(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $primary = $user->authorAliases()->first();
        $secondary = AuthorAlias::query()->create([
            'user_id' => $user->id,
            'username' => 'secondpen',
            'name' => 'Second Pen',
            'is_primary' => false,
        ]);
        $category = Category::query()->create(['name' => 'Tech', 'slug' => 'tech']);

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'author_alias_id' => $primary->id,
            'category_id' => $category->id,
            'title' => 'Alias switch story',
            'slug' => 'alias-switch-story',
            'status' => PostStatus::Published,
            'published_at' => now(),
        ]);

        $this->actingAs($user)->put(route('posts.update', $post), [
            'title' => $post->title,
            'body' => $post->body,
            'category_id' => $category->id,
            'author_alias_id' => $secondary->id,
            'status' => 'published',
        ])->assertRedirect();

        $this->get(route('posts.show', [$primary, 'alias-switch-story']))
            ->assertRedirect(route('posts.show', [$secondary, 'alias-switch-story']));
    }

    public function test_changing_post_category_does_not_require_post_url_redirect(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $alias = $user->authorAliases()->first();
        $oldCategory = Category::query()->create(['name' => 'Old', 'slug' => 'old']);
        $newCategory = Category::query()->create(['name' => 'New', 'slug' => 'new']);

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'author_alias_id' => $alias->id,
            'category_id' => $oldCategory->id,
            'slug' => 'same-slug',
            'status' => PostStatus::Published,
            'published_at' => now(),
        ]);

        $this->actingAs($user)->put(route('posts.update', $post), [
            'title' => $post->title,
            'body' => $post->body,
            'category_id' => $newCategory->id,
            'status' => 'published',
        ])->assertRedirect();

        $this->assertDatabaseMissing('post_redirects', [
            'author_username' => $alias->username,
            'slug' => 'same-slug',
        ]);

        $this->get(route('posts.show', [$alias, 'same-slug']))->assertOk();
    }

    public function test_admin_post_title_update_redirects_old_url(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $user = User::factory()->create();
        $alias = $user->authorAliases()->first();
        $category = Category::query()->create(['name' => 'Tech', 'slug' => 'tech']);

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'author_alias_id' => $alias->id,
            'category_id' => $category->id,
            'title' => 'Admin rename me',
            'slug' => 'admin-rename-me',
            'status' => PostStatus::Published,
            'published_at' => now(),
        ]);

        $this->actingAs($admin)->put(route('admin.posts.update', $post), [
            'title' => 'Admin renamed title',
            'status' => 'published',
            'category_id' => $category->id,
        ])->assertRedirect(route('admin.posts.index'));

        $this->get(route('posts.show', [$alias, 'admin-rename-me']))
            ->assertRedirect(route('posts.show', [$alias, 'admin-renamed-title']));
    }
}
