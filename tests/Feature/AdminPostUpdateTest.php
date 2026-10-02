<?php

namespace Tests\Feature;

use App\Enums\PostStatus;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPostUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_change_post_status_and_category(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $categoryA = Category::query()->create(['name' => 'Tech', 'slug' => 'tech']);
        $categoryB = Category::query()->create(['name' => 'Culture', 'slug' => 'culture']);
        $post = Post::factory()->create([
            'category_id' => $categoryA->id,
            'status' => PostStatus::Draft,
            'published_at' => null,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.posts.update', $post), [
                'title' => $post->title,
                'status' => 'published',
                'category_id' => $categoryB->id,
            ])
            ->assertRedirect(route('admin.posts.index'));

        $post->refresh();
        $this->assertSame(PostStatus::Published, $post->status);
        $this->assertSame($categoryB->id, $post->category_id);
        $this->assertNotNull($post->published_at);
    }
}
