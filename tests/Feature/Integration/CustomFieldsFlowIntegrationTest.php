<?php

namespace Tests\Feature\Integration;

use App\Models\Post;
use App\Models\User;
use App\Support\CustomFieldSchema;
use App\Support\Features;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Admin-defined profile custom fields end-to-end.
 */
class CustomFieldsFlowIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Features::seedDefaults();

        CustomFieldSchema::save(CustomFieldSchema::CONTEXT_USER, [
            [
                'key' => 'pronouns',
                'label' => 'Pronouns',
                'type' => CustomFieldSchema::TYPE_TEXT,
                'help' => '',
                'required' => false,
            ],
        ]);
    }

    public function test_user_saves_admin_defined_field_and_guest_sees_it_on_author_profile(): void
    {
        $user = User::factory()->create([
            'username' => 'customfieldsuser',
            'name' => 'Custom Fields User',
        ]);

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

        $this->post(route('logout'));

        $this->get(route('authors.show', $user->primaryAlias()).'?tab=about')
            ->assertOk()
            ->assertSee('Pronouns:')
            ->assertSee('they/them');
    }

    public function test_admin_configured_fields_replace_json_editor_on_profile_form(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Pronouns')
            ->assertDontSee('Custom fields (JSON)');
    }
}
