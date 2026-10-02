<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use App\Support\AuthorSubdomain;
use App\Support\Features;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorSubdomainTest extends TestCase
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

    public function test_author_profile_is_available_on_subdomain(): void
    {
        $user = User::factory()->create([
            'username' => 'subauthor',
            'name' => 'Sub Author',
        ]);

        $this->withServerVariables(['HTTP_HOST' => 'subauthor.localhost'])
            ->get('http://subauthor.localhost/')
            ->assertOk()
            ->assertSee('Sub Author');
    }

    public function test_published_post_is_available_on_author_subdomain(): void
    {
        $user = User::factory()->create(['username' => 'subauthor']);
        $post = Post::factory()->for($user)->create([
            'title' => 'Subdomain Story',
            'slug' => 'subdomain-story',
        ]);

        $this->withServerVariables(['HTTP_HOST' => 'subauthor.localhost'])
            ->get('http://subauthor.localhost/subdomain-story')
            ->assertOk()
            ->assertSee('Subdomain Story');
    }

    public function test_subdomain_redirects_to_main_site_when_feature_disabled(): void
    {
        Features::set('author_subdomains', false);

        $user = User::factory()->create(['username' => 'subauthor', 'name' => 'Sub Author']);

        $this->withServerVariables(['HTTP_HOST' => 'subauthor.localhost'])
            ->get('http://subauthor.localhost/')
            ->assertRedirect(AuthorSubdomain::mainSiteUrl('@subauthor'));
    }

    public function test_subdomain_post_redirects_to_main_site_when_feature_disabled(): void
    {
        Features::set('author_subdomains', false);

        $user = User::factory()->create(['username' => 'subauthor']);
        $post = Post::factory()->for($user)->create(['slug' => 'subdomain-story']);

        $this->withServerVariables(['HTTP_HOST' => 'subauthor.localhost'])
            ->get('http://subauthor.localhost/subdomain-story')
            ->assertRedirect(AuthorSubdomain::mainSiteUrl('@subauthor/subdomain-story'));
    }

    public function test_links_use_main_site_paths_when_feature_disabled(): void
    {
        Features::set('author_subdomains', false);

        $user = User::factory()->create(['username' => 'pathauthor', 'name' => 'Path Author']);
        $post = Post::factory()->for($user)->create(['slug' => 'path-story', 'title' => 'Path Story']);
        $post->load('authorAlias');

        $this->assertSame(
            route('authors.show', $user->primaryAlias()),
            \App\Support\SiteUrl::author($user->primaryAlias()),
        );
        $this->assertSame(
            route('posts.show', [$user->primaryAlias(), $post->slug]),
            \App\Support\PostUrl::canonical($post),
        );

        $this->get(route('authors.show', $user->primaryAlias()))
            ->assertOk()
            ->assertSee(route('authors.show', $user->primaryAlias()), false)
            ->assertDontSee('pathauthor.localhost', false);
    }

    public function test_main_path_still_works_when_redirect_disabled(): void
    {
        AuthorSubdomain::setRedirect(false);

        $user = User::factory()->create([
            'username' => 'pathauthor',
            'name' => 'Path Author',
        ]);

        $this->get(route('authors.show', $user->primaryAlias()))
            ->assertOk()
            ->assertSee('Path Author');
    }

    public function test_redirects_main_author_path_to_subdomain_when_enabled(): void
    {
        AuthorSubdomain::setRedirect(true);

        $user = User::factory()->create(['username' => 'redirectauthor']);

        $this->get(route('authors.show', $user->primaryAlias()))
            ->assertRedirect(AuthorSubdomain::authorHomeUrl($user->primaryAlias()));
    }

    public function test_redirects_main_post_path_to_subdomain_when_enabled(): void
    {
        AuthorSubdomain::setRedirect(true);

        $user = User::factory()->create(['username' => 'redirectauthor']);
        $post = Post::factory()->for($user)->create(['slug' => 'redirect-story']);
        $post->refresh();

        $this->get(route('posts.show', [$user->primaryAlias(), $post->slug]))
            ->assertRedirect(AuthorSubdomain::postUrl($post));
    }

    public function test_author_profile_canonical_prefers_subdomain(): void
    {
        AuthorSubdomain::setRedirect(false);

        $user = User::factory()->create(['username' => 'canonauthor', 'name' => 'Canon Author']);

        $this->get(route('authors.show', $user->primaryAlias()))
            ->assertOk()
            ->assertSee(AuthorSubdomain::authorHomeUrl($user->primaryAlias()), false);
    }

    public function test_post_page_canonical_prefers_subdomain(): void
    {
        AuthorSubdomain::setRedirect(false);

        $user = User::factory()->create(['username' => 'canonauthor']);
        $post = Post::factory()->for($user)->create(['slug' => 'canon-story']);
        $post->load('authorAlias');

        $this->get(route('posts.show', [$user->primaryAlias(), $post->slug]))
            ->assertOk()
            ->assertSee(AuthorSubdomain::postUrl($post), false);
    }

    public function test_username_is_normalized_on_save(): void
    {
        $user = User::factory()->create(['username' => 'City Reporter']);

        $this->assertSame('city-reporter', $user->username);
        $this->assertSame('city-reporter', $user->primaryAlias()->username);
    }

    public function test_at_path_and_subdomain_use_same_normalized_nickname(): void
    {
        AuthorSubdomain::setRedirect(true);

        $user = User::factory()->create([
            'username' => 'cityreporter',
            'name' => 'City Reporter',
        ]);

        $this->get('/@CityReporter')
            ->assertRedirect(AuthorSubdomain::authorHomeUrl($user->primaryAlias()));

        $this->withServerVariables(['HTTP_HOST' => 'cityreporter.localhost'])
            ->get('http://cityreporter.localhost/')
            ->assertOk()
            ->assertSee('City Reporter');
    }

    public function test_at_path_resolves_spaced_nickname_to_subdomain(): void
    {
        AuthorSubdomain::setRedirect(true);

        $user = User::factory()->create([
            'username' => 'City Reporter',
            'name' => 'City Reporter',
        ]);

        $this->get('/@City Reporter')
            ->assertRedirect('http://city-reporter.localhost/');

        $this->withServerVariables(['HTTP_HOST' => 'city-reporter.localhost'])
            ->get('http://city-reporter.localhost/')
            ->assertOk()
            ->assertSee('City Reporter');
    }

    public function test_subdomain_handles_user_follow_api_on_same_host(): void
    {
        $user = User::factory()->create(['username' => 'subauthor']);
        $follower = User::factory()->create();

        $this->actingAs($follower)
            ->withServerVariables(['HTTP_HOST' => 'subauthor.localhost'])
            ->postJson('http://subauthor.localhost/users/'.$user->id.'/follow')
            ->assertOk()
            ->assertJson(['following' => true]);
    }

    public function test_subdomain_still_redirects_main_site_get_paths(): void
    {
        $user = User::factory()->create(['username' => 'subauthor']);

        $this->withServerVariables(['HTTP_HOST' => 'subauthor.localhost'])
            ->get('http://subauthor.localhost/profile')
            ->assertRedirect('http://localhost/profile');
    }
}
