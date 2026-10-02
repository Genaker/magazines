<?php

namespace Tests\Unit;

use App\Support\AuthorSubdomain;
use App\Support\Features;
use App\Support\SiteUrl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteUrlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Features::seedDefaults();
        AuthorSubdomain::setBaseHost('localhost');
        config(['app.url' => 'http://localhost:8888']);
    }

    public function test_main_route_builds_absolute_main_site_url(): void
    {
        $this->assertSame(
            'http://localhost:8888/authors',
            SiteUrl::mainRoute('authors.index'),
        );
    }
}
