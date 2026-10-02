<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebManifestTest extends TestCase
{
    use RefreshDatabase;

    public function test_manifest_is_available_with_pwa_fields(): void
    {
        $response = $this->get(route('manifest'));

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/manifest+json');

        $response->assertJsonFragment([
            'display' => 'standalone',
            'start_url' => '/',
            'scope' => '/',
        ]);

        $response->assertJsonStructure([
            'name',
            'short_name',
            'icons' => [
                ['src', 'sizes', 'type'],
            ],
        ]);
    }

    public function test_service_worker_file_exists_in_public_directory(): void
    {
        $this->assertFileExists(public_path('sw.js'));
    }
}
