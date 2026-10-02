<?php

namespace Tests\Feature\Integration;

use App\Models\Post;
use App\Models\ReadingList;
use App\Models\User;
use App\Support\Features;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Reading lists: default bookmark → custom list → list page shows saved post.
 */
class ReadingListFlowIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Features::seedDefaults();
    }

    public function test_bookmark_and_custom_list_flow(): void
    {
        $reader = User::factory()->create();
        $post = Post::factory()->create(['title' => 'List Integration Story']);

        $this->actingAs($reader)->postJson(route('posts.bookmark', $post))
            ->assertOk()
            ->assertJson(['bookmarked' => true]);

        $defaultList = ReadingList::defaultForUser($reader);
        $this->assertTrue($defaultList->posts()->whereKey($post->id)->exists());

        $this->actingAs($reader)->post(route('reading-lists.store'), [
            'name' => 'Integration picks',
        ])->assertRedirect();

        $customList = ReadingList::query()
            ->where('user_id', $reader->id)
            ->where('name', 'Integration picks')
            ->firstOrFail();

        $this->actingAs($reader)->postJson(route('reading-lists.posts.toggle', [$customList, $post]))
            ->assertOk()
            ->assertJson(['saved' => true]);

        $this->actingAs($reader)->get(route('reading-lists.show', $customList))
            ->assertOk()
            ->assertSee('Integration picks')
            ->assertSee('List Integration Story');
    }
}
