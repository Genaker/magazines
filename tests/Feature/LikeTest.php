<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LikeTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_toggle_like_by_ip(): void
    {
        $post = $this->createPublishedPost();

        $this->postJson(route('posts.like', $post))->assertOk()->assertJson(['liked' => true]);
        $this->postJson(route('posts.like', $post))->assertOk()->assertJson(['liked' => false]);
    }

    public function test_authenticated_user_can_toggle_like(): void
    {
        $user = User::factory()->create();
        $post = $this->createPublishedPost();

        $this->actingAs($user)
            ->postJson(route('posts.like', $post))
            ->assertOk()
            ->assertJson(['liked' => true]);

        $this->actingAs($user)
            ->postJson(route('posts.like', $post))
            ->assertOk()
            ->assertJson(['liked' => false]);
    }

    private function createPublishedPost(): Post
    {
        return Post::factory()->create();
    }
}
