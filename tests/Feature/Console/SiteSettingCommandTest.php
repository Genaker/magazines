<?php

namespace Tests\Feature\Console;

use App\Models\SiteSetting;
use App\Support\AuthorSubdomain;
use App\Support\Features;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteSettingCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Features::seedDefaults();
    }

    public function test_list_shows_features_and_site_settings(): void
    {
        SiteSetting::setValue('site_name', 'CLI Blog');

        $this->artisan('site:setting list')
            ->assertSuccessful()
            ->expectsOutputToContain('Features:')
            ->expectsOutputToContain('author_subdomains:')
            ->expectsOutputToContain('Site settings:')
            ->expectsOutputToContain('site_name: CLI Blog');
    }

    public function test_set_and_get_feature_flag(): void
    {
        $this->artisan('site:setting set author_subdomains on')
            ->assertSuccessful()
            ->expectsOutputToContain('Set feature author_subdomains to on.');

        $this->assertTrue(Features::enabled('author_subdomains'));

        $this->artisan('site:setting get author_subdomains')
            ->assertSuccessful()
            ->expectsOutputToContain('author_subdomains: on');
    }

    public function test_set_author_subdomain_settings(): void
    {
        $this->artisan('site:setting set author_subdomains on')
            ->assertSuccessful();

        $this->artisan('site:setting set author_subdomain_base_host localhost')
            ->assertSuccessful();

        $this->assertSame('localhost', SiteSetting::getValue('author_subdomain_base_host'));

        $this->artisan('site:setting set author_subdomain_redirect on')
            ->assertSuccessful();

        $this->assertTrue(AuthorSubdomain::redirectEnabled());
    }

    public function test_unset_feature_restores_default(): void
    {
        Features::set('author_subdomains', true);

        $this->artisan('site:setting unset author_subdomains')
            ->assertSuccessful()
            ->expectsOutputToContain('Removed feature override for author_subdomains');

        $this->assertFalse(Features::enabled('author_subdomains'));
    }

    public function test_set_generic_site_setting(): void
    {
        $this->artisan('site:setting set site_name "My Site"')
            ->assertSuccessful();

        $this->assertSame('My Site', SiteSetting::getValue('site_name'));
    }

    public function test_set_requires_value(): void
    {
        $this->artisan('site:setting set author_subdomains')
            ->assertFailed()
            ->expectsOutputToContain('Provide a value for set');
    }
}
