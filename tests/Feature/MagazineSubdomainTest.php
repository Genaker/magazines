<?php

namespace Tests\Feature;

use App\Enums\MagazineSubmissionStatus;
use App\Models\Magazine;
use App\Models\Post;
use App\Models\User;
use App\Support\AuthorSubdomain;
use App\Support\Features;
use App\Support\MagazineSubdomain;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MagazineSubdomainTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Features::seedDefaults();
        Features::set('magazine_subdomains', true);
        MagazineSubdomain::setRedirect(true);
        config(['app.url' => 'http://localhost']);
        AuthorSubdomain::setBaseHost('localhost');
    }

    public function test_magazine_page_is_available_on_subdomain(): void
    {
        $owner = User::factory()->create();
        $magazine = Magazine::query()->create([
            'owner_id' => $owner->id,
            'name' => 'The Commons',
            'slug' => 'the-commons',
            'description' => 'Community stories',
        ]);

        $this->withServerVariables(['HTTP_HOST' => 'the-commons.localhost'])
            ->get('http://the-commons.localhost/')
            ->assertOk()
            ->assertSee('The Commons');
    }

    public function test_authenticated_user_is_logged_in_on_magazine_subdomain(): void
    {
        $user = User::factory()->create(['name' => 'Logged In User']);
        $magazine = Magazine::query()->create([
            'owner_id' => $user->id,
            'name' => 'The Commons',
            'slug' => 'the-commons',
        ]);

        $this->actingAs($user)
            ->withServerVariables(['HTTP_HOST' => 'the-commons.localhost'])
            ->get('http://the-commons.localhost/')
            ->assertOk()
            ->assertSee('Logged In User', false)
            ->assertDontSee(__('app.login'));
    }

    public function test_approved_magazine_post_is_available_on_magazine_subdomain(): void
    {
        $owner = User::factory()->create();
        $magazine = Magazine::query()->create([
            'owner_id' => $owner->id,
            'name' => 'The Commons',
            'slug' => 'the-commons',
        ]);
        $post = Post::factory()->for($owner)->create([
            'title' => 'Commons Story',
            'slug' => 'commons-story',
            'magazine_id' => $magazine->id,
            'magazine_submission_status' => MagazineSubmissionStatus::Approved,
        ]);

        $this->withServerVariables(['HTTP_HOST' => 'the-commons.localhost'])
            ->get('http://the-commons.localhost/commons-story')
            ->assertOk()
            ->assertSee('Commons Story');
    }

    public function test_redirects_magazine_path_to_subdomain(): void
    {
        $owner = User::factory()->create();
        $magazine = Magazine::query()->create([
            'owner_id' => $owner->id,
            'name' => 'The Commons',
            'slug' => 'the-commons',
        ]);

        $this->get(route('magazines.show', $magazine))
            ->assertRedirect(MagazineSubdomain::magazineUrl($magazine));
    }

    public function test_author_post_path_redirects_to_magazine_subdomain_for_magazine_posts(): void
    {
        Features::set('author_subdomains', true);

        $owner = User::factory()->create();
        $magazine = Magazine::query()->create([
            'owner_id' => $owner->id,
            'name' => 'The Commons',
            'slug' => 'the-commons',
        ]);
        $post = Post::factory()->for($owner)->create([
            'slug' => 'commons-story',
            'magazine_id' => $magazine->id,
            'magazine_submission_status' => MagazineSubmissionStatus::Approved,
        ]);
        $post->load('authorAlias');

        $this->get(route('posts.show', [$post->authorAlias, $post->slug]))
            ->assertRedirect(MagazineSubdomain::postUrl($post));
    }

    public function test_author_subdomain_redirects_magazine_post_to_magazine_url(): void
    {
        Features::set('author_subdomains', true);

        $owner = User::factory()->create(['username' => 'magwriter']);
        $magazine = Magazine::query()->create([
            'owner_id' => $owner->id,
            'name' => 'The Commons',
            'slug' => 'the-commons',
        ]);
        $post = Post::factory()->for($owner)->create([
            'slug' => 'commons-story',
            'magazine_id' => $magazine->id,
            'magazine_submission_status' => MagazineSubmissionStatus::Approved,
        ]);

        $this->withServerVariables(['HTTP_HOST' => 'magwriter.localhost'])
            ->get('http://magwriter.localhost/commons-story')
            ->assertRedirect(MagazineSubdomain::postUrl($post));
    }

    public function test_magazine_page_links_author_to_author_subdomain_not_magazine_host(): void
    {
        Features::set('author_subdomains', true);

        $owner = User::factory()->create(['username' => 'demoauthor', 'name' => 'Demo Author']);
        $magazine = Magazine::query()->create([
            'owner_id' => $owner->id,
            'name' => 'The Commons',
            'slug' => 'the-commons',
        ]);
        Post::factory()->for($owner)->create([
            'title' => 'Commons Story',
            'slug' => 'commons-story',
            'magazine_id' => $magazine->id,
            'magazine_submission_status' => MagazineSubmissionStatus::Approved,
        ]);

        $this->withServerVariables(['HTTP_HOST' => 'the-commons.localhost'])
            ->get('http://the-commons.localhost/')
            ->assertOk()
            ->assertSee('http://demoauthor.localhost', false)
            ->assertDontSee('the-commons.localhost/@demoauthor', false);
    }

    public function test_authors_path_on_magazine_subdomain_redirects_to_main_site(): void
    {
        $owner = User::factory()->create();
        Magazine::query()->create([
            'owner_id' => $owner->id,
            'name' => 'The Commons',
            'slug' => 'the-commons',
        ]);

        $this->withServerVariables(['HTTP_HOST' => 'the-commons.localhost'])
            ->get('http://the-commons.localhost/authors')
            ->assertRedirect(AuthorSubdomain::mainSiteUrl('/authors'));
    }

    public function test_author_path_on_magazine_subdomain_redirects_to_author_subdomain(): void
    {
        Features::set('author_subdomains', true);

        $owner = User::factory()->create();
        $magazine = Magazine::query()->create([
            'owner_id' => $owner->id,
            'name' => 'The Commons',
            'slug' => 'the-commons',
        ]);
        User::factory()->create(['username' => 'demoauthor']);

        $this->withServerVariables(['HTTP_HOST' => 'the-commons.localhost'])
            ->get('http://the-commons.localhost/@demoauthor')
            ->assertRedirect(AuthorSubdomain::authorHomeUrl(
                \App\Models\AuthorAlias::query()->where('username', 'demoauthor')->firstOrFail()
            ));
    }

    public function test_magazine_post_more_from_author_uses_magazine_urls_not_author_path(): void
    {
        Features::set('author_subdomains', true);

        $owner = User::factory()->create(['username' => 'demoauthor', 'name' => 'Demo Author']);
        $magazine = Magazine::query()->create([
            'owner_id' => $owner->id,
            'name' => 'The Commons',
            'slug' => 'the-commons',
        ]);

        $current = Post::factory()->for($owner)->create([
            'title' => 'Current Story',
            'slug' => 'current-story',
            'magazine_id' => $magazine->id,
            'magazine_submission_status' => MagazineSubmissionStatus::Approved,
        ]);

        Post::factory()->for($owner)->create([
            'title' => 'Older Story',
            'slug' => 'older-story',
            'magazine_id' => $magazine->id,
            'magazine_submission_status' => MagazineSubmissionStatus::Approved,
            'published_at' => now()->subDay(),
        ]);

        $this->withServerVariables(['HTTP_HOST' => 'the-commons.localhost'])
            ->get('http://the-commons.localhost/current-story')
            ->assertOk()
            ->assertSee('More from', false)
            ->assertSee('Older Story', false)
            ->assertSee('http://the-commons.localhost/older-story', false)
            ->assertSee('http://demoauthor.localhost', false)
            ->assertDontSee('the-commons.localhost/@demoauthor', false);
    }

    public function test_subdomain_returns_not_found_when_feature_disabled(): void
    {
        Features::set('magazine_subdomains', false);

        $owner = User::factory()->create();
        Magazine::query()->create([
            'owner_id' => $owner->id,
            'name' => 'The Commons',
            'slug' => 'the-commons',
        ]);

        $this->withServerVariables(['HTTP_HOST' => 'the-commons.localhost'])
            ->get('http://the-commons.localhost/')
            ->assertNotFound();
    }

    public function test_non_default_tenant_magazine_subdomain_works_when_multi_tenancy_disabled(): void
    {
        Features::set('multi_tenancy', false);
        AuthorSubdomain::setBaseHost('lvh.me');
        config(['app.url' => 'http://lvh.me']);

        $tenant = \App\Models\Tenant::query()->create([
            'name' => 'Tenant 1',
            'slug' => 'tenant1',
            'status' => 'active',
        ]);

        $owner = User::factory()->create(['tenant_id' => $tenant->id]);
        $magazine = Magazine::withoutGlobalScope('tenant')->create([
            'tenant_id' => $tenant->id,
            'owner_id' => $owner->id,
            'name' => 'Tenant One Weekly',
            'slug' => 'tenant1-weekly',
            'description' => 'Stories from tenant 1',
        ]);

        $this->withServerVariables(['HTTP_HOST' => 'tenant1-weekly.lvh.me'])
            ->get('http://tenant1-weekly.lvh.me/')
            ->assertOk()
            ->assertSee('Tenant One Weekly');
    }
}
