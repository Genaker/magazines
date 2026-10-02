<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_draft_post_by_link(): void
    {
        $author = User::factory()->create();
        $post = Post::factory()->for($author)->draft()->create(['title' => 'Secret draft']);

        $this->get(route('posts.show', [$author->username, $post->slug]))
            ->assertForbidden();
    }

    public function test_author_can_view_own_draft_by_link(): void
    {
        $author = User::factory()->create();
        $post = Post::factory()->for($author)->draft()->create(['title' => 'Secret draft']);

        $this->actingAs($author)
            ->get(route('posts.show', [$author->username, $post->slug]))
            ->assertOk()
            ->assertSee('Secret draft');
    }

    public function test_guest_can_view_unlisted_post_by_link(): void
    {
        $author = User::factory()->create();
        $post = Post::factory()->for($author)->unlisted()->create(['title' => 'Hidden gem']);

        $this->get(route('posts.show', [$author->username, $post->slug]))
            ->assertOk()
            ->assertSee('Hidden gem');
    }
}
