<?php

namespace Tests\Feature\Integration;

use App\Models\Category;
use App\Models\User;
use App\Support\Features;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Auth chain: register → PIN verifies + login → publish first post.
 */
class AuthFlowIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Features::seedDefaults();
        config(['app.debug' => true]);
    }

    public function test_register_verify_and_publish_first_post(): void
    {
        $response = $this->post('/register', [
            'name' => 'New Writer',
            'username' => 'newwriter',
            'email' => 'newwriter@integration.test',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertRedirectsToRegistrationCodeStep($response, 'newwriter@integration.test');

        $user = User::query()->where('email', 'newwriter@integration.test')->firstOrFail();
        $this->assertGuest();

        $this->completeRegistrationLogin('newwriter@integration.test');

        $this->assertAuthenticatedAs($user);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());

        $category = Category::query()->create(['name' => 'General', 'slug' => 'general']);

        $this->post(route('posts.store'), [
            'title' => 'My First Integration Post',
            'body' => '<p>Hello from a new account.</p>',
            'category_id' => $category->id,
            'status' => 'published',
        ])->assertRedirect();

        $this->assertDatabaseHas('posts', [
            'user_id' => $user->id,
            'title' => 'My First Integration Post',
        ]);
    }
}
