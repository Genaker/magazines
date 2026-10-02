<?php

namespace Tests\Feature;

use App\Events\AuthorSubscribed;
use App\Events\CommentCreated;
use App\Events\CommentLiked;
use App\Events\PostPublished;
use App\Events\PostViewed;
use App\Events\UserFollowed;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class DomainEventsTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_published_event_dispatched_when_post_is_published(): void
    {
        Event::fake([PostPublished::class]);

        Post::factory()->create(['status' => 'published']);

        Event::assertDispatched(PostPublished::class);
    }

    public function test_comment_created_event_dispatched_after_store(): void
    {
        Event::fake([CommentCreated::class]);

        $author = User::factory()->create();
        $commenter = User::factory()->create();
        $post = Post::factory()->for($author)->create();

        $this->actingAs($commenter)
            ->post(route('posts.comments.store', $post), ['body' => 'Hello world'])
            ->assertRedirect();

        Event::assertDispatched(CommentCreated::class, fn (CommentCreated $event) => $event->comment->body === 'Hello world');
    }

    public function test_user_followed_event_dispatched_after_follow(): void
    {
        Event::fake([UserFollowed::class]);

        $follower = User::factory()->create();
        $author = User::factory()->create();

        $this->actingAs($follower)
            ->postJson(route('users.follow', $author))
            ->assertOk();

        Event::assertDispatched(UserFollowed::class);
    }

    public function test_comment_liked_event_dispatched_after_like(): void
    {
        Event::fake([CommentLiked::class]);

        $author = User::factory()->create();
        $liker = User::factory()->create();
        $post = Post::factory()->for($author)->create();
        $comment = Comment::query()->create([
            'post_id' => $post->id,
            'user_id' => $author->id,
            'body' => 'Nice',
        ]);

        $this->actingAs($liker)
            ->postJson(route('comments.like', $comment))
            ->assertOk();

        Event::assertDispatched(CommentLiked::class);
    }

    public function test_author_subscribed_event_dispatched_after_subscribe(): void
    {
        Event::fake([AuthorSubscribed::class]);

        $subscriber = User::factory()->create();
        $author = User::factory()->create();

        $this->actingAs($subscriber)
            ->postJson(route('users.subscribe', $author))
            ->assertOk();

        Event::assertDispatched(AuthorSubscribed::class);
    }

    public function test_post_viewed_event_dispatched_after_unique_view_recorded(): void
    {
        Event::fake([PostViewed::class]);

        $post = Post::factory()->create();

        dispatch_sync(new \App\Jobs\RecordPostView($post->id, '192.168.1.1'));

        Event::assertDispatched(PostViewed::class, fn (PostViewed $event) => $event->postId === $post->id);
    }
}
