<?php

namespace Tests\Feature\Integration;

use App\Enums\MagazineSubmissionStatus;
use App\Enums\PostStatus;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Magazine;
use App\Models\Post;
use App\Models\User;
use App\Support\EntityCache;
use App\Support\Features;
use App\Support\MagazineNavSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Admin magazine management: edit, posts list, soft-delete, trash restore, public visibility.
 */
class AdminMagazineManagementFlowIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Features::seedDefaults();
        Features::set('magazines', true);
    }

    public function test_admin_magazine_lifecycle_edit_posts_trash_and_restore(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $category = Category::query()->create(['name' => 'Culture', 'slug' => 'culture']);

        $this->actingAs($owner)->post(route('magazines.store'), [
            'name' => 'Lifecycle Weekly',
            'description' => 'Original magazine description.',
        ])->assertRedirect();

        $magazine = Magazine::query()->where('name', 'Lifecycle Weekly')->firstOrFail();
        $publicShowUrl = route('magazines.show', $magazine);

        $this->post(route('logout'));

        $this->get(route('magazines.index'))
            ->assertOk()
            ->assertSee('Lifecycle Weekly');

        $this->get($publicShowUrl)
            ->assertOk()
            ->assertSee('Original magazine description.');

        $this->actingAs($owner)->post(route('posts.store'), [
            'title' => 'Lifecycle Magazine Story',
            'body' => '<p>Published in the magazine.</p>',
            'category_id' => $category->id,
            'magazine_id' => $magazine->id,
            'status' => 'draft',
        ])->assertRedirect();

        $post = Post::query()->where('title', 'Lifecycle Magazine Story')->firstOrFail();

        $this->actingAs($owner)->post(route('magazines.posts.submit', [$magazine, $post]))
            ->assertRedirect();

        $this->actingAs($owner)->post(route('magazines.submissions.approve', [$magazine, $post]))
            ->assertRedirect();

        $post->refresh();
        $this->assertSame(PostStatus::Published, $post->status);
        $this->assertSame(MagazineSubmissionStatus::Approved, $post->magazine_submission_status);

        $this->actingAs($admin)->get(route('admin.magazines.manage'))
            ->assertOk()
            ->assertSee('Lifecycle Weekly');

        $this->actingAs($admin)->get(route('admin.magazines.posts', $magazine))
            ->assertOk()
            ->assertSee('Lifecycle Magazine Story')
            ->assertSee(route('admin.posts.edit', $post), false);

        $this->actingAs($admin)->from(route('admin.magazines.edit', $magazine))
            ->put(route('admin.magazines.update', $magazine), [
                'name' => 'Lifecycle Weekly Renamed',
                'description' => 'Admin updated description.',
            ])
            ->assertRedirect(route('admin.magazines.manage'))
            ->assertSessionHas('status', 'magazine-updated');

        $magazine->refresh();
        $this->assertSame('lifecycle-weekly-renamed', $magazine->slug);
        $publicShowUrl = route('magazines.show', $magazine);

        $this->post(route('logout'));

        $this->get($publicShowUrl)
            ->assertOk()
            ->assertSee('Lifecycle Weekly Renamed')
            ->assertSee('Admin updated description.')
            ->assertSee('Lifecycle Magazine Story');

        $this->actingAs($admin)->from(route('admin.magazines.manage'))
            ->delete(route('admin.magazines.destroy', $magazine))
            ->assertRedirect(route('admin.magazines.manage'))
            ->assertSessionHas('status', 'magazine-deleted');

        $this->assertSoftDeleted('magazines', ['id' => $magazine->id]);

        $this->post(route('logout'));

        $this->get($publicShowUrl)->assertNotFound();

        $this->get(route('magazines.index'))
            ->assertOk()
            ->assertDontSee('Lifecycle Weekly Renamed');

        $this->actingAs($admin)->get(route('admin.trash.index'))
            ->assertOk()
            ->assertSee('Lifecycle Weekly Renamed');

        $this->actingAs($admin)->from(route('admin.trash.index'))
            ->post(route('admin.trash.magazines.restore', $magazine->id))
            ->assertRedirect(route('admin.trash.index'))
            ->assertSessionHas('status', 'magazine-restored');

        $this->assertNull($magazine->fresh()->deleted_at);

        EntityCache::flushTag(config('entity-cache.tags.magazines'));

        $this->post(route('logout'));

        $this->get($publicShowUrl)
            ->assertOk()
            ->assertSee('Lifecycle Weekly Renamed')
            ->assertSee('Lifecycle Magazine Story');
    }

    public function test_admin_soft_delete_clears_nav_pinning(): void
    {
        MagazineNavSettings::save(10, MagazineNavSettings::MODE_MANUAL);
        EntityCache::flushTag(config('entity-cache.tags.magazines'));

        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $owner = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($owner)->post(route('magazines.store'), [
            'name' => 'Pinned Lifecycle Mag',
            'description' => 'Will be pinned then trashed.',
        ])->assertRedirect();

        $magazine = Magazine::query()->where('name', 'Pinned Lifecycle Mag')->firstOrFail();

        $this->actingAs($admin)
            ->patch(route('admin.magazines.nav', $magazine), [
                'pinned' => true,
                'nav_sort_order' => 0,
            ])
            ->assertRedirect();

        EntityCache::flushTag(config('entity-cache.tags.magazines'));

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Pinned Lifecycle Mag', false);

        $this->actingAs($admin)->from(route('admin.magazines.manage'))
            ->delete(route('admin.magazines.destroy', $magazine))
            ->assertRedirect(route('admin.magazines.manage'));

        EntityCache::flushTag(config('entity-cache.tags.magazines'));

        $magazine->refresh();
        $this->assertSoftDeleted($magazine);
        $this->assertNull($magazine->nav_pinned_at);

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('Pinned Lifecycle Mag', false);
    }

    public function test_super_admin_can_permanently_delete_magazine_from_trash(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $owner = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($owner)->post(route('magazines.store'), [
            'name' => 'Forever Gone Mag',
            'description' => 'Will be force deleted.',
        ])->assertRedirect();

        $magazine = Magazine::query()->where('name', 'Forever Gone Mag')->firstOrFail();

        $this->actingAs($admin)->delete(route('admin.magazines.destroy', $magazine))
            ->assertRedirect(route('admin.magazines.manage'));

        $this->actingAs($admin)->from(route('admin.trash.index'))
            ->delete(route('admin.trash.magazines.force-delete', $magazine->id))
            ->assertRedirect(route('admin.trash.index'))
            ->assertSessionHas('status', 'magazine-permanently-deleted');

        $this->assertDatabaseMissing('magazines', ['id' => $magazine->id, 'deleted_at' => null]);
        $this->assertNull(Magazine::withTrashed()->find($magazine->id));
    }
}
