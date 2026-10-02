<?php

namespace Tests\Unit;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentTreeTest extends TestCase
{
    use RefreshDatabase;

    public function test_depth_counts_ancestors(): void
    {
        $post = Post::factory()->create();
        $root = Comment::query()->create(['post_id' => $post->id, 'user_id' => User::factory()->create()->id, 'body' => 'root']);
        $reply = Comment::query()->create(['post_id' => $post->id, 'user_id' => User::factory()->create()->id, 'parent_id' => $root->id, 'body' => 'reply']);
        $nested = Comment::query()->create(['post_id' => $post->id, 'user_id' => User::factory()->create()->id, 'parent_id' => $reply->id, 'body' => 'nested']);

        $this->assertSame(0, $root->depth());
        $this->assertSame(1, $reply->depth());
        $this->assertSame(2, $nested->depth());
    }

    public function test_build_tree_nests_replies_and_orders_roots_newest_first(): void
    {
        $post = Post::factory()->create();
        $user = User::factory()->create();

        $older = Comment::query()->create([
            'post_id' => $post->id,
            'user_id' => $user->id,
            'body' => 'older',
        ]);
        $newer = Comment::query()->create([
            'post_id' => $post->id,
            'user_id' => $user->id,
            'body' => 'newer',
        ]);
        Comment::query()->whereKey($older->id)->update(['created_at' => now()->subHours(2)]);
        Comment::query()->whereKey($newer->id)->update(['created_at' => now()->subHour()]);
        $older->refresh();
        $newer->refresh();

        Comment::query()->create([
            'post_id' => $post->id,
            'user_id' => $user->id,
            'parent_id' => $older->id,
            'body' => 'reply',
        ]);

        $tree = Comment::buildTree(Comment::query()->where('post_id', $post->id)->get());

        $this->assertSame('newer', $tree->first()->body);
        $this->assertSame('older', $tree->last()->body);
        $this->assertSame('reply', $tree->last()->replies->first()->body);
    }

    public function test_tree_for_post_loads_users(): void
    {
        $post = Post::factory()->create();
        $user = User::factory()->create(['name' => 'Tree Author']);

        Comment::query()->create([
            'post_id' => $post->id,
            'user_id' => $user->id,
            'body' => 'hello',
        ]);

        $tree = Comment::treeForPost($post);

        $this->assertSame('Tree Author', $tree->first()->user->name);
    }

    public function test_build_tree_orders_roots_by_likes_when_top_sort(): void
    {
        $post = Post::factory()->create();
        $user = User::factory()->create();

        $low = Comment::query()->create([
            'post_id' => $post->id,
            'user_id' => $user->id,
            'body' => 'low',
        ]);
        $high = Comment::query()->create([
            'post_id' => $post->id,
            'user_id' => $user->id,
            'body' => 'high',
        ]);
        Comment::query()->whereKey($low->id)->update(['likes_count' => 1]);
        Comment::query()->whereKey($high->id)->update(['likes_count' => 10]);

        $tree = Comment::buildTree(
            Comment::query()->where('post_id', $post->id)->get(),
            null,
            true,
            'top',
        );

        $this->assertSame('high', $tree->first()->body);
        $this->assertSame('low', $tree->last()->body);
    }
}
