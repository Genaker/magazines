<?php

namespace Tests\Feature\Integration;

use App\Models\SiteSetting;
use App\Support\Features;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PWA manifest reflects site branding and is linked from HTML layout.
 */
class PwaManifestFlowIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Features::seedDefaults();
    }

    public function test_branded_manifest_is_linked_from_home_page(): void
    {
        SiteSetting::setValue('site_name', 'Integration PWA');
        SiteSetting::setValue('site_tagline', 'Stories on every device');
        SiteSetting::setValue('site_theme_color', '#445566');
        SiteSetting::setValue('site_background_color', '#fafafa');

        $this->get(route('manifest'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/manifest+json')
            ->assertJson([
                'name' => 'Integration PWA',
                'short_name' => 'Integration PWA',
                'description' => 'Stories on every device',
                'theme_color' => '#445566',
                'background_color' => '#fafafa',
                'display' => 'standalone',
                'start_url' => '/',
            ])
            ->assertJsonStructure([
                'icons' => [
                    ['src', 'sizes', 'type'],
                ],
            ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('rel="manifest"', false)
            ->assertSee(route('manifest'), false);

        $this->assertFileExists(public_path('sw.js'));
    }
}
