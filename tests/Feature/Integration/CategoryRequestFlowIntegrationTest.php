<?php

namespace Tests\Feature\Integration;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\User;
use App\Support\Features;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Category requests: user requests → admin approves → category usable for new posts.
 */
class CategoryRequestFlowIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Features::seedDefaults();
    }

    public function test_category_request_approval_enables_posting(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $writer = User::factory()->create();

        $this->actingAs($writer)->post(route('category-requests.store'), [
            'name' => 'Integration Design',
            'reason' => 'We need a design category for UI content.',
        ])->assertRedirect(route('category-requests.mine'));

        $request = $writer->categoryRequests()->firstOrFail();

        $this->actingAs($admin)->post(route('admin.category-requests.approve', $request))
            ->assertRedirect();

        $category = Category::query()->where('slug', 'integration-design')->first()
            ?? Category::query()->where('name', 'Integration Design')->firstOrFail();

        $this->actingAs($writer)->post(route('posts.store'), [
            'title' => 'Design Category Story',
            'body' => '<p>First post in new category.</p>',
            'category_id' => $category->id,
            'status' => 'published',
        ])->assertRedirect();

        $this->get(route('categories.show', $category))
            ->assertOk()
            ->assertSee('Design Category Story');
    }
}
