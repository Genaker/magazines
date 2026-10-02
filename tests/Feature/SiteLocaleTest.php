<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\SiteSetting;
use App\Models\User;
use App\Support\SiteLocale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsAdminSettingsPayload;
use Tests\TestCase;

class SiteLocaleTest extends TestCase
{
    use BuildsAdminSettingsPayload;
    use RefreshDatabase;

    public function test_super_admin_can_set_single_language(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);

        $this->actingAs($admin)
            ->put(route('admin.settings.update'), $this->settingsPayload([
                'locales_enabled' => ['en'],
                'locale_default' => 'en',
            ]))
            ->assertRedirect(route('admin.settings.edit'));

        $this->assertSame(['en'], SiteLocale::enabled());
        $this->assertSame('en', SiteLocale::default());
        $this->assertFalse(SiteLocale::isSwitchable());
    }

    public function test_single_language_hides_switcher_and_ignores_session_locale(): void
    {
        SiteLocale::setEnabled(['en']);
        SiteLocale::setDefault('en');

        $this->get(route('locale.switch', 'ua'));
        $this->get('/')
            ->assertOk()
            ->assertSee('Home', false)
            ->assertDontSee('href="'.route('locale.switch', 'ua').'"', false);
    }

    public function test_disabled_locale_switch_returns_not_found(): void
    {
        SiteLocale::setEnabled(['en']);
        SiteLocale::setDefault('en');

        $this->get(route('locale.switch', 'ua'))->assertNotFound();
    }

    public function test_default_must_be_one_of_enabled_languages(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);

        $this->actingAs($admin)
            ->from(route('admin.settings.edit'))
            ->put(route('admin.settings.update'), $this->settingsPayload([
                'locales_enabled' => ['en'],
                'locale_default' => 'ua',
            ]))
            ->assertRedirect(route('admin.settings.edit'))
            ->assertSessionHasErrors('locale_default');
    }

    public function test_legacy_uk_locale_code_is_normalized_to_ua(): void
    {
        SiteSetting::setValue('site_locales_enabled', json_encode(['en', 'uk']));
        SiteSetting::setValue('site_locale_default', 'uk');

        $this->assertSame(['en', 'ua'], SiteLocale::enabled());
        $this->assertSame('ua', SiteLocale::default());

        $this->from('/')
            ->get(route('locale.switch', 'uk'))
            ->assertRedirect('/');

        $this->get('/')
            ->assertOk()
            ->assertSee('Головна', false);
    }

    /** @param array<string, mixed> $overrides */
    private function settingsPayload(array $overrides = []): array
    {
        return $this->adminSettingsPayload($overrides);
    }
}
