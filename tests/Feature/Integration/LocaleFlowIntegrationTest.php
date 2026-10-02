<?php

namespace Tests\Feature\Integration;

use App\Models\SiteSetting;
use App\Support\Features;
use App\Support\SiteLocale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Locale switching: session locale → translated UI → switch back to English.
 */
class LocaleFlowIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Features::seedDefaults();
        session()->forget('locale');
        SiteSetting::query()->delete();
        SiteLocale::setEnabled(['en', 'ua']);
        SiteLocale::setDefault('en');
    }

    public function test_locale_switch_translates_home_and_back(): void
    {
        $this->from('/')
            ->get(route('locale.switch', 'ua'))
            ->assertRedirect('/');

        $this->get('/')
            ->assertOk()
            ->assertSee('Головна', false)
            ->assertSee('Пошук..', false);

        $this->get(route('locale.switch', 'en'))->assertRedirect();

        $this->get('/')
            ->assertOk()
            ->assertSee('Home', false);
    }

    public function test_disabled_locale_returns_not_found(): void
    {
        SiteLocale::setEnabled(['en']);
        SiteLocale::setDefault('en');

        $this->get(route('locale.switch', 'ua'))->assertNotFound();
    }
}
