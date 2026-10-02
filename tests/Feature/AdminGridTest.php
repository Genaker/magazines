<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminGridTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_users_index_supports_search(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        User::factory()->create(['name' => 'Alice Writer', 'email' => 'alice@example.com']);
        User::factory()->create(['name' => 'Bob Reader', 'email' => 'bob@example.com']);

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['q' => 'Alice']))
            ->assertOk()
            ->assertSee('Alice Writer')
            ->assertDontSee('Bob Reader');
    }

    public function test_admin_posts_index_supports_status_filter(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        Post::factory()->create(['title' => 'Draft story', 'status' => 'draft']);
        Post::factory()->create(['title' => 'Live story', 'status' => 'published']);

        $this->actingAs($admin)
            ->get(route('admin.posts.index', ['status' => 'draft']))
            ->assertOk()
            ->assertSee('Draft story')
            ->assertDontSee('Live story');
    }
}
