<?php

namespace Tests\Feature\Integration;

use App\Models\User;
use App\Support\AuthorSubdomain;
use App\Support\Features;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Follow/subscribe API on author subdomains without cross-origin redirects.
 */
class AuthorSubdomainSocialIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Features::seedDefaults();
        Features::set('author_subdomains', true);
        config(['app.url' => 'http://localhost']);
        AuthorSubdomain::setBaseHost('localhost');
    }

    public function test_subdomain_follow_and_unfollow_stays_on_same_host(): void
    {
        $author = User::factory()->create(['username' => 'subsocial']);
        $reader = User::factory()->create();

        $this->actingAs($reader)
            ->withServerVariables(['HTTP_HOST' => 'subsocial.localhost'])
            ->postJson('http://subsocial.localhost/users/'.$author->id.'/follow')
            ->assertOk()
            ->assertJson(['following' => true]);

        $this->actingAs($reader)
            ->withServerVariables(['HTTP_HOST' => 'subsocial.localhost'])
            ->postJson('http://subsocial.localhost/users/'.$author->id.'/follow')
            ->assertOk()
            ->assertJson(['following' => false]);
    }

    public function test_subdomain_self_follow_works_on_same_host(): void
    {
        $user = User::factory()->create(['username' => 'subself']);

        $this->actingAs($user)
            ->withServerVariables(['HTTP_HOST' => 'subself.localhost'])
            ->postJson('http://subself.localhost/users/'.$user->id.'/follow')
            ->assertOk()
            ->assertJson(['following' => true]);

        $this->assertTrue($user->following()->where('following_id', $user->id)->exists());
    }

    public function test_subdomain_subscribe_api_stays_on_same_host(): void
    {
        $author = User::factory()->create(['username' => 'subsubscribe']);
        $reader = User::factory()->create();

        $this->actingAs($reader)
            ->withServerVariables(['HTTP_HOST' => 'subsubscribe.localhost'])
            ->postJson('http://subsubscribe.localhost/users/'.$author->id.'/subscribe')
            ->assertOk()
            ->assertJson(['subscribed' => true]);
    }

    public function test_subdomain_get_profile_still_redirects_to_main_site(): void
    {
        User::factory()->create(['username' => 'subredirect']);

        $this->withServerVariables(['HTTP_HOST' => 'subredirect.localhost'])
            ->get('http://subredirect.localhost/profile')
            ->assertRedirect(AuthorSubdomain::mainSiteUrl('/profile'));
    }
}
