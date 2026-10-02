<?php

namespace Tests\Unit;

use App\Models\Magazine;
use App\Models\Post;
use App\Models\User;
use App\Notifications\UserActivity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserActivityNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_to_array_includes_post_and_magazine_fields(): void
    {
        $actor = User::factory()->create([
            'name' => 'Actor',
            'username' => 'actor1',
        ]);
        $author = User::factory()->create(['username' => 'postauthor']);
        $post = Post::factory()->for($author)->create([
            'title' => 'Test Story',
            'slug' => 'test-story',
        ]);
        $magazine = Magazine::query()->create([
            'owner_id' => $author->id,
            'name' => 'Tech Mag',
            'slug' => 'tech-mag',
        ]);

        $payload = (new UserActivity('magazine_join_request', $actor, $post, null, $magazine))
            ->toArray(User::factory()->create());

        $this->assertSame('magazine_join_request', $payload['kind']);
        $this->assertSame('Actor', $payload['actor_name']);
        $this->assertSame('actor1', $payload['actor_username']);
        $this->assertSame('Test Story', $payload['post_title']);
        $this->assertSame('test-story', $payload['post_slug']);
        $this->assertSame('postauthor', $payload['post_author_username']);
        $this->assertSame('Tech Mag', $payload['magazine_name']);
        $this->assertSame('tech-mag', $payload['magazine_slug']);
    }
}
