<?php

namespace Tests\Feature;

use App\Enums\AuthorSubscriptionDelivery;
use App\Enums\PostStatus;
use App\Models\Category;
use App\Models\Post;
use App\Models\SubscriptionDigestItem;
use App\Models\User;
use App\Notifications\AuthorStoryDigest;
use App\Notifications\AuthorStoryPublished;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AuthorSubscriptionEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_instant_email_sent_when_subscribed_author_publishes(): void
    {
        Notification::fake();

        $author = User::factory()->create(['email_verified_at' => now()]);
        $subscriber = User::factory()->create(['email_verified_at' => now()]);
        $category = Category::query()->create(['name' => 'News', 'slug' => 'news']);

        $subscriber->storySubscriptions()->attach($author->id, [
            'email_delivery' => AuthorSubscriptionDelivery::Instant->value,
        ]);

        Post::query()->create([
            'user_id' => $author->id,
            'category_id' => $category->id,
            'title' => 'Breaking update',
            'slug' => 'breaking-update',
            'body' => '<p>Hello subscribers</p>',
            'status' => PostStatus::Published,
            'published_at' => now(),
        ]);

        Notification::assertSentTo($subscriber, AuthorStoryPublished::class);
    }

    public function test_daily_delivery_queues_digest_item(): void
    {
        Notification::fake();

        $author = User::factory()->create(['email_verified_at' => now()]);
        $subscriber = User::factory()->create(['email_verified_at' => now()]);
        $category = Category::query()->create(['name' => 'News', 'slug' => 'news-digest']);

        $subscriber->storySubscriptions()->attach($author->id, [
            'email_delivery' => AuthorSubscriptionDelivery::Daily->value,
        ]);

        $post = Post::query()->create([
            'user_id' => $author->id,
            'category_id' => $category->id,
            'title' => 'Digest story',
            'slug' => 'digest-story',
            'body' => '<p>Daily</p>',
            'status' => PostStatus::Published,
            'published_at' => now(),
        ]);

        Notification::assertNotSentTo($subscriber, AuthorStoryPublished::class);
        $this->assertDatabaseHas('subscription_digest_items', [
            'subscriber_id' => $subscriber->id,
            'post_id' => $post->id,
        ]);
    }

    public function test_digest_command_sends_email_and_clears_queue(): void
    {
        Notification::fake();

        $author = User::factory()->create(['email_verified_at' => now()]);
        $subscriber = User::factory()->create(['email_verified_at' => now()]);
        $category = Category::query()->create(['name' => 'News', 'slug' => 'news-cmd']);

        $post = Post::query()->create([
            'user_id' => $author->id,
            'category_id' => $category->id,
            'title' => 'Queued story',
            'slug' => 'queued-story',
            'body' => '<p>Queued</p>',
            'status' => PostStatus::Published,
            'published_at' => now(),
            'subscription_notified_at' => now(),
        ]);

        SubscriptionDigestItem::query()->create([
            'subscriber_id' => $subscriber->id,
            'post_id' => $post->id,
        ]);

        Artisan::call('subscriptions:send-digest');

        Notification::assertSentTo($subscriber, AuthorStoryDigest::class);
        $this->assertDatabaseMissing('subscription_digest_items', [
            'subscriber_id' => $subscriber->id,
            'post_id' => $post->id,
        ]);
    }

    public function test_subscribe_accepts_delivery_preference(): void
    {
        $author = User::factory()->create();
        $subscriber = User::factory()->create();

        $this->actingAs($subscriber)
            ->postJson(route('users.subscribe', $author), [
                'delivery' => AuthorSubscriptionDelivery::Daily->value,
            ])
            ->assertOk()
            ->assertJson([
                'subscribed' => true,
                'delivery' => AuthorSubscriptionDelivery::Daily->value,
            ]);

        $this->assertDatabaseHas('author_subscriptions', [
            'subscriber_id' => $subscriber->id,
            'author_id' => $author->id,
            'email_delivery' => AuthorSubscriptionDelivery::Daily->value,
        ]);
    }

    public function test_publish_does_not_renotify_after_edit(): void
    {
        Notification::fake();

        $author = User::factory()->create(['email_verified_at' => now()]);
        $subscriber = User::factory()->create(['email_verified_at' => now()]);
        $category = Category::query()->create(['name' => 'News', 'slug' => 'news-edit']);

        $subscriber->storySubscriptions()->attach($author->id, [
            'email_delivery' => AuthorSubscriptionDelivery::Instant->value,
        ]);

        $post = Post::query()->create([
            'user_id' => $author->id,
            'category_id' => $category->id,
            'title' => 'Original',
            'slug' => 'original',
            'body' => '<p>One</p>',
            'status' => PostStatus::Published,
            'published_at' => now(),
        ]);

        Notification::assertSentToTimes($subscriber, AuthorStoryPublished::class, 1);

        $post->update(['title' => 'Updated title']);

        Notification::assertSentToTimes($subscriber, AuthorStoryPublished::class, 1);
    }
}
