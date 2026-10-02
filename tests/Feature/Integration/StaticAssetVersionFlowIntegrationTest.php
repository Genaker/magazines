<?php

namespace Tests\Feature\Integration;

use App\Models\Post;
use App\Support\StaticAssetVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Static asset versioning: bump command → versioned URLs in public pages.
 */
class StaticAssetVersionFlowIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private ?string $originalVersionFile = null;

    protected function setUp(): void
    {
        parent::setUp();

        $path = StaticAssetVersion::path();
        if (is_file($path)) {
            $this->originalVersionFile = (string) file_get_contents($path);
        }
    }

    protected function tearDown(): void
    {
        $path = StaticAssetVersion::path();

        if ($this->originalVersionFile === null) {
            if (is_file($path)) {
                unlink($path);
            }
        } else {
            file_put_contents($path, $this->originalVersionFile);
        }

        parent::tearDown();
    }

    public function test_bumped_version_appears_on_post_page_and_helper(): void
    {
        file_put_contents(StaticAssetVersion::path(), "99\n");
        $this->assertStringContainsString('v=99', static_asset('js/post.js'));

        $post = Post::factory()->create();
        $post->load('authorAlias');

        $this->get(route('posts.show', [$post->authorAlias, $post->slug]))
            ->assertOk()
            ->assertSee('js/post.js?v=99', false);

        $this->artisan('static-version:bump')
            ->expectsOutput('Static asset version bumped to 100.')
            ->assertSuccessful();

        $this->assertSame('100', StaticAssetVersion::get());

        $this->get(route('posts.show', [$post->authorAlias, $post->slug]))
            ->assertOk()
            ->assertSee('js/post.js?v=100', false);
    }
}
