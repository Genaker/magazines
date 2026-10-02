<?php

namespace Tests\Unit;

use App\Models\Post;
use App\Models\User;
use App\Notifications\UserActivity;
use App\Services\ActivityNotifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ActivityNotifierTest extends TestCase
{
    use RefreshDatabase;

    public function test_does_not_notify_actor_about_own_action(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $post = Post::factory()->draft()->for($user)->create();

        ActivityNotifier::notify($user, $user, 'like', $post);

        Notification::assertNothingSent();
    }

    public function test_does_not_notify_when_recipient_blocked_actor(): void
    {
        Notification::fake();

        $author = User::factory()->create();
        $blocked = User::factory()->create();
        $author->blockedUsers()->attach($blocked->id);
        $post = Post::factory()->draft()->for($author)->create();

        ActivityNotifier::notify($author, $blocked, 'comment', $post);

        Notification::assertNothingSent();
    }

    public function test_notifies_recipient_for_valid_activity(): void
    {
        Notification::fake();

        $author = User::factory()->create();
        $reader = User::factory()->create();
        $post = Post::factory()->draft()->for($author)->create();

        ActivityNotifier::notify($author, $reader, 'like', $post);

        Notification::assertSentTo($author, UserActivity::class, fn (UserActivity $n) => $n->kind === 'like');
    }
}
