<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryDeleteReassignTest extends TestCase
{
    use RefreshDatabase;

    public function test_deleting_category_reassigns_posts_instead_of_deleting_them(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $source = Category::query()->create(['name' => 'Old', 'slug' => 'old']);
        $target = Category::query()->create(['name' => 'New', 'slug' => 'new']);
        $post = Post::factory()->create(['category_id' => $source->id]);

        $this->actingAs($admin)
            ->delete(route('admin.categories.destroy', $source), [
                'fallback_category_id' => $target->id,
            ])
            ->assertRedirect(route('admin.categories.index'));

        $this->assertDatabaseMissing('categories', ['id' => $source->id]);
        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'category_id' => $target->id,
        ]);
    }
}
