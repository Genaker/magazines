<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Theme;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ThemeTest extends TestCase
{
    use RefreshDatabase;

    private string $themeName = 'phpunit-theme';

    protected function tearDown(): void
    {
        File::deleteDirectory(base_path('templates/'.$this->themeName));
        config(['theme.name' => '']);

        parent::tearDown();
    }

    public function test_theme_name_returns_null_for_default(): void
    {
        config(['theme.name' => 'default']);

        $this->assertNull(Theme::name());
    }

    public function test_invalid_theme_name_is_ignored(): void
    {
        config(['theme.name' => '../escape']);

        $this->assertNull(Theme::name());
        $this->assertNull(Theme::path());
    }

    public function test_theme_overrides_view_with_fallback_for_missing_files(): void
    {
        $themeRoot = base_path('templates/'.$this->themeName);
        File::ensureDirectoryExists($themeRoot.'/partials');

        File::put(
            $themeRoot.'/partials/footer.blade.php',
            '<footer data-theme-override>THEME FOOTER MARKER</footer>',
        );

        config(['theme.name' => $this->themeName]);
        Theme::register();

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('THEME FOOTER MARKER', false);
    }

    public function test_theme_overrides_page_view(): void
    {
        $author = User::factory()->create(['username' => 'themeauthor']);
        $themeRoot = base_path('templates/'.$this->themeName);
        File::ensureDirectoryExists($themeRoot.'/authors');

        File::put(
            $themeRoot.'/authors/show.blade.php',
            '<x-app-layout><p data-theme-override>AUTHOR THEME PAGE</p></x-app-layout>',
        );

        config(['theme.name' => $this->themeName]);
        Theme::register();

        $this->get(route('authors.show', $author))
            ->assertOk()
            ->assertSee('AUTHOR THEME PAGE', false);
    }
}
