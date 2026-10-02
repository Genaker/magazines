<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\ReadingList;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingListTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_and_view_reading_lists(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('reading-lists.store'), ['name' => 'Weekend reads'])
            ->assertRedirect();

        $list = ReadingList::query()->where('user_id', $user->id)->where('name', 'Weekend reads')->first();
        $this->assertNotNull($list);

        $this->actingAs($user)
            ->get(route('reading-lists.show', $list))
            ->assertOk()
            ->assertSee('Weekend reads')
            ->assertSee('No stories in this list yet.');
    }

    public function test_bookmark_toggle_uses_default_list(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $this->actingAs($user)
            ->postJson(route('posts.bookmark', $post))
            ->assertOk()
            ->assertJson(['bookmarked' => true]);

        $defaultList = ReadingList::defaultForUser($user);
        $this->assertTrue($defaultList->posts()->where('posts.id', $post->id)->exists());

        $this->actingAs($user)
            ->get(route('reading-lists.show', $defaultList))
            ->assertOk()
            ->assertSee($post->title);
    }

    public function test_user_can_add_post_to_custom_list(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();
        $list = $user->readingLists()->create(['name' => 'Favorites']);

        $this->actingAs($user)
            ->postJson(route('reading-lists.posts.toggle', [$list, $post]))
            ->assertOk()
            ->assertJson(['saved' => true, 'bookmarked' => true]);

        $this->assertTrue($list->posts()->where('posts.id', $post->id)->exists());

        $this->actingAs($user)
            ->postJson(route('reading-lists.posts.toggle', [$list, $post]))
            ->assertOk()
            ->assertJson(['saved' => false, 'bookmarked' => false]);
    }

    public function test_user_cannot_view_another_users_list(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $list = $owner->readingLists()->create(['name' => 'Private picks']);

        $this->actingAs($other)
            ->get(route('reading-lists.show', $list))
            ->assertForbidden();
    }

    public function test_user_cannot_delete_default_list(): void
    {
        $user = User::factory()->create();
        $defaultList = ReadingList::defaultForUser($user);

        $this->actingAs($user)
            ->delete(route('reading-lists.destroy', $defaultList))
            ->assertForbidden();
    }

    public function test_creating_list_from_post_adds_post_automatically(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $this->actingAs($user)
            ->postJson(route('reading-lists.store'), [
                'name' => 'Weekend reads',
                'description' => 'For slow Sundays',
                'is_private' => true,
                'post_id' => $post->id,
            ])
            ->assertOk()
            ->assertJson([
                'bookmarked' => true,
                'list' => [
                    'name' => 'Weekend reads',
                    'has_post' => true,
                    'is_private' => true,
                ],
            ]);

        $list = ReadingList::query()->where('name', 'Weekend reads')->first();
        $this->assertTrue($list->posts()->where('posts.id', $post->id)->exists());
    }
}
