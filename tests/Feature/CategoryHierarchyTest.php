<?php

namespace Tests\Feature;

use App\Enums\PostStatus;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryHierarchyTest extends TestCase
{
    use RefreshDatabase;

    public function test_parent_category_lists_posts_from_subcategories(): void
    {
        $parent = Category::query()->create(['name' => 'Technology', 'slug' => 'technology']);
        $child = Category::query()->create([
            'name' => 'Web Development',
            'slug' => 'web-development',
            'parent_id' => $parent->id,
        ]);

        Post::factory()->create([
            'category_id' => $child->id,
            'title' => 'Nested category post',
            'status' => PostStatus::Published,
            'published_at' => now(),
        ]);

        $response = $this->get(route('categories.show', $parent));

        $response->assertOk();
        $response->assertSee('Nested category post');
        $response->assertSee('Web Development');
    }

    public function test_subcategory_page_shows_parent_breadcrumb(): void
    {
        $parent = Category::query()->create(['name' => 'Writing', 'slug' => 'writing']);
        $child = Category::query()->create([
            'name' => 'Personal Essays',
            'slug' => 'personal-essays',
            'parent_id' => $parent->id,
        ]);

        $response = $this->get(route('categories.show', $child));

        $response->assertOk();
        $response->assertSee('Writing');
        $response->assertSee('Personal Essays');
    }

    public function test_admin_can_create_subcategory(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $parent = Category::query()->create(['name' => 'Science', 'slug' => 'science']);

        $this->actingAs($admin)
            ->post(route('admin.categories.store'), [
                'name' => 'Physics',
                'parent_id' => $parent->id,
            ])
            ->assertRedirect(route('admin.categories.index'));

        $this->assertDatabaseHas('categories', [
            'name' => 'Physics',
            'parent_id' => $parent->id,
        ]);
    }

    public function test_admin_cannot_nest_category_under_its_descendant(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $parent = Category::query()->create(['name' => 'Technology', 'slug' => 'technology']);
        $child = Category::query()->create([
            'name' => 'Mobile',
            'slug' => 'mobile',
            'parent_id' => $parent->id,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.categories.update', $parent), [
                'name' => 'Technology',
                'parent_id' => $child->id,
            ])
            ->assertSessionHasErrors('parent_id');
    }

    public function test_deleting_parent_promotes_children_to_root(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $parent = Category::query()->create(['name' => 'Culture', 'slug' => 'culture']);
        $child = Category::query()->create([
            'name' => 'Film',
            'slug' => 'film',
            'parent_id' => $parent->id,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.categories.destroy', $parent))
            ->assertRedirect(route('admin.categories.index'));

        $this->assertDatabaseMissing('categories', ['id' => $parent->id]);
        $this->assertDatabaseHas('categories', [
            'id' => $child->id,
            'parent_id' => null,
        ]);
    }
}
