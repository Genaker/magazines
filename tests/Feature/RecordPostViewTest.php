<?php

namespace Tests\Feature;

use App\Jobs\RecordPostView;
use App\Models\Category;
use App\Models\Post;
use App\Models\PostView;
use App\Models\User;
use App\Support\PostViewRecorder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class RecordPostViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_count_mode_is_sync(): void
    {
        $this->assertSame('sync', config('post-views.count_mode'));
        $this->assertFalse(PostViewRecorder::isAsync());
    }

    public function test_published_post_can_be_viewed_multiple_times_same_day(): void
    {
        $author = User::factory()->create(['username' => 'demoauthor']);
        $category = Category::query()->create(['name' => 'Writing', 'slug' => 'writing']);
        $post = Post::factory()->for($author)->for($category)->create([
            'slug' => 'why-writing-matters',
        ]);

        $url = route('posts.show', [$author->username, $post->slug]);

        $this->get($url)->assertOk();
        $this->get($url)->assertOk();

        $this->assertSame(1, $post->fresh()->views_count);
        $this->assertSame(1, PostView::query()->where('post_id', $post->id)->count());
    }

    public function test_sync_mode_records_view_after_response(): void
    {
        config(['post-views.count_mode' => 'sync']);

        $author = User::factory()->create(['username' => 'syncauthor']);
        $category = Category::query()->create(['name' => 'Sync', 'slug' => 'sync']);
        $post = Post::factory()->for($author)->for($category)->create([
            'slug' => 'sync-view-test',
            'views_count' => 0,
        ]);

        Queue::fake();

        $this->get(route('posts.show', [$author->username, $post->slug]))->assertOk();

        Queue::assertNothingPushed();
        $this->assertSame(1, $post->fresh()->views_count);
    }

    public function test_async_mode_dispatches_job_to_queue(): void
    {
        Queue::fake();
        config(['post-views.count_mode' => 'async']);

        $author = User::factory()->create(['username' => 'asyncauthor']);
        $category = Category::query()->create(['name' => 'Async', 'slug' => 'async']);
        $post = Post::factory()->for($author)->for($category)->create([
            'slug' => 'async-view-test',
            'views_count' => 0,
        ]);

        $this->get(route('posts.show', [$author->username, $post->slug]))->assertOk();

        Queue::assertPushed(RecordPostView::class, fn (RecordPostView $job) => $job->postId === $post->id
            && $job->ipAddress !== ''
        );
        $this->assertSame(0, $post->fresh()->views_count);
    }

    public function test_async_mode_records_view_after_response_when_queue_runs(): void
    {
        config(['post-views.count_mode' => 'async']);

        $author = User::factory()->create(['username' => 'asyncrunauthor']);
        $category = Category::query()->create(['name' => 'Async Run', 'slug' => 'async-run']);
        $post = Post::factory()->for($author)->for($category)->create([
            'slug' => 'async-run-view-test',
            'views_count' => 0,
        ]);

        $this->get(route('posts.show', [$author->username, $post->slug]))->assertOk();

        $this->assertSame(1, $post->fresh()->views_count);
        $this->assertSame(1, PostView::query()->where('post_id', $post->id)->count());
    }

    public function test_post_view_recorder_is_async_reflects_config(): void
    {
        config(['post-views.count_mode' => 'async']);
        $this->assertTrue(PostViewRecorder::usesQueue());
        $this->assertTrue(PostViewRecorder::isAsync());

        config(['post-views.count_mode' => 'sync']);
        $this->assertFalse(PostViewRecorder::usesQueue());
        $this->assertFalse(PostViewRecorder::isAsync());
    }
}
