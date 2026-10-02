<?php

namespace Tests\Feature\Integration;

use App\Models\AuthorAlias;
use App\Models\Category;
use App\Models\User;
use App\Support\ActiveAuthorAlias;
use App\Support\Features;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Author aliases: create pen name → switch → publish under alias URL.
 */
class AuthorAliasFlowIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Features::seedDefaults();
    }

    public function test_create_alias_switch_and_publish_under_pen_name(): void
    {
        $user = User::factory()->create(['username' => 'mainwriter']);
        $category = Category::query()->create(['name' => 'Essays', 'slug' => 'essays']);

        $this->actingAs($user)->post(route('profile.aliases.store'), [
            'name' => 'Pen Name',
            'username' => 'penintegration',
            'bio' => 'Alternate voice for integration tests.',
        ])->assertRedirect(route('profile.aliases'));

        $alias = AuthorAlias::query()->where('username', 'penintegration')->firstOrFail();
        $this->assertSame($alias->id, ActiveAuthorAlias::resolve($user)->id);

        $this->actingAs($user)->post(route('posts.store'), [
            'title' => 'Alias Integration Story',
            'body' => '<p>Published under pen name.</p>',
            'category_id' => $category->id,
            'status' => 'published',
        ])->assertRedirect();

        $this->post(route('logout'));

        $this->get(route('posts.show', [$alias, 'alias-integration-story']))
            ->assertOk()
            ->assertSee('Alias Integration Story');

        $this->get(route('authors.show', $alias))
            ->assertOk()
            ->assertSee('Alias Integration Story');
    }
}
