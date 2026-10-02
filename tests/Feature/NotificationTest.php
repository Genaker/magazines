<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Magazine;
use App\Models\Post;
use App\Models\User;
use App\Notifications\UserActivity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_follow_creates_notification_for_followed_user(): void
    {
        Notification::fake();

        $author = User::factory()->create();
        $follower = User::factory()->create();

        $this->actingAs($follower)
            ->postJson(route('users.follow', $author))
            ->assertOk()
            ->assertJson(['following' => true]);

        Notification::assertSentTo(
            $author,
            UserActivity::class,
            fn (UserActivity $notification) => $notification->kind === 'follow'
                && $notification->actor->is($follower),
        );
    }

    public function test_like_creates_notification_for_post_author(): void
    {
        Notification::fake();

        $author = User::factory()->create();
        $reader = User::factory()->create();
        $post = Post::factory()->for($author)->create();

        $this->actingAs($reader)
            ->postJson(route('posts.like', $post))
            ->assertOk()
            ->assertJson(['liked' => true]);

        Notification::assertSentTo(
            $author,
            UserActivity::class,
            fn (UserActivity $notification) => $notification->kind === 'like'
                && $notification->post?->is($post),
        );
    }

    public function test_self_actions_do_not_create_notifications(): void
    {
        Notification::fake();

        $author = User::factory()->create();
        $post = Post::factory()->for($author)->draft()->create();

        $this->actingAs($author)
            ->postJson(route('posts.like', $post))
            ->assertOk();

        $this->actingAs($author)
            ->postJson(route('users.follow', $author))
            ->assertOk()
            ->assertJson(['following' => true]);

        Notification::assertNothingSent();
    }

    public function test_comment_and_reply_create_notifications(): void
    {
        Notification::fake();

        $author = User::factory()->create();
        $commenter = User::factory()->create();
        $replier = User::factory()->create();
        $post = Post::factory()->for($author)->create();

        $this->actingAs($commenter)
            ->post(route('posts.comments.store', $post), ['body' => 'Nice post'])
            ->assertRedirect();

        Notification::assertSentTo(
            $author,
            UserActivity::class,
            fn (UserActivity $notification) => $notification->kind === 'comment',
        );

        $comment = Comment::query()->firstOrFail();

        $this->actingAs($replier)
            ->post(route('posts.comments.store', $post), [
                'body' => 'Thanks',
                'parent_id' => $comment->id,
            ])
            ->assertRedirect();

        Notification::assertSentTo(
            $commenter,
            UserActivity::class,
            fn (UserActivity $notification) => $notification->kind === 'comment_reply',
        );
    }

    public function test_subscribe_creates_notification(): void
    {
        $author = User::factory()->create();
        $subscriber = User::factory()->create();

        $this->actingAs($subscriber)
            ->postJson(route('users.subscribe', $author))
            ->assertOk()
            ->assertJson(['subscribed' => true]);

        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $author->id,
        ]);

        $notification = $author->notifications()->first();
        $this->assertSame('story_subscription', $notification->data['kind']);
        $this->assertSame($subscriber->id, $notification->data['actor_id']);
    }

    public function test_notifications_page_lists_activity(): void
    {
        $recipient = User::factory()->create();
        $actor = User::factory()->create(['name' => 'Jane Reader']);

        $recipient->notify(new UserActivity('follow', $actor));

        $this->actingAs($recipient)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Notifications')
            ->assertSee('Jane Reader')
            ->assertSee('followed you');
    }

    public function test_bookmark_creates_notification_for_post_author(): void
    {
        Notification::fake();

        $author = User::factory()->create();
        $reader = User::factory()->create();
        $post = Post::factory()->for($author)->create();

        $this->actingAs($reader)
            ->postJson(route('posts.bookmark', $post))
            ->assertOk()
            ->assertJson(['bookmarked' => true]);

        Notification::assertSentTo(
            $author,
            UserActivity::class,
            fn (UserActivity $notification) => $notification->kind === 'bookmark',
        );
    }

    public function test_mark_read_redirects_to_post_for_like_notification(): void
    {
        $recipient = User::factory()->create();
        $actor = User::factory()->create();
        $author = User::factory()->create(['username' => 'storyowner']);
        $post = Post::factory()->for($author)->create(['slug' => 'liked-story']);

        $recipient->notify(new UserActivity('like', $actor, $post));
        $notification = $recipient->notifications()->firstOrFail();

        $this->actingAs($recipient)
            ->post(route('notifications.read-one', $notification->id))
            ->assertRedirect(route('posts.show', [$author, $post->slug]));

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_blocked_users_do_not_receive_notifications(): void
    {
        Notification::fake();

        $author = User::factory()->create();
        $blocked = User::factory()->create();
        $author->blockedUsers()->attach($blocked->id);

        $this->actingAs($blocked)
            ->postJson(route('users.follow', $author))
            ->assertOk();

        Notification::assertNothingSent();
    }

    public function test_magazine_join_request_notifies_owner(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $applicant = User::factory()->create(['email_verified_at' => now()]);
        $magazine = Magazine::query()->create([
            'owner_id' => $owner->id,
            'name' => 'Join Mag',
            'slug' => 'join-mag',
        ]);
        $magazine->members()->attach($owner->id, ['role' => 'owner']);

        $this->actingAs($applicant)
            ->post(route('magazines.join-requests.store', $magazine))
            ->assertRedirect();

        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $owner->id,
        ]);

        $notification = $owner->notifications()->first();
        $this->assertSame('magazine_join_request', $notification->data['kind']);
        $this->assertSame('Join Mag', $notification->data['magazine_name']);
    }

    public function test_navigation_shows_unread_notification_badge(): void
    {
        $user = User::factory()->create();
        $actor = User::factory()->create();

        $user->notify(new UserActivity('follow', $actor));

        $this->actingAs($user)
            ->get(route('home'))
            ->assertOk()
            ->assertSee('Notifications', false)
            ->assertSee('bg-red-500', false);
    }

    public function test_mark_all_read_clears_unread_count(): void
    {
        $user = User::factory()->create();
        $actor = User::factory()->create();

        $user->notify(new UserActivity('follow', $actor));
        $this->assertSame(1, $user->unreadNotifications()->count());

        $this->actingAs($user)
            ->post(route('notifications.read'))
            ->assertRedirect();

        $this->assertSame(0, $user->fresh()->unreadNotifications()->count());
    }
}
