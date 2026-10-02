<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocaleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        session()->forget('locale');
        \App\Models\SiteSetting::query()->delete();
        \App\Support\SiteBranding::seedDefaults();
        \App\Support\SiteLocale::setEnabled(['en', 'ua']);
        \App\Support\SiteLocale::setDefault('en');
    }

    public function test_user_can_switch_to_ukrainian(): void
    {
        $this->from('/')
            ->get(route('locale.switch', 'ua'))
            ->assertRedirect('/');

        $this->get('/')
            ->assertOk()
            ->assertSee('Головна', false)
            ->assertSee('Пошук..', false);
    }

    public function test_user_can_switch_back_to_english(): void
    {
        $this->get(route('locale.switch', 'ua'));
        $this->get(route('locale.switch', 'en'))->assertRedirect();

        $this->get('/')
            ->assertOk()
            ->assertSee('Home', false);
    }

    public function test_invalid_locale_returns_not_found(): void
    {
        $this->get(route('locale.switch', 'fr'))->assertNotFound();
    }
}
