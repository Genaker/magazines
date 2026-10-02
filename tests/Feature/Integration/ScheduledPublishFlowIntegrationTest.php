<?php

namespace Tests\Feature\Integration;

use App\Enums\AuthorSubscriptionDelivery;
use App\Enums\PostStatus;
use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use App\Notifications\AuthorStoryPublished;
use App\Support\EntityCache;
use App\Support\Features;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Scheduled publish: form → hidden from guests → goes live → feed + subscriber cron.
 */
class ScheduledPublishFlowIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Features::seedDefaults();
        EntityCache::flushStore();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_scheduled_post_becomes_public_and_appears_on_home(): void
    {
        Carbon::setTestNow('2026-06-10 10:00:00');

        $author = User::factory()->create(['username' => 'schedauthor']);
        $category = Category::query()->create(['name' => 'News', 'slug' => 'news']);

        $this->actingAs($author)->post(route('posts.store'), [
            'title' => 'Future Integration Story',
            'body' => '<p>Scheduled body.</p>',
            'category_id' => $category->id,
            'status' => 'published',
            'publish_at' => '2026-06-10 14:00',
        ])->assertRedirect();

        $post = Post::query()->where('user_id', $author->id)->firstOrFail();
        $showUrl = route('posts.show', [$author->primaryAlias(), $post->slug]);

        $this->post(route('logout'));

        $this->get($showUrl)->assertForbidden();

        Carbon::setTestNow('2026-06-10 14:00:01');
        EntityCache::flushStore();

        $this->get($showUrl)
            ->assertOk()
            ->assertSee('Future Integration Story');

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Future Integration Story');
    }

    public function test_publications_notify_due_command_emails_subscribers_after_schedule(): void
    {
        Notification::fake();
        Carbon::setTestNow('2026-06-10 10:00:00');

        $author = User::factory()->create(['email_verified_at' => now()]);
        $subscriber = User::factory()->create(['email_verified_at' => now()]);
        $category = Category::query()->create(['name' => 'News', 'slug' => 'news-notify']);

        $subscriber->storySubscriptions()->attach($author->id, [
            'email_delivery' => AuthorSubscriptionDelivery::Instant->value,
        ]);

        Post::withoutEvents(function () use ($author, $category): void {
            Post::query()->create([
                'user_id' => $author->id,
                'author_alias_id' => $author->primaryAlias()->id,
                'category_id' => $category->id,
                'title' => 'Cron Notified Story',
                'slug' => 'cron-notified-story',
                'body' => '<p>Due soon</p>',
                'status' => PostStatus::Published,
                'published_at' => '2026-06-10 12:00:00',
                'subscription_notified_at' => null,
            ]);
        });

        Notification::assertNothingSent();

        Carbon::setTestNow('2026-06-10 12:00:01');

        Artisan::call('publications:notify-due');

        Notification::assertSentTo($subscriber, AuthorStoryPublished::class);
        $this->assertNotNull(Post::query()->value('subscription_notified_at'));
    }
}
