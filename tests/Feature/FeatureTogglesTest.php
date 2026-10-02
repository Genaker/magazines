<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use App\Support\Features;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsAdminSettingsPayload;
use Tests\TestCase;

class FeatureTogglesTest extends TestCase
{
    use BuildsAdminSettingsPayload;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Features::seedDefaults();
    }

    public function test_super_admin_can_disable_registration(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);

        $this->actingAs($admin)
            ->put(route('admin.settings.update'), $this->adminSettingsPayload([
                'site_name' => 'Magazines',
                'site_tagline' => 'Test',
                'locales_enabled' => ['en'],
                'locale_default' => 'en',
                'features' => [
                    'registration' => '0',
                    'registration_invites' => '0',
                ],
            ]))
            ->assertRedirect(route('admin.settings.edit'));

        $this->assertFalse(Features::enabled('registration'));
        $this->assertFalse(Features::enabled('registration_invites'));

        $this->post(route('logout'));

        $this->get(route('register'))->assertNotFound();
    }

    public function test_closed_registration_with_invites_allows_register_route(): void
    {
        Features::set('registration', false);
        Features::set('registration_invites', true);

        $this->get(route('register'))->assertOk();
    }

    public function test_disabled_magazines_route_returns_not_found(): void
    {
        Features::set('magazines', false);

        $this->get(route('magazines.index'))->assertNotFound();
    }

    public function test_enabled_magazines_route_is_accessible(): void
    {
        Features::set('magazines', true);

        $this->get(route('magazines.index'))->assertOk();
    }

    public function test_magic_link_login_redirects_to_password_when_disabled(): void
    {
        Features::set('magic_link_login', false);

        $this->get(route('login'))->assertRedirect(route('login.password'));
    }
}
