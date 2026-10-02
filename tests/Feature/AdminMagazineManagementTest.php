<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Magazine;
use App\Models\Post;
use App\Models\User;
use App\Support\Features;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminMagazineManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Features::seedDefaults();
        Features::set('magazines', true);
    }

    public function test_admin_can_manage_magazines_list(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $owner = User::factory()->create();
        $magazine = Magazine::query()->create([
            'owner_id' => $owner->id,
            'name' => 'Test Magazine',
            'slug' => 'test-magazine',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.magazines.manage'))
            ->assertOk()
            ->assertSee('Test Magazine');
    }

    public function test_admin_can_update_magazine(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $owner = User::factory()->create();
        $magazine = Magazine::query()->create([
            'owner_id' => $owner->id,
            'name' => 'Old Name',
            'slug' => 'old-name',
        ]);

        $this->actingAs($admin)
            ->put(route('admin.magazines.update', $magazine), [
                'name' => 'New Name',
                'description' => 'Updated description',
            ])
            ->assertRedirect(route('admin.magazines.manage'))
            ->assertSessionHas('status', 'magazine-updated');

        $magazine->refresh();
        $this->assertSame('New Name', $magazine->name);
        $this->assertSame('new-name', $magazine->slug);
        $this->assertSame('Updated description', $magazine->description);
    }

    public function test_admin_can_soft_delete_magazine(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $owner = User::factory()->create();
        $magazine = Magazine::query()->create([
            'owner_id' => $owner->id,
            'name' => 'Delete Me',
            'slug' => 'delete-me',
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.magazines.destroy', $magazine))
            ->assertRedirect(route('admin.magazines.manage'))
            ->assertSessionHas('status', 'magazine-deleted');

        $this->assertSoftDeleted($magazine);

        $this->get(route('magazines.show', $magazine))
            ->assertNotFound();
    }

    public function test_admin_can_view_magazine_posts(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $owner = User::factory()->create();
        $magazine = Magazine::query()->create([
            'owner_id' => $owner->id,
            'name' => 'Story Hub',
            'slug' => 'story-hub',
        ]);
        $post = Post::factory()->create([
            'user_id' => $owner->id,
            'magazine_id' => $magazine->id,
            'title' => 'Magazine Story Title',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.magazines.posts', $magazine))
            ->assertOk()
            ->assertSee('Magazine Story Title');
    }

    public function test_super_admin_can_restore_trashed_magazine(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $owner = User::factory()->create();
        $magazine = Magazine::query()->create([
            'owner_id' => $owner->id,
            'name' => 'Restore Me',
            'slug' => 'restore-me',
        ]);
        $magazine->delete();

        $this->actingAs($admin)
            ->post(route('admin.trash.magazines.restore', $magazine->id))
            ->assertRedirect()
            ->assertSessionHas('status', 'magazine-restored');

        $this->assertNull($magazine->fresh()->deleted_at);
    }
}
