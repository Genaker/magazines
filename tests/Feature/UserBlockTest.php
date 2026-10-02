<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserBlockTest extends TestCase
{
    use RefreshDatabase;

    public function test_block_from_author_profile_prevents_viewing_posts(): void
    {
        $author = User::factory()->create(['username' => 'blocker']);
        $viewer = User::factory()->create();
        $post = Post::factory()->for($author)->create(['slug' => 'blocked-view']);

        $this->actingAs($author)
            ->post(route('users.block', $viewer))
            ->assertRedirect();

        $this->actingAs($viewer)
            ->get(route('posts.show', [$author, $post->slug]))
            ->assertForbidden();
    }

    public function test_block_removes_follow_and_subscription_relationships(): void
    {
        $author = User::factory()->create();
        $viewer = User::factory()->create();

        $viewer->following()->attach($author->id);
        $viewer->storySubscriptions()->attach($author->id);

        $this->actingAs($author)
            ->post(route('users.block', $viewer))
            ->assertRedirect();

        $viewer->refresh();
        $this->assertFalse($viewer->following()->where('following_id', $author->id)->exists());
        $this->assertFalse($viewer->storySubscriptions()->where('author_id', $author->id)->exists());
    }

    public function test_author_profile_shows_block_button(): void
    {
        $author = User::factory()->create(['username' => 'profilehost']);
        $viewer = User::factory()->create();

        $this->actingAs($viewer)
            ->get(route('authors.show', $author))
            ->assertOk()
            ->assertSee('Block');
    }
}
