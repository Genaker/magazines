<?php

namespace Tests\Feature\Integration;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use App\Support\EntityCache;
use App\Support\Features;
use App\Support\SiteBranding;
use App\Support\Theme;
use App\Support\ViewHooks;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithTheme;
use Tests\TestCase;

/**
 * End-to-end flows with templates/{theme}/ view overrides active.
 */
class ThemeOverrideFlowIntegrationTest extends TestCase
{
    use InteractsWithTheme;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Features::seedDefaults();
        EntityCache::flushStore();
    }

    protected function tearDown(): void
    {
        $this->removeTheme();

        parent::tearDown();
    }

    public function test_guest_publishing_journey_sees_themed_footer_on_html_pages(): void
    {
        $this->activateTheme();
        $this->installThemeFooterOverride();

        $author = User::factory()->create(['username' => 'themeintegration']);
        $category = Category::query()->create(['name' => 'Theme', 'slug' => 'theme']);

        $this->actingAs($author)
            ->post(route('posts.store'), [
                'title' => 'Theme Integration Story',
                'body' => '<p>Theme integration body copy.</p>',
                'category_id' => $category->id,
                'status' => 'published',
            ])
            ->assertRedirect();

        $post = Post::query()->where('user_id', $author->id)->firstOrFail();
        $showUrl = route('posts.show', [$author->primaryAlias(), $post->slug]);

        $this->post(route('logout'));

        $marker = $this->themeFooterMarker();

        $this->get(route('home'))
            ->assertOk()
            ->assertSee($marker, false)
            ->assertSee('Theme Integration Story');

        $this->get($showUrl)
            ->assertOk()
            ->assertSee($marker, false)
            ->assertSee('Theme Integration Story')
            ->assertSee('Theme integration body copy', false);

        $this->get(route('authors.show', $author->primaryAlias()))
            ->assertOk()
            ->assertSee($marker, false)
            ->assertSee('Theme Integration Story');

        $this->get(route('search', ['q' => 'Theme Integration']))
            ->assertOk()
            ->assertSee($marker, false)
            ->assertSee('Theme Integration Story');
    }

    public function test_theme_post_card_partial_override_on_author_profile(): void
    {
        $this->activateTheme();
        $this->installThemePostCardOverride();

        $author = User::factory()->create(['username' => 'themedcards']);
        Post::factory()->for($author)->create([
            'title' => 'Themed Card Story',
            'slug' => 'themed-card-story',
            'status' => 'published',
        ]);

        $this->get(route('authors.show', $author->primaryAlias()))
            ->assertOk()
            ->assertSee('data-theme-post-card', false)
            ->assertSee('Themed Card Story');
    }

    public function test_theme_override_works_alongside_view_hooks_on_post_page(): void
    {
        $this->activateTheme();
        $this->installThemeFooterOverride();
        ViewHooks::register('post.after_content', 'partials.hooks.post-banner');

        $author = User::factory()->create();
        $post = Post::factory()->for($author)->create([
            'author_alias_id' => $author->primaryAlias()->id,
            'title' => 'Hook And Theme Story',
            'status' => 'published',
        ]);

        $this->get(route('posts.show', [$author->primaryAlias(), $post->slug]))
            ->assertOk()
            ->assertSee($this->themeFooterMarker(), false)
            ->assertSee('Hook And Theme Story banner', false)
            ->assertSee('Hook And Theme Story');
    }

    public function test_magic_link_login_flow_works_with_themed_footer(): void
    {
        config(['app.debug' => true]);
        $this->activateTheme();
        $this->installThemeFooterOverride();

        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->from(route('login'))
            ->post(route('login.magic.send'), ['email' => $user->email])
            ->assertRedirect()
            ->assertSessionHas('dev_login_code');

        $code = session('dev_login_code');

        $this->get(route('login'))
            ->assertOk()
            ->assertSee($this->themeFooterMarker(), false);

        $this->post(route('login.magic.verify.code'), [
            'email' => $user->email,
            'code' => $code,
        ])->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_missing_theme_directory_falls_back_to_default_footer(): void
    {
        config(['theme.name' => 'missing-theme-folder']);
        Theme::register();

        $this->get(route('home'))
            ->assertOk()
            ->assertSee(SiteBranding::get('footer_tagline'))
            ->assertDontSee($this->themeFooterMarker(), false);
    }

    public function test_theme_feed_section_override_on_home_page(): void
    {
        $this->activateTheme();

        $this->writeThemeView('partials/feed-section.blade.php', <<<'BLADE'
<section data-theme-feed-section>
    <h2>{{ $title }}</h2>
    @foreach ($posts as $post)
        <p>{{ $post->title }}</p>
    @endforeach
</section>
BLADE);

        $author = User::factory()->create();
        Post::factory()->for($author)->create([
            'title' => 'Feed Section Theme Story',
            'status' => 'published',
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('data-theme-feed-section', false)
            ->assertSee('Feed Section Theme Story');
    }

    public function test_theme_available_lists_installed_integration_theme(): void
    {
        $this->activateTheme();

        $this->assertContains($this->themeName, Theme::available());
        $this->assertSame(base_path('templates/'.$this->themeName), Theme::path());
    }

    public function test_theme_post_show_page_override(): void
    {
        $this->activateTheme();
        $this->installThemePostShowOverride();

        $author = User::factory()->create();
        $post = Post::factory()->for($author)->create([
            'author_alias_id' => $author->primaryAlias()->id,
            'title' => 'Full Page Theme Story',
            'body' => '<p>Theme body content.</p>',
            'status' => 'published',
        ]);

        $this->get(route('posts.show', [$author->primaryAlias(), $post->slug]))
            ->assertOk()
            ->assertSee('data-theme-post-page', false)
            ->assertSee('Full Page Theme Story')
            ->assertSee('Theme body content', false);
    }

    public function test_theme_post_author_partial_on_post_page(): void
    {
        $this->activateTheme();
        $this->installThemePostAuthorOverride();

        $author = User::factory()->create(['name' => 'Theme Author Name']);
        $post = Post::factory()->for($author)->create([
            'author_alias_id' => $author->primaryAlias()->id,
            'title' => 'Post Author Theme Story',
            'status' => 'published',
        ]);

        $this->get(route('posts.show', [$author->primaryAlias(), $post->slug]))
            ->assertOk()
            ->assertSee('data-theme-post-author', false)
            ->assertSee('Theme Author Name');
    }

    public function test_theme_search_results_use_themed_post_card(): void
    {
        $this->activateTheme();
        $this->installThemePostCardOverride();

        $author = User::factory()->create();
        Post::factory()->for($author)->create([
            'title' => 'Search Theme Card Story',
            'status' => 'published',
        ]);

        $this->get(route('search', ['q' => 'Search Theme Card']))
            ->assertOk()
            ->assertSee('data-theme-post-card', false)
            ->assertSee('Search Theme Card Story');
    }

    public function test_theme_category_page_shows_themed_footer(): void
    {
        $this->activateTheme();
        $this->installThemeFooterOverride();

        $category = Category::query()->create(['name' => 'Theme Category', 'slug' => 'theme-category']);
        $author = User::factory()->create();
        Post::factory()->for($author)->create([
            'category_id' => $category->id,
            'title' => 'Category Theme Story',
            'status' => 'published',
        ]);

        $this->get(route('categories.show', $category))
            ->assertOk()
            ->assertSee($this->themeFooterMarker(), false)
            ->assertSee('Theme Category')
            ->assertSee('Category Theme Story');
    }

    public function test_password_login_page_uses_themed_footer(): void
    {
        $this->activateTheme();
        $this->installThemeFooterOverride();

        $this->get(route('login.password'))
            ->assertOk()
            ->assertSee($this->themeFooterMarker(), false);
    }

    public function test_theme_partial_override_falls_back_for_unoverridden_partials(): void
    {
        $this->activateTheme();
        $this->installThemeFooterOverride();

        $author = User::factory()->create();
        Post::factory()->for($author)->create([
            'title' => 'Default Card Story',
            'status' => 'published',
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee($this->themeFooterMarker(), false)
            ->assertSee('Default Card Story')
            ->assertDontSee('data-theme-post-card', false);
    }
}
