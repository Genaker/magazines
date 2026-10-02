<?php

namespace Tests\Feature\Integration;

use App\Enums\MagazineSubmissionStatus;
use App\Enums\PostStatus;
use App\Enums\UserRole;
use App\Models\Magazine;
use App\Models\Post;
use App\Models\User;
use App\Support\EntityCache;
use App\Support\Features;
use App\Support\MagazineNavSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Magazine menu: manual pins, drag reorder, auto weekly activity, mode switching.
 */
class MagazineNavFlowIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Features::seedDefaults();
        Features::set('magazines', true);
        MagazineNavSettings::save(10, MagazineNavSettings::MODE_MANUAL);
    }

    public function test_admin_pin_flow_updates_public_navigation(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $magazine = Magazine::query()->create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Nav Flow Mag',
            'slug' => 'nav-flow-mag',
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('Nav Flow Mag', false);

        $this->actingAs($admin)
            ->patch(route('admin.magazines.nav', $magazine), [
                'pinned' => true,
                'nav_sort_order' => 1,
            ])
            ->assertRedirect();

        EntityCache::flushTag(config('entity-cache.tags.magazines'));

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Nav Flow Mag', false);
    }

    public function test_admin_reorder_flow_updates_public_navigation_order(): void
    {
        EntityCache::flushTag(config('entity-cache.tags.magazines'));

        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $owner = User::factory()->create();

        $first = Magazine::query()->create([
            'owner_id' => $owner->id,
            'name' => 'First In Nav',
            'slug' => 'first-in-nav',
            'nav_pinned_at' => now(),
            'nav_sort_order' => 0,
        ]);

        $second = Magazine::query()->create([
            'owner_id' => $owner->id,
            'name' => 'Second In Nav',
            'slug' => 'second-in-nav',
            'nav_pinned_at' => now(),
            'nav_sort_order' => 1,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSeeInOrder(['First In Nav', 'Second In Nav'], false);

        $this->actingAs($admin)
            ->patch(route('admin.magazines.nav.reorder'), [
                'magazine_ids' => [$second->id, $first->id],
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'magazine-nav-reordered');

        EntityCache::flushTag(config('entity-cache.tags.magazines'));

        $this->get(route('home'))
            ->assertOk()
            ->assertSeeInOrder(['Second In Nav', 'First In Nav'], false);
    }

    public function test_auto_mode_weekly_activity_flow_updates_public_navigation(): void
    {
        MagazineNavSettings::save(2, MagazineNavSettings::MODE_AUTO);
        EntityCache::flushTag(config('entity-cache.tags.magazines'));

        $owner = User::factory()->create();

        $quiet = Magazine::query()->create([
            'owner_id' => $owner->id,
            'name' => 'Quiet Weekly Mag',
            'slug' => 'quiet-weekly-mag',
        ]);

        $active = Magazine::query()->create([
            'owner_id' => $owner->id,
            'name' => 'Active Weekly Mag',
            'slug' => 'active-weekly-mag',
        ]);

        Post::factory()->count(2)->create([
            'magazine_id' => $quiet->id,
            'status' => PostStatus::Published,
            'magazine_submission_status' => MagazineSubmissionStatus::Approved,
            'published_at' => now()->subDays(12),
        ]);

        Post::factory()->create([
            'magazine_id' => $active->id,
            'status' => PostStatus::Published,
            'magazine_submission_status' => MagazineSubmissionStatus::Approved,
            'published_at' => now()->subDays(1),
        ]);

        EntityCache::flushTag(config('entity-cache.tags.magazines'));

        $this->get(route('home'))
            ->assertOk()
            ->assertSeeInOrder(['Active Weekly Mag', 'Quiet Weekly Mag'], false);
    }

    public function test_switching_to_auto_mode_ignores_pinned_magazines_in_public_navigation(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $owner = User::factory()->create();

        $pinnedOnly = Magazine::query()->create([
            'owner_id' => $owner->id,
            'name' => 'Pinned Only Nav',
            'slug' => 'pinned-only-nav',
            'nav_pinned_at' => now(),
            'nav_sort_order' => 0,
        ]);

        $active = Magazine::query()->create([
            'owner_id' => $owner->id,
            'name' => 'Auto Active Nav',
            'slug' => 'auto-active-nav',
        ]);

        Post::factory()->create([
            'magazine_id' => $active->id,
            'status' => PostStatus::Published,
            'magazine_submission_status' => MagazineSubmissionStatus::Approved,
            'published_at' => now()->subDay(),
        ]);

        EntityCache::flushTag(config('entity-cache.tags.magazines'));

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Pinned Only Nav', false)
            ->assertDontSee('Auto Active Nav', false);

        $this->actingAs($admin)
            ->patch(route('admin.magazines.settings'), [
                'magazines_nav_limit' => 1,
                'magazines_nav_mode' => MagazineNavSettings::MODE_AUTO,
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'magazine-nav-settings-updated');

        EntityCache::flushTag(config('entity-cache.tags.magazines'));

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Auto Active Nav', false)
            ->assertDontSee('Pinned Only Nav', false);

        $pinnedOnly->refresh();
        $this->assertNotNull($pinnedOnly->nav_pinned_at);
    }

    public function test_admin_settings_manual_mode_only_shows_pinned_magazines(): void
    {
        MagazineNavSettings::save(1, MagazineNavSettings::MODE_MANUAL);
        EntityCache::flushTag(config('entity-cache.tags.magazines'));

        $owner = User::factory()->create();

        Magazine::query()->create([
            'owner_id' => $owner->id,
            'name' => 'Shown Manual Mag',
            'slug' => 'shown-manual-mag',
            'nav_pinned_at' => now(),
            'nav_sort_order' => 0,
        ]);

        Magazine::query()->create([
            'owner_id' => $owner->id,
            'name' => 'Hidden Manual Mag',
            'slug' => 'hidden-manual-mag',
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Shown Manual Mag', false)
            ->assertDontSee('Hidden Manual Mag', false);
    }
}
