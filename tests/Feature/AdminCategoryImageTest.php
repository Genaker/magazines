<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\User;
use App\Services\ImageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminCategoryImageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        config(['media.disk' => 'public']);
    }

    public function test_admin_can_create_category_with_image(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);

        $this->actingAs($admin)->post(route('admin.categories.store'), [
            'name' => 'Visual Culture',
            'description' => 'Stories about art and design.',
            'image' => UploadedFile::fake()->image('culture.jpg', 800, 600),
        ])->assertRedirect(route('admin.categories.index'));

        $category = Category::query()->where('name', 'Visual Culture')->firstOrFail();

        $this->assertNotNull($category->image);
        $this->assertIsArray($category->image_variants);
        $this->assertNotNull($category->imageUrl());
        Storage::disk('public')->assertExists($category->image);
    }

    public function test_admin_can_replace_category_image_on_update(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $images = app(ImageService::class);

        $processed = $images->processUpload(
            UploadedFile::fake()->image('old.jpg', 600, 400),
            'categories',
        );

        $category = Category::query()->create([
            'name' => 'Design',
            'slug' => 'design',
            'image' => $processed['path'],
            'image_variants' => $processed['variants'],
        ]);

        $oldPath = $category->image;

        $this->actingAs($admin)->put(route('admin.categories.update', $category), [
            'name' => 'Design',
            'image' => UploadedFile::fake()->image('new.jpg', 900, 600),
        ])->assertRedirect(route('admin.categories.index'));

        $category->refresh();

        $this->assertNotSame($oldPath, $category->image);
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($category->image);
    }
}
