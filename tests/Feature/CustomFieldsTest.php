<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use App\Support\CustomFieldSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomFieldsTest extends TestCase
{
    use RefreshDatabase;

    private function seedPostFields(): void
    {
        CustomFieldSchema::save(CustomFieldSchema::CONTEXT_POST, [
            [
                'key' => 'series',
                'label' => 'Series',
                'type' => CustomFieldSchema::TYPE_TEXT,
                'help' => '',
                'required' => false,
            ],
            [
                'key' => 'source',
                'label' => 'Source',
                'type' => CustomFieldSchema::TYPE_TEXT,
                'help' => '',
                'required' => false,
            ],
        ]);
    }

    private function seedUserFields(): void
    {
        CustomFieldSchema::save(CustomFieldSchema::CONTEXT_USER, [
            [
                'key' => 'pronouns',
                'label' => 'Pronouns',
                'type' => CustomFieldSchema::TYPE_TEXT,
                'help' => 'How you would like to be addressed.',
                'required' => false,
            ],
        ]);
    }

    public function test_author_can_save_custom_fields_on_post(): void
    {
        $this->seedPostFields();

        $author = User::factory()->create();
        $category = Category::query()->create(['name' => 'Essays', 'slug' => 'essays']);
        $post = Post::factory()->for($author)->create(['slug' => 'custom-fields-post']);

        $this->actingAs($author)
            ->put(route('posts.update', $post), [
                'type' => 'article',
                'title' => $post->title,
                'category_id' => $category->id,
                'body' => $post->body,
                'status' => 'published',
                'tags' => '',
                'custom_fields' => [
                    'series' => 'part-1',
                    'source' => 'newsletter',
                ],
            ])
            ->assertRedirect();

        $post->refresh();

        $this->assertSame([
            'series' => 'part-1',
            'source' => 'newsletter',
        ], $post->custom_fields);
    }

    public function test_user_can_save_custom_fields_on_profile(): void
    {
        $this->seedUserFields();

        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('profile.edit'))
            ->patch(route('profile.update'), [
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'allow_comments' => '1',
                'custom_fields' => [
                    'pronouns' => 'they/them',
                ],
            ])
            ->assertRedirect(route('profile.edit'));

        $this->assertSame(['pronouns' => 'they/them'], $user->fresh()->custom_fields);
    }

    public function test_profile_shows_configured_fields_not_json_editor(): void
    {
        $this->seedUserFields();

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Pronouns')
            ->assertDontSee('Custom fields (JSON)');
    }

    public function test_invalid_custom_field_value_is_rejected(): void
    {
        CustomFieldSchema::save(CustomFieldSchema::CONTEXT_POST, [
            [
                'key' => 'source_url',
                'label' => 'Source URL',
                'type' => CustomFieldSchema::TYPE_URL,
                'help' => '',
                'required' => true,
            ],
        ]);

        $author = User::factory()->create();
        $post = Post::factory()->for($author)->create();

        $this->actingAs($author)
            ->from(route('posts.edit', $post))
            ->put(route('posts.update', $post), [
                'type' => 'article',
                'title' => $post->title,
                'category_id' => $post->category_id,
                'body' => $post->body,
                'status' => 'published',
                'tags' => '',
                'custom_fields' => [
                    'source_url' => 'not-a-url',
                ],
            ])
            ->assertInvalid(['custom_fields.source_url']);
    }

    public function test_admin_can_configure_custom_fields(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)
            ->put(route('admin.custom-fields.update'), [
                'custom_field_definitions' => [
                    'user' => [
                        [
                            'key' => 'location',
                            'label' => 'Location',
                            'type' => 'text',
                            'help' => 'City or region',
                            'required' => '0',
                        ],
                    ],
                ],
            ])
            ->assertRedirect(route('admin.custom-fields.edit'));

        $definitions = CustomFieldSchema::forContext(CustomFieldSchema::CONTEXT_USER);

        $this->assertCount(1, $definitions);
        $this->assertSame('location', $definitions[0]['key']);
        $this->assertSame('Location', $definitions[0]['label']);
    }

    public function test_admin_custom_fields_page_is_available(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)
            ->get(route('admin.custom-fields.edit'))
            ->assertOk()
            ->assertSee('Custom fields')
            ->assertSee('User profiles');
    }
}
