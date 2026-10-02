<?php

namespace Tests\Feature\Integration;

use App\Enums\AuthorSubscriptionDelivery;
use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use App\Notifications\AuthorStoryDigest;
use App\Notifications\AuthorStoryPublished;
use App\Support\Features;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Author subscriptions: subscribe → publish → instant email; daily digest cron.
 */
class AuthorSubscriptionFlowIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Features::seedDefaults();
    }

    public function test_subscribe_then_publish_sends_instant_notification(): void
    {
        Notification::fake();

        $author = User::factory()->create(['email_verified_at' => now()]);
        $subscriber = User::factory()->create(['email_verified_at' => now()]);
        $category = Category::query()->create(['name' => 'Essays', 'slug' => 'essays']);

        $this->actingAs($subscriber)->postJson(route('users.subscribe', $author), [
            'delivery' => AuthorSubscriptionDelivery::Instant->value,
        ])
            ->assertOk()
            ->assertJson(['subscribed' => true]);

        $this->actingAs($author)->post(route('posts.store'), [
            'title' => 'Subscriber Integration Post',
            'body' => '<p>For our subscribers.</p>',
            'category_id' => $category->id,
            'status' => 'published',
        ])->assertRedirect();

        Notification::assertSentTo($subscriber, AuthorStoryPublished::class);

        $post = Post::query()->where('title', 'Subscriber Integration Post')->firstOrFail();

        $this->post(route('logout'));

        $this->get(route('posts.show', [$author->primaryAlias(), $post->slug]))
            ->assertOk()
            ->assertSee('Subscriber Integration Post');
    }

    public function test_daily_subscription_queues_digest_then_command_sends_email(): void
    {
        Notification::fake();

        $author = User::factory()->create(['email_verified_at' => now()]);
        $subscriber = User::factory()->create(['email_verified_at' => now()]);
        $category = Category::query()->create(['name' => 'Essays', 'slug' => 'essays-digest']);

        $this->actingAs($subscriber)->postJson(route('users.subscribe', $author), [
            'delivery' => AuthorSubscriptionDelivery::Daily->value,
        ])->assertOk();

        $this->actingAs($author)->post(route('posts.store'), [
            'title' => 'Digest Integration Post',
            'body' => '<p>Daily batch.</p>',
            'category_id' => $category->id,
            'status' => 'published',
        ])->assertRedirect();

        $post = Post::query()->where('title', 'Digest Integration Post')->firstOrFail();

        Notification::assertNotSentTo($subscriber, AuthorStoryPublished::class);
        $this->assertDatabaseHas('subscription_digest_items', [
            'subscriber_id' => $subscriber->id,
            'post_id' => $post->id,
        ]);

        $post->updateQuietly(['subscription_notified_at' => now()]);

        Artisan::call('subscriptions:send-digest');

        Notification::assertSentTo($subscriber, AuthorStoryDigest::class);
        $this->assertDatabaseMissing('subscription_digest_items', [
            'subscriber_id' => $subscriber->id,
            'post_id' => $post->id,
        ]);
    }
}
