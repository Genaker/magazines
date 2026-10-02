<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTrashTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_restore_trashed_post(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $post = Post::factory()->create();
        $post->delete();

        $this->actingAs($admin)
            ->post(route('admin.trash.posts.restore', $post->id))
            ->assertRedirect();

        $this->assertNull($post->fresh()->deleted_at);
    }

    public function test_super_admin_can_restore_trashed_user(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $user = User::factory()->create();
        $user->delete();

        $this->actingAs($admin)
            ->post(route('admin.trash.users.restore', $user->id))
            ->assertRedirect();

        $this->assertNull($user->fresh()->deleted_at);
    }
}
