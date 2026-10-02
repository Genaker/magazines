<?php

namespace Tests\Feature;

use App\Models\Magazine;
use App\Models\Post;
use App\Models\User;
use App\Enums\UserRole;
use App\Enums\MagazineSubmissionStatus;
use App\Enums\PostStatus;
use App\Support\EntityCache;
use App\Support\Features;
use App\Support\MagazineNavSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MagazineNavPinTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Features::seedDefaults();
        Features::set('magazines', true);
    }

    public function test_navigation_only_shows_admin_pinned_magazines_in_manual_mode(): void
    {
        MagazineNavSettings::save(10, MagazineNavSettings::MODE_MANUAL);

        $owner = User::factory()->create();

        $pinned = Magazine::query()->create([
            'owner_id' => $owner->id,
            'name' => 'Pinned Mag',
            'slug' => 'pinned-mag',
            'nav_pinned_at' => now(),
            'nav_sort_order' => 1,
        ]);

        Magazine::query()->create([
            'owner_id' => $owner->id,
            'name' => 'Hidden Mag',
            'slug' => 'hidden-mag',
        ]);

        $navigation = Magazine::cachedForNavigation();

        $this->assertCount(1, $navigation);
        $this->assertTrue($navigation->first()->is($pinned));
    }

    public function test_auto_mode_orders_magazines_by_recent_weekly_activity(): void
    {
        MagazineNavSettings::save(2, MagazineNavSettings::MODE_AUTO);
        EntityCache::flushTag(config('entity-cache.tags.magazines'));

        $owner = User::factory()->create();

        $quietMag = Magazine::query()->create([
            'owner_id' => $owner->id,
            'name' => 'Quiet Mag',
            'slug' => 'quiet-mag',
        ]);

        $activeMag = Magazine::query()->create([
            'owner_id' => $owner->id,
            'name' => 'Active Mag',
            'slug' => 'active-mag',
        ]);

        Post::factory()->count(3)->create([
            'magazine_id' => $quietMag->id,
            'status' => PostStatus::Published,
            'magazine_submission_status' => MagazineSubmissionStatus::Approved,
            'published_at' => now()->subDays(10),
        ]);

        Post::factory()->create([
            'magazine_id' => $activeMag->id,
            'status' => PostStatus::Published,
            'magazine_submission_status' => MagazineSubmissionStatus::Approved,
            'published_at' => now()->subDays(2),
        ]);

        EntityCache::flushTag(config('entity-cache.tags.magazines'));

        $navigation = Magazine::cachedForNavigation();

        $this->assertCount(2, $navigation);
        $this->assertSame('Active Mag', $navigation->first()->name);
        $this->assertSame('Quiet Mag', $navigation->last()->name);
    }

    public function test_auto_mode_shows_top_magazines_up_to_limit(): void
    {
        MagazineNavSettings::save(2, MagazineNavSettings::MODE_AUTO);
        EntityCache::flushTag(config('entity-cache.tags.magazines'));

        $owner = User::factory()->create();

        Magazine::query()->create([
            'owner_id' => $owner->id,
            'name' => 'Alpha',
            'slug' => 'alpha',
        ]);

        Magazine::query()->create([
            'owner_id' => $owner->id,
            'name' => 'Beta',
            'slug' => 'beta',
        ]);

        Magazine::query()->create([
            'owner_id' => $owner->id,
            'name' => 'Gamma',
            'slug' => 'gamma',
        ]);

        $payload = Magazine::cachedNavigationPayload();

        $this->assertCount(2, $payload['items']);
        $this->assertSame(1, $payload['more_count']);
    }

    public function test_more_count_shown_in_navigation_when_truncated(): void
    {
        MagazineNavSettings::save(1, MagazineNavSettings::MODE_AUTO);
        EntityCache::flushTag(config('entity-cache.tags.magazines'));

        $owner = User::factory()->create();

        Magazine::query()->create([
            'owner_id' => $owner->id,
            'name' => 'First Mag',
            'slug' => 'first-mag',
        ]);

        Magazine::query()->create([
            'owner_id' => $owner->id,
            'name' => 'Second Mag',
            'slug' => 'second-mag',
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('1 more available', false);
    }

    public function test_admin_can_update_nav_settings(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)
            ->patch(route('admin.magazines.settings'), [
                'magazines_nav_limit' => 5,
                'magazines_nav_mode' => MagazineNavSettings::MODE_MANUAL,
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'magazine-nav-settings-updated');

        $this->assertSame(5, MagazineNavSettings::limit());
        $this->assertTrue(MagazineNavSettings::isManualMode());
    }

    public function test_auto_mode_ignores_pinned_magazines(): void
    {
        MagazineNavSettings::save(1, MagazineNavSettings::MODE_AUTO);
        EntityCache::flushTag(config('entity-cache.tags.magazines'));

        $owner = User::factory()->create();

        Magazine::query()->create([
            'owner_id' => $owner->id,
            'name' => 'Pinned Only',
            'slug' => 'pinned-only',
            'nav_pinned_at' => now(),
            'nav_sort_order' => 0,
        ]);

        Magazine::query()->create([
            'owner_id' => $owner->id,
            'name' => 'Top By Stories',
            'slug' => 'top-by-stories',
        ]);

        Post::factory()->create([
            'magazine_id' => Magazine::query()->where('slug', 'top-by-stories')->value('id'),
            'status' => PostStatus::Published,
            'magazine_submission_status' => MagazineSubmissionStatus::Approved,
        ]);

        EntityCache::flushTag(config('entity-cache.tags.magazines'));

        $navigation = Magazine::cachedForNavigation();

        $this->assertCount(1, $navigation);
        $this->assertSame('Top By Stories', $navigation->first()->name);
    }

    public function test_admin_can_pin_and_unpin_magazine_for_navigation(): void
    {
        MagazineNavSettings::save(10, MagazineNavSettings::MODE_MANUAL);
        EntityCache::flushTag(config('entity-cache.tags.magazines'));

        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $magazine = Magazine::query()->create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Menu Mag',
            'slug' => 'menu-mag',
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.magazines.nav', $magazine), [
                'pinned' => true,
                'nav_sort_order' => 2,
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'magazine-nav-updated');

        $magazine->refresh();
        $this->assertNotNull($magazine->nav_pinned_at);
        $this->assertSame(2, $magazine->nav_sort_order);
        $this->assertTrue(Magazine::cachedForNavigation()->contains(fn (Magazine $item) => $item->is($magazine)));

        EntityCache::flushTag(config('entity-cache.tags.magazines'));

        $this->actingAs($admin)
            ->patch(route('admin.magazines.nav', $magazine), [
                'pinned' => false,
            ])
            ->assertRedirect();

        $magazine->refresh();
        $this->assertNull($magazine->nav_pinned_at);
        $this->assertFalse(Magazine::cachedForNavigation()->contains(fn (Magazine $item) => $item->is($magazine)));
    }

    public function test_admin_can_reorder_pinned_magazines_in_navigation(): void
    {
        MagazineNavSettings::save(10, MagazineNavSettings::MODE_MANUAL);
        EntityCache::flushTag(config('entity-cache.tags.magazines'));

        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $owner = User::factory()->create();

        $first = Magazine::query()->create([
            'owner_id' => $owner->id,
            'name' => 'First Mag',
            'slug' => 'first-mag',
            'nav_pinned_at' => now(),
            'nav_sort_order' => 0,
        ]);

        $second = Magazine::query()->create([
            'owner_id' => $owner->id,
            'name' => 'Second Mag',
            'slug' => 'second-mag',
            'nav_pinned_at' => now(),
            'nav_sort_order' => 1,
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.magazines.nav.reorder'), [
                'magazine_ids' => [$second->id, $first->id],
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'magazine-nav-reordered');

        $first->refresh();
        $second->refresh();

        $this->assertSame(1, $first->nav_sort_order);
        $this->assertSame(0, $second->nav_sort_order);

        EntityCache::flushTag(config('entity-cache.tags.magazines'));

        $navigation = Magazine::cachedForNavigation();

        $this->assertTrue($navigation->first()->is($second));
        $this->assertTrue($navigation->last()->is($first));
    }

    public function test_pinned_magazines_page_shows_magazine_in_navigation_dropdown(): void
    {
        MagazineNavSettings::save(10, MagazineNavSettings::MODE_MANUAL);
        EntityCache::flushTag(config('entity-cache.tags.magazines'));

        $owner = User::factory()->create();

        Magazine::query()->create([
            'owner_id' => $owner->id,
            'name' => 'Featured Weekly',
            'slug' => 'featured-weekly',
            'nav_pinned_at' => now(),
            'nav_sort_order' => 1,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Featured Weekly', false)
            ->assertSee(__('app.see_all_magazines'), false);
    }
}
