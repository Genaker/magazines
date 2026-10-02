<?php

namespace Tests\Feature;

use App\Enums\MagazineSubmissionStatus;
use App\Enums\PostStatus;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Magazine;
use App\Models\Post;
use App\Models\User;
use App\Support\Features;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MagazineCategoriesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Features::seedDefaults();
    }

    public function test_magazine_show_lists_category_sub_header_and_filters_posts(): void
    {
        $magazine = Magazine::query()->create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'City Weekly',
            'slug' => 'city-weekly',
        ]);
        $essays = Category::query()->create([
            'magazine_id' => $magazine->id,
            'name' => 'Essays',
            'slug' => 'city-weekly-essays',
        ]);
        $news = Category::query()->create([
            'magazine_id' => $magazine->id,
            'name' => 'News',
            'slug' => 'city-weekly-news',
        ]);
        $author = User::factory()->create(['email_verified_at' => now()]);
        $alias = $author->authorAliases()->first();

        Post::factory()->create([
            'user_id' => $author->id,
            'author_alias_id' => $alias->id,
            'magazine_id' => $magazine->id,
            'category_id' => $essays->id,
            'title' => 'Essay in magazine',
            'status' => PostStatus::Published,
            'magazine_submission_status' => MagazineSubmissionStatus::Approved,
            'published_at' => now(),
        ]);

        Post::factory()->create([
            'user_id' => $author->id,
            'author_alias_id' => $alias->id,
            'magazine_id' => $magazine->id,
            'category_id' => $news->id,
            'title' => 'News in magazine',
            'status' => PostStatus::Published,
            'magazine_submission_status' => MagazineSubmissionStatus::Approved,
            'published_at' => now(),
        ]);

        $this->get(route('magazines.show', $magazine))
            ->assertOk()
            ->assertSee('Essays')
            ->assertSee('News')
            ->assertSee('Essay in magazine')
            ->assertSee('News in magazine');

        $this->get(route('magazines.show', [$magazine, 'category' => $essays->slug]))
            ->assertOk()
            ->assertSee('Essay in magazine')
            ->assertDontSee('News in magazine');
    }

    public function test_post_store_accepts_magazine_category_when_magazine_selected(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        Category::query()->create(['name' => 'Culture', 'slug' => 'culture']);
        $magazine = Magazine::query()->create([
            'owner_id' => $user->id,
            'name' => 'Test Mag',
            'slug' => 'test-mag',
        ]);
        $magazineCategory = Category::query()->create([
            'magazine_id' => $magazine->id,
            'name' => 'Profiles',
            'slug' => 'profiles',
        ]);

        $this->actingAs($user)
            ->post(route('posts.store'), [
                'title' => 'Magazine profile story',
                'body' => '<p>Hello</p>',
                'category_id' => $magazineCategory->id,
                'magazine_id' => $magazine->id,
                'status' => 'draft',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('posts', [
            'title' => 'Magazine profile story',
            'category_id' => $magazineCategory->id,
            'magazine_id' => $magazine->id,
        ]);
    }

    public function test_post_store_rejects_magazine_category_without_magazine(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        Category::query()->create(['name' => 'Culture', 'slug' => 'culture']);
        $magazine = Magazine::query()->create([
            'owner_id' => $user->id,
            'name' => 'Other Mag',
            'slug' => 'other-mag',
        ]);
        $magazineCategory = Category::query()->create([
            'magazine_id' => $magazine->id,
            'name' => 'Profiles',
            'slug' => 'profiles',
        ]);

        $this->actingAs($user)
            ->post(route('posts.store'), [
                'title' => 'Invalid category scope',
                'body' => '<p>Hello</p>',
                'category_id' => $magazineCategory->id,
                'status' => 'draft',
            ])
            ->assertSessionHasErrors('category_id');
    }

    public function test_admin_can_create_magazine_category(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $magazine = Magazine::query()->create([
            'owner_id' => $admin->id,
            'name' => 'Admin Mag',
            'slug' => 'admin-mag',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.categories.store'), [
                'name' => 'Politics',
                'magazine_id' => $magazine->id,
            ])
            ->assertRedirect(route('admin.categories.index'));

        $this->assertDatabaseHas('categories', [
            'name' => 'Politics',
            'magazine_id' => $magazine->id,
        ]);
    }

    public function test_write_form_lists_site_and_magazine_categories_separately(): void
    {
        Features::set('magazines', true);

        $user = User::factory()->create(['email_verified_at' => now()]);
        Category::query()->create(['name' => 'Global Culture', 'slug' => 'global-culture']);
        $magazine = Magazine::query()->create([
            'owner_id' => $user->id,
            'name' => 'City Weekly',
            'slug' => 'city-weekly',
        ]);
        Category::query()->create([
            'magazine_id' => $magazine->id,
            'name' => 'Magazine Profiles',
            'slug' => 'magazine-profiles',
        ]);

        $this->actingAs($user)
            ->get(route('posts.create'))
            ->assertOk()
            ->assertSee('Global Culture', false)
            ->assertSee('Magazine Profiles', false)
            ->assertSee('data-magazine-id="'.$magazine->id.'"', false)
            ->assertSee('data-magazine-id=""', false);
    }

    public function test_site_navigation_excludes_magazine_categories(): void
    {
        Category::query()->create(['name' => 'Technology', 'slug' => 'technology']);
        $magazine = Magazine::query()->create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Hidden Mag',
            'slug' => 'hidden-mag',
        ]);
        Category::query()->create([
            'magazine_id' => $magazine->id,
            'name' => 'Hidden Beat',
            'slug' => 'hidden-beat',
        ]);

        $navigation = Category::cachedForNavigation();

        $this->assertTrue($navigation->contains(fn (Category $category) => $category->slug === 'technology'));
        $this->assertFalse($navigation->contains(fn (Category $category) => $category->slug === 'hidden-beat'));
    }
}
