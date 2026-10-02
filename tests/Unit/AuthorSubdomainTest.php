<?php

namespace Tests\Unit;

use App\Support\AuthorSubdomain;
use App\Support\Features;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
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
    }

    public function test_base_host_is_detected_from_app_url_when_not_configured(): void
    {
        config(['app.url' => 'https://magazines.example']);

        $this->assertSame('magazines.example', AuthorSubdomain::baseHost());
        $this->assertTrue(AuthorSubdomain::usesAutoDetectedBaseHost());
    }

    public function test_admin_override_takes_precedence_over_app_url(): void
    {
        config(['app.url' => 'https://magazines.example']);
        AuthorSubdomain::setBaseHost('localhost');

        $this->assertSame('localhost', AuthorSubdomain::baseHost());
        $this->assertFalse(AuthorSubdomain::usesAutoDetectedBaseHost());
    }

    public function test_username_from_request_parses_subdomain_host(): void
    {
        $request = Request::create('http://demoauthor.localhost/posts', 'GET');
        $request->headers->set('HOST', 'demoauthor.localhost');

        $this->assertSame('demoauthor', AuthorSubdomain::usernameFromRequest($request));
    }

    public function test_main_site_host_returns_null_username(): void
    {
        $request = Request::create('http://localhost/', 'GET');
        $request->headers->set('HOST', 'localhost');

        $this->assertNull(AuthorSubdomain::usernameFromRequest($request));
    }

    public function test_reserved_subdomain_is_ignored(): void
    {
        AuthorSubdomain::setBaseHost('example.com');

        $request = Request::create('http://www.example.com/', 'GET');
        $request->headers->set('HOST', 'www.example.com');

        $this->assertNull(AuthorSubdomain::usernameFromRequest($request));
    }

    public function test_build_subdomain_url_uses_app_url_scheme_and_port(): void
    {
        config(['app.url' => 'http://localhost:9888']);

        $this->assertSame(
            'http://demoauthor.localhost:9888/my-story',
            AuthorSubdomain::buildSubdomainUrl('demoauthor', '/my-story'),
        );
    }
}
