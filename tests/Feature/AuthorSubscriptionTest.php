<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_subscribe_and_unsubscribe_to_author(): void
    {
        $author = User::factory()->create();
        $subscriber = User::factory()->create();

        $this->actingAs($subscriber)
            ->postJson(route('users.subscribe', $author))
            ->assertOk()
            ->assertJson(['subscribed' => true]);

        $this->assertTrue($subscriber->storySubscriptions()->where('author_id', $author->id)->exists());

        $this->actingAs($subscriber)
            ->postJson(route('users.subscribe', $author))
            ->assertOk()
            ->assertJson(['subscribed' => false]);
    }

    public function test_user_cannot_subscribe_to_self(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('users.subscribe', $user))
            ->assertStatus(422);
    }

    public function test_author_profile_shows_subscribe_button(): void
    {
        $author = User::factory()->create(['username' => 'subscribable']);
        $viewer = User::factory()->create();

        $this->actingAs($viewer)
            ->get(route('authors.show', $author))
            ->assertOk()
            ->assertSee('Subscribe');
    }
}
