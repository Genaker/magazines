<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Post;
use App\Models\SiteSetting;
use App\Models\User;
use App\Notifications\UserActivity;
use App\Support\CommentFormatter;
use App\Support\Features;
use App\Support\Message;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CommentTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_post_comment(): void
    {
        $post = Post::factory()->create();

        $this->post(route('posts.comments.store', $post), ['body' => 'Hello'])
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_post_comment_and_reply(): void
    {
        $author = User::factory()->create();
        $commenter = User::factory()->create();
        $post = Post::factory()->for($author)->create();

        $this->actingAs($commenter)
            ->post(route('posts.comments.store', $post), ['body' => 'Top level'])
            ->assertRedirect();

        $comment = Comment::query()->first();
        $this->assertNotNull($comment);
        $this->assertNull($comment->parent_id);

        $this->actingAs($commenter)
            ->post(route('posts.comments.store', $post), [
                'body' => 'A reply',
                'parent_id' => $comment->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('comments', [
            'parent_id' => $comment->id,
            'body' => 'A reply',
        ]);
    }

    public function test_blocked_user_cannot_view_post_or_comment(): void
    {
        $author = User::factory()->create();
        $blocked = User::factory()->create();
        $post = Post::factory()->for($author)->create([
            'slug' => 'blocked-post',
        ]);

        $author->blockedUsers()->attach($blocked->id);

        $this->actingAs($blocked)
            ->get(route('posts.show', [$author, $post->slug]))
            ->assertForbidden();

        $this->actingAs($blocked)
            ->post(route('posts.comments.store', $post), ['body' => 'Nope'])
            ->assertForbidden();
    }

    public function test_author_can_disable_comments_on_posts(): void
    {
        $author = User::factory()->create(['allow_comments' => false]);
        $commenter = User::factory()->create();
        $post = Post::factory()->for($author)->create();

        $this->actingAs($commenter)
            ->post(route('posts.comments.store', $post), ['body' => 'Not allowed'])
            ->assertForbidden();
    }

    public function test_user_can_reply_to_nested_comment(): void
    {
        $author = User::factory()->create();
        $commenter = User::factory()->create();
        $post = Post::factory()->for($author)->create();

        $this->actingAs($commenter)
            ->post(route('posts.comments.store', $post), ['body' => 'Top level'])
            ->assertRedirect();

        $top = Comment::query()->whereNull('parent_id')->firstOrFail();

        $this->actingAs($commenter)
            ->post(route('posts.comments.store', $post), [
                'body' => 'First reply',
                'parent_id' => $top->id,
            ])
            ->assertRedirect();

        $reply = Comment::query()->where('parent_id', $top->id)->firstOrFail();

        $this->actingAs($commenter)
            ->post(route('posts.comments.store', $post), [
                'body' => 'Nested reply',
                'parent_id' => $reply->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('comments', [
            'parent_id' => $reply->id,
            'body' => 'Nested reply',
        ]);
    }

    public function test_comment_depth_limit_is_enforced(): void
    {
        $author = User::factory()->create();
        $commenter = User::factory()->create();
        $post = Post::factory()->for($author)->create();
        $post->load('authorAlias');

        $parent = null;
        for ($i = 0; $i < Comment::MAX_DEPTH; $i++) {
            $parent = Comment::query()->create([
                'post_id' => $post->id,
                'user_id' => $commenter->id,
                'parent_id' => $parent?->id,
                'body' => "level {$i}",
            ]);
        }

        $this->actingAs($commenter)
            ->from(route('posts.show', [$post->authorAlias, $post->slug]))
            ->post(route('posts.comments.store', $post), [
                'body' => 'too deep',
                'parent_id' => $parent->id,
            ])
            ->assertRedirect(route('posts.show', [$post->authorAlias, $post->slug]))
            ->assertSessionHas(Message::SESSION_KEY);

        $this->assertDatabaseMissing('comments', [
            'body' => 'too deep',
        ]);
    }

    public function test_post_author_can_delete_comment(): void
    {
        $author = User::factory()->create();
        $commenter = User::factory()->create();
        $post = Post::factory()->for($author)->create(['slug' => 'delete-comment-post']);
        $comment = Comment::query()->create([
            'post_id' => $post->id,
            'user_id' => $commenter->id,
            'body' => 'remove me',
        ]);

        $this->actingAs($author)
            ->delete(route('comments.destroy', $comment))
            ->assertRedirect(route('posts.show', [$author, $post->slug]).'#comments');

        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
    }

    public function test_comments_render_on_post_page(): void
    {
        $author = User::factory()->create(['username' => 'commenthost']);
        $commenter = User::factory()->create(['name' => 'Comment Writer']);
        $post = Post::factory()->for($author)->create(['slug' => 'comment-render-post']);

        Comment::query()->create([
            'post_id' => $post->id,
            'user_id' => $commenter->id,
            'body' => 'Visible threaded comment',
        ]);

        $this->get(route('posts.show', [$author, $post->slug]))
            ->assertOk()
            ->assertSee('Discussion (1)')
            ->assertSee('Visible threaded comment')
            ->assertSee('Comment Writer');
    }

    public function test_post_page_renders_disqus_when_enabled(): void
    {
        Features::seedDefaults();
        SiteSetting::setValue('comments_use_disqus', '1');
        SiteSetting::setValue('disqus_shortname', 'testforum');

        $author = User::factory()->create(['username' => 'disqushost']);
        $post = Post::factory()->for($author)->create(['slug' => 'disqus-post']);

        $this->get(route('posts.show', [$author, $post->slug]))
            ->assertOk()
            ->assertSee('id="disqus_thread"', false)
            ->assertSee('testforum.disqus.com/embed.js', false)
            ->assertDontSee('Discussion (0)');
    }

    public function test_native_comment_routes_disabled_when_disqus_enabled(): void
    {
        Features::seedDefaults();
        SiteSetting::setValue('comments_use_disqus', '1');
        SiteSetting::setValue('disqus_shortname', 'testforum');

        $author = User::factory()->create();
        $commenter = User::factory()->create();
        $post = Post::factory()->for($author)->create();

        $this->actingAs($commenter)
            ->post(route('posts.comments.store', $post), ['body' => 'Hello'])
            ->assertNotFound();

        $comment = Comment::query()->create([
            'post_id' => $post->id,
            'user_id' => $commenter->id,
            'body' => 'Existing',
        ]);

        $this->actingAs($commenter)
            ->delete(route('comments.destroy', $comment))
            ->assertNotFound();
    }

    public function test_native_comment_update_disabled_when_disqus_enabled(): void
    {
        Features::seedDefaults();
        SiteSetting::setValue('comments_use_disqus', '1');
        SiteSetting::setValue('disqus_shortname', 'testforum');

        $author = User::factory()->create();
        $commenter = User::factory()->create();
        $post = Post::factory()->for($author)->create();
        $comment = Comment::query()->create([
            'post_id' => $post->id,
            'user_id' => $commenter->id,
            'body' => 'Existing',
        ]);

        $this->actingAs($commenter)
            ->put(route('comments.update', $comment), ['body' => 'Updated'])
            ->assertNotFound();
    }

    public function test_user_can_toggle_block_from_profile_settings(): void
    {
        $user = User::factory()->create();
        $target = User::factory()->create();

        $this->actingAs($user)
            ->post(route('users.block', $target))
            ->assertRedirect();

        $this->assertTrue($user->fresh()->hasBlocked($target));

        $this->actingAs($user)
            ->delete(route('profile.blocked-users.destroy', $target))
            ->assertRedirect();

        $this->assertFalse($user->fresh()->hasBlocked($target));
    }

    public function test_comment_author_can_edit_own_comment(): void
    {
        $author = User::factory()->create();
        $commenter = User::factory()->create();
        $other = User::factory()->create();
        $post = Post::factory()->for($author)->create(['slug' => 'edit-comment-post']);
        $comment = Comment::query()->create([
            'post_id' => $post->id,
            'user_id' => $commenter->id,
            'body' => 'Original text',
        ]);

        $this->actingAs($commenter)
            ->put(route('comments.update', $comment), ['body' => 'Updated text'])
            ->assertRedirect(route('posts.show', [$author, $post->slug]).'#comment-'.$comment->id);

        $comment->refresh();
        $this->assertSame('Updated text', $comment->body);
        $this->assertNotNull($comment->edited_at);

        $this->actingAs($other)
            ->put(route('comments.update', $comment), ['body' => 'Hijacked'])
            ->assertForbidden();
    }

    public function test_user_can_like_and_unlike_comment(): void
    {
        $author = User::factory()->create();
        $commenter = User::factory()->create();
        $reader = User::factory()->create();
        $post = Post::factory()->for($author)->create(['slug' => 'like-comment-post']);
        $comment = Comment::query()->create([
            'post_id' => $post->id,
            'user_id' => $commenter->id,
            'body' => 'Like me',
        ]);

        $this->actingAs($reader)
            ->postJson(route('comments.like', $comment))
            ->assertOk()
            ->assertJson(['liked' => true, 'likes_count' => 1]);

        $this->assertDatabaseHas('comment_likes', [
            'comment_id' => $comment->id,
            'user_id' => $reader->id,
        ]);

        $this->actingAs($reader)
            ->postJson(route('comments.like', $comment))
            ->assertOk()
            ->assertJson(['liked' => false, 'likes_count' => 0]);

        $this->assertDatabaseMissing('comment_likes', [
            'comment_id' => $comment->id,
            'user_id' => $reader->id,
        ]);
    }

    public function test_comment_like_creates_notification_for_comment_author(): void
    {
        Notification::fake();

        $author = User::factory()->create();
        $commenter = User::factory()->create();
        $reader = User::factory()->create();
        $post = Post::factory()->for($author)->create();
        $comment = Comment::query()->create([
            'post_id' => $post->id,
            'user_id' => $commenter->id,
            'body' => 'Nice take',
        ]);

        $this->actingAs($reader)
            ->postJson(route('comments.like', $comment))
            ->assertOk();

        Notification::assertSentTo(
            $commenter,
            UserActivity::class,
            fn (UserActivity $notification) => $notification->kind === 'comment_like',
        );
    }

    public function test_top_sort_orders_root_comments_by_likes(): void
    {
        $author = User::factory()->create(['username' => 'topsortauthor']);
        $post = Post::factory()->for($author)->create(['slug' => 'top-sort-post']);
        $user = User::factory()->create();

        $popular = Comment::query()->create([
            'post_id' => $post->id,
            'user_id' => $user->id,
            'body' => 'Popular comment',
        ]);
        $recent = Comment::query()->create([
            'post_id' => $post->id,
            'user_id' => $user->id,
            'body' => 'Recent comment',
        ]);
        Comment::query()->whereKey($popular->id)->update(['likes_count' => 5]);
        Comment::query()->whereKey($popular->id)->update(['created_at' => now()->subDay()]);
        Comment::query()->whereKey($recent->id)->update(['created_at' => now()]);

        $tree = Comment::treeForPost($post, 'top');

        $this->assertSame('Popular comment', $tree->first()->body);
        $this->assertSame('Recent comment', $tree->last()->body);

        $response = $this->get(route('posts.show', [$author, $post->slug]).'?comments=top');

        $response->assertOk();
        $this->assertLessThan(
            strpos($response->getContent(), 'Recent comment'),
            strpos($response->getContent(), 'Popular comment'),
        );
    }

    public function test_mention_notifies_user_and_renders_profile_link(): void
    {
        Notification::fake();

        $author = User::factory()->create(['username' => 'posthost']);
        $commenter = User::factory()->create();
        $mentioned = User::factory()->create(['username' => 'mentioneduser']);
        $post = Post::factory()->for($author)->create(['slug' => 'mention-post']);

        $this->actingAs($commenter)
            ->post(route('posts.comments.store', $post), ['body' => 'Hello @mentioneduser!'])
            ->assertRedirect();

        Notification::assertSentTo(
            $mentioned,
            UserActivity::class,
            fn (UserActivity $notification) => $notification->kind === 'comment_mention',
        );

        Notification::assertNotSentTo(
            $mentioned,
            UserActivity::class,
            fn (UserActivity $notification) => $notification->kind === 'comment',
        );

        $alias = $mentioned->primaryAlias();
        $profileUrl = route('authors.show', $alias);

        $this->get(route('posts.show', [$author, $post->slug]))
            ->assertOk()
            ->assertSee($profileUrl, false)
            ->assertSee('@mentioneduser', false);
    }

    public function test_comment_formatter_linkifies_known_usernames(): void
    {
        $user = User::factory()->create(['username' => 'linkableuser']);
        $alias = $user->primaryAlias();
        $url = route('authors.show', $alias);

        $formatted = CommentFormatter::format('Thanks @linkableuser for reading');

        $this->assertStringContainsString($url, $formatted);
        $this->assertStringContainsString('@linkableuser', $formatted);
    }
}
