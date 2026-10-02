<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SoftDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_soft_delete_user(): void
    {
        $admin = User::factory()->create(['role' => \App\Enums\UserRole::SuperAdmin]);
        $user = User::factory()->create();

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $user))
            ->assertRedirect(route('admin.users.index'));

        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }

    public function test_author_can_soft_delete_own_post(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->for($user)->create();

        $this->actingAs($user)
            ->delete(route('posts.destroy', $post))
            ->assertRedirect(route('posts.mine'));

        $this->assertSoftDeleted('posts', ['id' => $post->id]);
    }
}
