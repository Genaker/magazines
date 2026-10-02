<?php

namespace Tests\Feature;

use App\Enums\AuthorSubscriptionDelivery;
use App\Enums\PostStatus;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Post;
use App\Models\ReadingList;
use App\Models\User;
use App\Notifications\AuthorStoryPublished;
use App\Services\FeedService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ScheduledPublicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_notify_due_command_sends_subscription_emails_for_past_due_scheduled_posts(): void
    {
        Notification::fake();

        Carbon::setTestNow('2026-06-06 12:00:01');

        $author = User::factory()->create(['email_verified_at' => now()]);
        $subscriber = User::factory()->create(['email_verified_at' => now()]);
        $category = Category::query()->create(['name' => 'News', 'slug' => 'news-due']);

        $subscriber->storySubscriptions()->attach($author->id, [
            'email_delivery' => AuthorSubscriptionDelivery::Instant->value,
        ]);

        Post::withoutEvents(function () use ($author, $category): void {
            Post::query()->create([
                'user_id' => $author->id,
                'category_id' => $category->id,
                'title' => 'Scheduled and live',
                'slug' => 'scheduled-and-live',
                'body' => '<p>Hello</p>',
                'status' => PostStatus::Published,
                'published_at' => '2026-06-06 12:00:00',
                'subscription_notified_at' => null,
            ]);
        });

        Artisan::call('publications:notify-due');

        Notification::assertSentTo($subscriber, AuthorStoryPublished::class);
        $this->assertNotNull(Post::query()->value('subscription_notified_at'));
    }

    public function test_feed_service_does_not_notify_due_posts(): void
    {
        Notification::fake();

        Carbon::setTestNow('2026-06-06 12:00:01');

        $author = User::factory()->create(['email_verified_at' => now()]);
        $subscriber = User::factory()->create(['email_verified_at' => now()]);
        $category = Category::query()->create(['name' => 'News', 'slug' => 'news-feed']);

        $subscriber->storySubscriptions()->attach($author->id, [
            'email_delivery' => AuthorSubscriptionDelivery::Instant->value,
        ]);

        Post::withoutEvents(function () use ($author, $category): void {
            Post::query()->create([
                'user_id' => $author->id,
                'category_id' => $category->id,
                'title' => 'Feed should not notify',
                'slug' => 'feed-should-not-notify',
                'body' => '<p>Hello</p>',
                'status' => PostStatus::Published,
                'published_at' => '2026-06-06 12:00:00',
                'subscription_notified_at' => null,
            ]);
        });

        app(FeedService::class)->discoverSections();

        Notification::assertNothingSent();
    }
}
