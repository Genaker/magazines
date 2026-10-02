<?php

namespace Tests\Feature;

use App\Models\AuthorAlias;
use App\Models\Post;
use App\Models\User;
use App\Support\ActiveAuthorAlias;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorAliasTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_and_switch_aliases(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('profile.aliases.store'), [
                'name' => 'Pen Name',
                'username' => 'penname',
                'bio' => 'Alternate voice',
            ])
            ->assertRedirect(route('profile.aliases'));

        $alias = AuthorAlias::query()->where('username', 'penname')->first();
        $this->assertNotNull($alias);
        $this->assertSame('Pen Name', ActiveAuthorAlias::resolve($user)->name);
    }

    public function test_post_is_created_under_active_alias(): void
    {
        $user = User::factory()->create();
        $category = \App\Models\Category::query()->create(['name' => 'Tech', 'slug' => 'tech']);

        $alias = $user->authorAliases()->create([
            'name' => 'Alt Author',
            'username' => 'altauthor',
            'is_primary' => false,
        ]);

        ActiveAuthorAlias::set($user, $alias);

        $this->actingAs($user)->post(route('posts.store'), [
            'title' => 'Alias Story',
            'body' => '<p>Written as alias</p>',
            'category_id' => $category->id,
            'status' => 'published',
        ]);

        $post = Post::query()->where('title', 'Alias Story')->firstOrFail();
        $this->assertSame($alias->id, $post->author_alias_id);
        $this->assertSame('altauthor', $post->authorAlias->username);
    }

    public function test_deleted_alias_profile_is_empty_but_post_remains_visible(): void
    {
        $user = User::factory()->create(['username' => 'mainuser']);
        $alias = $user->authorAliases()->create([
            'name' => 'Retired Name',
            'username' => 'retiredalias',
            'is_primary' => false,
        ]);

        $post = Post::factory()->for($user)->create([
            'author_alias_id' => $alias->id,
            'title' => 'Legacy Story',
            'slug' => 'legacy-story',
        ]);

        $this->actingAs($user)
            ->delete(route('profile.aliases.destroy', $alias))
            ->assertRedirect(route('profile.aliases'));

        $this->get(route('authors.show', 'retiredalias'))
            ->assertOk()
            ->assertSee('no longer available', false)
            ->assertDontSee('Retired Name')
            ->assertDontSee('Legacy Story');

        $this->get(route('posts.show', ['retiredalias', $post->slug]))
            ->assertOk()
            ->assertSee('Legacy Story')
            ->assertSee('Unavailable author', false);
    }

    public function test_cannot_delete_last_alias(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->delete(route('profile.aliases.destroy', $user->primaryAlias()))
            ->assertRedirect(route('profile.aliases'))
            ->assertSessionHasErrors('alias');
    }
}
