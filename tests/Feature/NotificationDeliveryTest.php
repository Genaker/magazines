<?php

namespace Tests\Feature;

use App\Enums\PostStatus;
use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use App\Notifications\AuthorStoryPublished;
use App\Support\NotificationSender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class NotificationDeliveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_subscription_email_is_queued_when_delivery_mode_is_queue(): void
    {
        Bus::fake();
        config(['notifications.email_delivery' => 'queue']);

        $author = User::factory()->create(['email_verified_at' => now()]);
        $subscriber = User::factory()->create(['email_verified_at' => now()]);
        $category = Category::query()->create(['name' => 'News', 'slug' => 'news']);
        $post = Post::query()->create([
            'user_id' => $author->id,
            'category_id' => $category->id,
            'title' => 'Queued story',
            'slug' => 'queued-story',
            'body' => '<p>Hello</p>',
            'status' => PostStatus::Published,
            'published_at' => now(),
        ]);

        NotificationSender::send($subscriber, new AuthorStoryPublished($post, $author));

        Bus::assertDispatched(SendQueuedNotifications::class);
    }

    public function test_subscription_email_is_sent_immediately_when_delivery_mode_is_sync(): void
    {
        Bus::fake();
        config(['notifications.email_delivery' => 'sync']);

        $author = User::factory()->create(['email_verified_at' => now()]);
        $subscriber = User::factory()->create(['email_verified_at' => now()]);
        $category = Category::query()->create(['name' => 'News', 'slug' => 'news']);
        $post = Post::query()->create([
            'user_id' => $author->id,
            'category_id' => $category->id,
            'title' => 'Instant story',
            'slug' => 'instant-story',
            'body' => '<p>Hello</p>',
            'status' => PostStatus::Published,
            'published_at' => now(),
        ]);

        NotificationSender::send($subscriber, new AuthorStoryPublished($post, $author));

        Bus::assertNotDispatched(SendQueuedNotifications::class);
    }

    public function test_notification_delivery_helper_reports_queue_mode(): void
    {
        config(['notifications.email_delivery' => 'queue']);
        $this->assertTrue(\App\Support\NotificationDelivery::usesQueue());

        config(['notifications.email_delivery' => 'sync']);
        $this->assertFalse(\App\Support\NotificationDelivery::usesQueue());
    }
}
