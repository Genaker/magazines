<?php

namespace Tests\Feature;

use App\Enums\PostStatus;
use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use App\Services\FeedService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ScheduledPostTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_scheduled_post(): void
    {
        Carbon::setTestNow('2026-06-06 10:00:00');

        $author = User::factory()->create(['username' => 'scheduledauthor']);
        $post = Post::factory()->for($author)->create([
            'status' => PostStatus::Published,
            'published_at' => '2026-06-06 15:00:00',
        ]);

        $this->get(route('posts.show', [$author->username, $post->slug]))
            ->assertForbidden();
    }

    public function test_author_can_preview_scheduled_post(): void
    {
        Carbon::setTestNow('2026-06-06 10:00:00');

        $author = User::factory()->create(['username' => 'scheduledauthor']);
        $post = Post::factory()->for($author)->create([
            'title' => 'Future Story',
            'status' => PostStatus::Published,
            'published_at' => '2026-06-06 15:00:00',
        ]);

        $this->actingAs($author)
            ->get(route('posts.show', [$author->username, $post->slug]))
            ->assertOk()
            ->assertSee('Future Story')
            ->assertSee('Scheduled', false);
    }

    public function test_scheduled_post_becomes_public_when_publish_time_passes(): void
    {
        Carbon::setTestNow('2026-06-06 10:00:00');

        $author = User::factory()->create(['username' => 'scheduledauthor']);
        $post = Post::factory()->for($author)->create([
            'title' => 'Now Live',
            'status' => PostStatus::Published,
            'published_at' => '2026-06-06 15:00:00',
        ]);

        Carbon::setTestNow('2026-06-06 15:00:01');

        $this->get(route('posts.show', [$author->username, $post->slug]))
            ->assertOk()
            ->assertSee('Now Live')
            ->assertDontSee('Scheduled', false);
    }

    public function test_scheduled_post_is_excluded_from_published_scope(): void
    {
        Carbon::setTestNow('2026-06-06 10:00:00');

        $live = Post::factory()->create([
            'published_at' => '2026-06-06 09:00:00',
        ]);
        $scheduled = Post::factory()->create([
            'status' => PostStatus::Published,
            'published_at' => '2026-06-06 18:00:00',
        ]);

        $ids = Post::query()->published()->pluck('id')->all();

        $this->assertContains($live->id, $ids);
        $this->assertNotContains($scheduled->id, $ids);
    }

    public function test_author_can_schedule_post_via_publish_at_field(): void
    {
        Carbon::setTestNow('2026-06-06 10:00:00');

        $author = User::factory()->create();
        $category = Category::query()->create(['name' => 'Tech', 'slug' => 'tech']);

        $this->actingAs($author)->post(route('posts.store'), [
            'title' => 'Scheduled via form',
            'body' => '<p>Body</p>',
            'category_id' => $category->id,
            'status' => 'published',
            'publish_at' => '2026-06-07 09:30',
        ])->assertRedirect();

        $post = Post::query()->where('user_id', $author->id)->firstOrFail();

        $this->assertTrue($post->isScheduled());
        $this->assertSame('2026-06-07 09:30:00', $post->published_at->format('Y-m-d H:i:s'));
    }

    public function test_scheduled_post_appears_in_feed_after_publish_time(): void
    {
        Carbon::setTestNow('2026-06-06 10:00:00');

        $post = Post::factory()->create([
            'title' => 'Feed Scheduled Story',
            'status' => PostStatus::Published,
            'published_at' => '2026-06-06 12:00:00',
        ]);

        $sections = app(FeedService::class)->discoverSections();
        $this->assertFalse($sections['latest']->contains(fn (Post $item) => $item->id === $post->id));

        Carbon::setTestNow('2026-06-06 12:00:01');

        $sections = app(FeedService::class)->discoverSections();
        $this->assertTrue($sections['latest']->contains(fn (Post $item) => $item->id === $post->id));
    }
}
