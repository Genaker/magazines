<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostPinTest extends TestCase
{
    use RefreshDatabase;

    public function test_pinned_post_stays_on_profile_but_newest_story_appears_first(): void
    {
        $author = User::factory()->create(['username' => 'pinner']);
        $olderPinned = Post::factory()->for($author)->create([
            'slug' => 'older-story',
            'published_at' => now()->subDays(10),
        ]);
        $newer = Post::factory()->for($author)->create([
            'slug' => 'newer-story',
            'published_at' => now()->subDay(),
        ]);

        $this->actingAs($author)
            ->post(route('posts.pin', $olderPinned))
            ->assertRedirect();

        $olderPinned->refresh();
        $this->assertNotNull($olderPinned->pinned_at);

        $response = $this->get(route('authors.show', $author));
        $response->assertOk();
        $response->assertSee(__('app.pinned'), false);

        $olderPos = strpos($response->getContent(), 'older-story');
        $newerPos = strpos($response->getContent(), 'newer-story');
        $this->assertNotFalse($olderPos);
        $this->assertNotFalse($newerPos);
        $this->assertLessThan($olderPos, $newerPos);
    }

    public function test_author_can_unpin_post(): void
    {
        $author = User::factory()->create();
        $post = Post::factory()->for($author)->create([
            'pinned_at' => now(),
        ]);

        $this->actingAs($author)
            ->post(route('posts.pin', $post))
            ->assertRedirect();

        $this->assertNull($post->fresh()->pinned_at);
    }

    public function test_draft_post_cannot_be_pinned(): void
    {
        $author = User::factory()->create();
        $post = Post::factory()->for($author)->create([
            'status' => 'draft',
            'published_at' => null,
        ]);

        $this->actingAs($author)
            ->post(route('posts.pin', $post))
            ->assertForbidden();
    }

    public function test_other_user_cannot_pin_post(): void
    {
        $author = User::factory()->create();
        $other = User::factory()->create();
        $post = Post::factory()->for($author)->create();

        $this->actingAs($other)
            ->post(route('posts.pin', $post))
            ->assertForbidden();
    }
}
