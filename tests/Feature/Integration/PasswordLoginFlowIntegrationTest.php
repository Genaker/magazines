<?php

namespace Tests\Feature\Integration;

use App\Models\Category;
use App\Models\User;
use App\Support\Features;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Password login: sign in → publish → public post URL.
 */
class PasswordLoginFlowIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Features::seedDefaults();
    }

    public function test_password_login_then_publish_post(): void
    {
        $user = User::factory()->create([
            'email' => 'writer@integration.test',
            'email_verified_at' => now(),
        ]);
        $category = Category::query()->create(['name' => 'Essays', 'slug' => 'essays']);

        $this->post(route('logout'));

        $this->post('/login', [
            'email' => 'writer@integration.test',
            'password' => 'password',
        ])->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($user);

        $this->post(route('posts.store'), [
            'title' => 'Password Login Integration Post',
            'body' => '<p>Signed in with password.</p>',
            'category_id' => $category->id,
            'status' => 'published',
        ])->assertRedirect();

        $this->post(route('logout'));

        $this->get(route('posts.show', [$user->primaryAlias(), 'password-login-integration-post']))
            ->assertOk()
            ->assertSee('Password Login Integration Post');
    }
}
