<?php

namespace Tests\Feature\Integration;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use App\Notifications\UserActivity;
use App\Support\Features;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Social chain: follow author → like post → comment → author sees notifications.
 */
class SocialFlowIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Features::seedDefaults();
    }

    public function test_reader_interactions_notify_author(): void
    {
        Notification::fake();

        $author = User::factory()->create();
        $reader = User::factory()->create();
        $post = Post::factory()->for($author)->create([
            'title' => 'Social Integration Post',
            'body' => '<p>Discuss me.</p>',
        ]);

        $this->actingAs($reader)->postJson(route('users.follow', $author))
            ->assertOk()
            ->assertJson(['following' => true]);

        $this->actingAs($reader)->postJson(route('posts.like', $post))
            ->assertOk()
            ->assertJson(['liked' => true]);

        $this->actingAs($reader)->post(route('posts.comments.store', $post), [
            'body' => 'Great integration test comment.',
        ])->assertRedirect();

        Notification::assertSentTo($author, UserActivity::class, fn (UserActivity $n) => $n->kind === 'follow');
        Notification::assertSentTo($author, UserActivity::class, fn (UserActivity $n) => $n->kind === 'like');
        Notification::assertSentTo($author, UserActivity::class, fn (UserActivity $n) => $n->kind === 'comment');

        $this->actingAs($author)
            ->get(route('notifications.index'))
            ->assertOk();
    }
}
