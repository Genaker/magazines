<?php

namespace Tests\Feature;

use App\Support\StaticAssetVersion;
use Tests\TestCase;

class StaticAssetVersionTest extends TestCase
{
    private ?string $originalContents = null;

    protected function setUp(): void
    {
        parent::setUp();

        $path = StaticAssetVersion::path();

        if (is_file($path)) {
            $this->originalContents = (string) file_get_contents($path);
        }
    }

    protected function tearDown(): void
    {
        $path = StaticAssetVersion::path();

        if ($this->originalContents === null) {
            if (is_file($path)) {
                unlink($path);
            }
        } else {
            file_put_contents($path, $this->originalContents);
        }

        parent::tearDown();
    }

    public function test_static_asset_helper_appends_version_query(): void
    {
        file_put_contents(StaticAssetVersion::path(), "42\n");

        $this->assertStringContainsString('v=42', static_asset('js/post.js'));
    }

    public function test_static_version_bump_command_increments_file(): void
    {
        file_put_contents(StaticAssetVersion::path(), "7\n");

        $this->artisan('static-version:bump')
            ->expectsOutput('Static asset version bumped to 8.')
            ->assertSuccessful();

        $this->assertSame('8', StaticAssetVersion::get());
    }

    public function test_static_version_show_option_prints_current_value(): void
    {
        file_put_contents(StaticAssetVersion::path(), "15\n");

        $this->artisan('static-version:bump --show')
            ->expectsOutput('15')
            ->assertSuccessful();
    }
}
