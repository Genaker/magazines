<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Comment;
use App\Models\Post;
use App\Models\ReadingList;
use App\Models\User;
use App\Services\PostShowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostShowServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_viewer_data_reuses_comment_query_for_count_and_likes(): void
    {
        $author = User::factory()->create(['username' => 'showauthor']);
        $viewer = User::factory()->create();
        $category = Category::query()->create(['name' => 'Essays', 'slug' => 'essays']);
        $post = Post::factory()->for($author)->for($category)->create([
            'slug' => 'show-service-test',
        ]);

        $root = Comment::query()->create([
            'post_id' => $post->id,
            'user_id' => $author->id,
            'body' => 'Root comment',
        ]);
        Comment::query()->create([
            'post_id' => $post->id,
            'user_id' => $author->id,
            'parent_id' => $root->id,
            'body' => 'Reply comment',
        ]);

        $list = ReadingList::query()->create([
            'user_id' => $viewer->id,
            'name' => 'Saved',
            'is_default' => true,
            'is_private' => true,
        ]);
        $list->posts()->attach($post->id);

        $data = app(PostShowService::class)->viewerData($post, $viewer, 'new', false);

        $this->assertSame(2, $data['commentCount']);
        $this->assertCount(1, $data['comments']);
        $this->assertTrue($data['bookmarked']);
        $this->assertTrue($data['savedListIds']->contains($list->id));
        $this->assertCount(1, $data['readingLists']);
    }

    public function test_post_show_page_renders_with_batched_viewer_data(): void
    {
        $author = User::factory()->create(['username' => 'pageauthor']);
        $category = Category::query()->create(['name' => 'Culture', 'slug' => 'culture']);
        $post = Post::factory()->for($author)->for($category)->create([
            'title' => 'Batch show page',
            'slug' => 'batch-show-page',
        ]);

        Comment::query()->create([
            'post_id' => $post->id,
            'user_id' => $author->id,
            'body' => 'Nice read',
        ]);

        $this->get(route('posts.show', [$author->username, $post->slug]))
            ->assertOk()
            ->assertSee('Batch show page')
            ->assertSee('Nice read');
    }
}
