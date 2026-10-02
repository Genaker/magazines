<?php

namespace Tests\Feature;

use App\Enums\MagazineSubmissionStatus;
use App\Models\Magazine;
use App\Models\Post;
use App\Models\User;
use App\Support\AuthorSubdomain;
use App\Support\Features;
use App\Support\MagazineSubdomain;
use App\Support\SubdomainSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubdomainSessionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        putenv('SESSION_DOMAIN');
        unset($_ENV['SESSION_DOMAIN'], $_SERVER['SESSION_DOMAIN']);
        config(['session.domain' => null]);

        Features::seedDefaults();
        AuthorSubdomain::setBaseHost('localhost');
    }

    public function test_cookie_domain_is_null_for_localhost_base_due_to_public_suffix_list(): void
    {
        Features::set('magazine_subdomains', true);

        $this->assertNull(SubdomainSession::cookieDomain());
        $this->assertFalse(SubdomainSession::supportsSharedCookies('localhost'));
    }

    public function test_cookie_domain_is_lvh_me_when_base_host_is_lvh_me(): void
    {
        Features::set('magazine_subdomains', true);
        AuthorSubdomain::setBaseHost('lvh.me');

        $this->assertSame('.lvh.me', SubdomainSession::cookieDomain());
        $this->assertTrue(SubdomainSession::supportsSharedCookies('lvh.me'));
    }

    public function test_localhost_session_domain_from_env_is_rejected(): void
    {
        config(['session.domain' => null]);
        putenv('SESSION_DOMAIN=.localhost');
        $_ENV['SESSION_DOMAIN'] = '.localhost';

        $this->assertNull(SubdomainSession::cookieDomain());
    }

    public function test_cookie_domain_is_null_when_subdomains_disabled(): void
    {
        Features::set('author_subdomains', false);
        Features::set('magazine_subdomains', false);
        AuthorSubdomain::setBaseHost('lvh.me');

        $this->assertNull(SubdomainSession::cookieDomain());
    }

    public function test_profile_on_magazine_subdomain_redirects_to_main_site(): void
    {
        Features::set('magazine_subdomains', true);

        $owner = User::factory()->create();
        Magazine::query()->create([
            'owner_id' => $owner->id,
            'name' => 'The Commons',
            'slug' => 'the-commons',
        ]);

        $this->actingAs($owner)
            ->withServerVariables(['HTTP_HOST' => 'the-commons.localhost'])
            ->get('http://the-commons.localhost/profile')
            ->assertRedirect(AuthorSubdomain::mainSiteUrl('/profile'));
    }

    public function test_legacy_localhost_subdomain_redirects_to_lvh_me(): void
    {
        Features::set('magazine_subdomains', true);
        AuthorSubdomain::setBaseHost('lvh.me');
        config(['app.url' => 'http://lvh.me:8888']);

        $this->get('http://the-commons.localhost:8888/')
            ->assertRedirect('http://the-commons.lvh.me:8888/');
    }

    public function test_legacy_localhost_main_site_redirects_to_lvh_me(): void
    {
        AuthorSubdomain::setBaseHost('lvh.me');
        config(['app.url' => 'http://lvh.me:8888']);

        $this->get('http://localhost:8888/discover')
            ->assertRedirect('http://lvh.me:8888/discover');
    }

    public function test_legacy_127001_main_site_redirects_to_lvh_me(): void
    {
        AuthorSubdomain::setBaseHost('lvh.me');
        config(['app.url' => 'http://lvh.me:8888']);

        $this->get('http://127.0.0.1:8888/')
            ->assertRedirect('http://lvh.me:8888/');
    }

    public function test_app_url_is_aligned_to_base_host_when_subdomains_enabled(): void
    {
        Features::set('magazine_subdomains', true);
        AuthorSubdomain::setBaseHost('lvh.me');
        config(['app.url' => 'http://127.0.0.1:8888']);

        SubdomainSession::configureAppUrl();

        $this->assertSame('http://lvh.me:8888', config('app.url'));
    }

    public function test_app_url_resets_to_env_when_base_host_cleared(): void
    {
        Features::set('author_subdomains', true);
        AuthorSubdomain::setBaseHost('custom.test');
        config(['app.url' => 'http://127.0.0.1:8000']);

        SubdomainSession::configureAppUrl();
        $this->assertSame('http://custom.test:8000', config('app.url'));

        AuthorSubdomain::setBaseHost(null);
        SubdomainSession::configureAppUrl();

        $this->assertSame('http://127.0.0.1:8000', config('app.url'));
        $this->assertSame('http://127.0.0.1:8000/admin/settings', route('admin.settings.edit'));
    }

    /** Regression: subdomain views must load session (ServeMagazineSubdomain short-circuits web middleware). */
    public function test_login_on_main_site_persists_on_magazine_subdomain_pages(): void
    {
        Features::set('magazine_subdomains', true);
        AuthorSubdomain::setBaseHost('lvh.me');
        config([
            'app.url' => 'http://lvh.me:8888',
            'session.domain' => '.lvh.me',
        ]);
        SubdomainSession::configure();

        $user = User::factory()->create(['email_verified_at' => now()]);
        $magazine = Magazine::query()->create([
            'owner_id' => $user->id,
            'name' => 'The Commons',
            'slug' => 'the-commons',
        ]);
        Post::factory()->for($user)->create([
            'title' => 'Commons Story',
            'slug' => 'commons-story',
            'magazine_id' => $magazine->id,
            'magazine_submission_status' => MagazineSubmissionStatus::Approved,
        ]);

        $this->withServerVariables(['HTTP_HOST' => 'lvh.me'])
            ->post('http://lvh.me:8888/login', [
                'email' => $user->email,
                'password' => 'password',
            ])
            ->assertRedirect();

        $this->assertAuthenticated();

        $this->withServerVariables(['HTTP_HOST' => 'the-commons.lvh.me'])
            ->get('http://the-commons.lvh.me:8888/')
            ->assertOk()
            ->assertSee(__('app.profile'))
            ->assertDontSee(__('app.login'));

        // Post URLs have no matching web route — session must start in SubdomainRequestSession.
        $this->withServerVariables(['HTTP_HOST' => 'the-commons.lvh.me'])
            ->get('http://the-commons.lvh.me:8888/commons-story')
            ->assertOk()
            ->assertSee('Commons Story')
            ->assertSee(__('app.profile'))
            ->assertDontSee(__('app.login'));
    }
}
