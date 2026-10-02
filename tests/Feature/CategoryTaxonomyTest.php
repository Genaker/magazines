<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Magazine;
use App\Models\Post;
use App\Models\User;
use App\Services\CategoryTaxonomyService;
use App\Support\Features;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTaxonomyTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_merge_moves_posts_children_and_registers_redirect(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $from = Category::query()->create(['name' => 'Old Section', 'slug' => 'old-section']);
        $into = Category::query()->create(['name' => 'Main Section', 'slug' => 'main-section']);
        $child = Category::query()->create([
            'name' => 'Child',
            'slug' => 'child',
            'parent_id' => $from->id,
        ]);
        $post = Post::factory()->create(['category_id' => $from->id]);

        $this->actingAs($admin)
            ->post(route('admin.categories.merge', $from), [
                'merge_into_id' => $into->id,
            ])
            ->assertRedirect(route('admin.categories.index'));

        $this->assertDatabaseMissing('categories', ['id' => $from->id]);
        $this->assertDatabaseHas('categories', ['id' => $child->id, 'parent_id' => $into->id]);
        $this->assertDatabaseHas('posts', ['id' => $post->id, 'category_id' => $into->id]);
        $this->assertDatabaseHas('category_redirects', [
            'slug' => 'old-section',
            'category_id' => $into->id,
        ]);
    }

    public function test_admin_delete_with_fallback_reparents_children_and_registers_redirect(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $source = Category::query()->create(['name' => 'Retiring', 'slug' => 'retiring']);
        $fallback = Category::query()->create(['name' => 'Fallback', 'slug' => 'fallback']);
        $child = Category::query()->create([
            'name' => 'Sub',
            'slug' => 'sub',
            'parent_id' => $source->id,
        ]);
        $post = Post::factory()->create(['category_id' => $source->id]);

        $this->actingAs($admin)
            ->delete(route('admin.categories.destroy', $source), [
                'fallback_category_id' => $fallback->id,
            ])
            ->assertRedirect(route('admin.categories.index'));

        $this->assertDatabaseMissing('categories', ['id' => $source->id]);
        $this->assertDatabaseHas('categories', ['id' => $child->id, 'parent_id' => $fallback->id]);
        $this->assertDatabaseHas('posts', ['id' => $post->id, 'category_id' => $fallback->id]);
        $this->assertDatabaseHas('category_redirects', [
            'slug' => 'retiring',
            'category_id' => $fallback->id,
        ]);
    }

    public function test_delete_without_posts_promotes_children_to_deleted_parent(): void
    {
        $service = app(CategoryTaxonomyService::class);
        $parent = Category::query()->create(['name' => 'Culture', 'slug' => 'culture']);
        $middle = Category::query()->create([
            'name' => 'Film',
            'slug' => 'film',
            'parent_id' => $parent->id,
        ]);
        $child = Category::query()->create([
            'name' => 'Documentary',
            'slug' => 'documentary',
            'parent_id' => $middle->id,
        ]);

        $service->delete($middle);

        $this->assertDatabaseMissing('categories', ['id' => $middle->id]);
        $this->assertDatabaseHas('categories', [
            'id' => $child->id,
            'parent_id' => $parent->id,
        ]);
    }

    public function test_approve_category_request_with_parent(): void
    {
        Features::seedDefaults();

        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $parent = Category::query()->create(['name' => 'Design', 'slug' => 'design']);
        $writer = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($writer)->post(route('category-requests.store'), [
            'name' => 'Typography',
            'reason' => 'For type-focused posts.',
        ])->assertRedirect();

        $request = $writer->categoryRequests()->firstOrFail();

        $this->actingAs($admin)->post(route('admin.category-requests.approve', $request), [
            'parent_id' => $parent->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('categories', [
            'name' => 'Typography',
            'parent_id' => $parent->id,
            'magazine_id' => null,
        ]);
    }

    public function test_approve_category_request_with_magazine(): void
    {
        Features::seedDefaults();

        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $owner = User::factory()->create();
        $magazine = Magazine::query()->create([
            'owner_id' => $owner->id,
            'name' => 'Beat Mag',
            'slug' => 'beat-mag',
        ]);
        $writer = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($writer)->post(route('category-requests.store'), [
            'name' => 'Profiles',
            'reason' => 'For magazine profile stories.',
        ])->assertRedirect();

        $request = $writer->categoryRequests()->firstOrFail();

        $this->actingAs($admin)->post(route('admin.category-requests.approve', $request), [
            'magazine_id' => $magazine->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('categories', [
            'name' => 'Profiles',
            'magazine_id' => $magazine->id,
            'parent_id' => null,
        ]);
    }

    public function test_admin_merge_rejects_cross_magazine_scope(): void
    {
        Features::seedDefaults();

        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $owner = User::factory()->create();
        $magazine = Magazine::query()->create([
            'owner_id' => $owner->id,
            'name' => 'Beat Mag',
            'slug' => 'beat-mag',
        ]);
        $site = Category::query()->create(['name' => 'Site Section', 'slug' => 'site-section']);
        $beat = Category::query()->create([
            'magazine_id' => $magazine->id,
            'name' => 'Beat',
            'slug' => 'beat',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.categories.merge', $beat), [
                'merge_into_id' => $site->id,
            ])
            ->assertSessionHasErrors('merge_into_id');

        $this->assertDatabaseHas('categories', ['id' => $beat->id]);
    }

    public function test_admin_merge_rejects_merge_into_subcategory(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $parent = Category::query()->create(['name' => 'Parent', 'slug' => 'parent']);
        $child = Category::query()->create([
            'name' => 'Child',
            'slug' => 'child',
            'parent_id' => $parent->id,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.categories.merge', $parent), [
                'merge_into_id' => $child->id,
            ])
            ->assertSessionHasErrors('merge_into_id');

        $this->assertDatabaseHas('categories', ['id' => $parent->id]);
    }

    public function test_admin_http_delete_without_posts_promotes_children_to_root(): void
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
