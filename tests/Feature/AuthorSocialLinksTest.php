<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorSocialLinksTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_save_social_and_crowdfunding_links(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch('/profile', [
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'social_links' => [
                    'patreon' => 'demo-creator',
                    'github' => 'demo-dev',
                ],
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();
        $this->assertSame('demo-creator', $user->social_links['patreon']);
        $this->assertSame('demo-dev', $user->social_links['github']);
        $this->assertSame($user->social_links, $user->primaryAlias()->fresh()->social_links);
    }

    public function test_author_profile_shows_saved_social_links(): void
    {
        $user = User::factory()->create([
            'username' => 'linkedauthor',
            'website' => 'https://example.com',
            'twitter_handle' => 'linkedauthor',
            'social_links' => [
                'patreon' => 'demo-creator',
                'instagram' => 'demo.photo',
            ],
        ]);
        $user->primaryAlias()->update([
            'social_links' => $user->social_links,
        ]);

        $this->get(route('authors.show', $user->primaryAlias()))
            ->assertOk()
            ->assertSee('Patreon', false)
            ->assertSee('Instagram', false)
            ->assertSee('patreon.com/demo-creator', false);

        $this->get(route('authors.show', $user->primaryAlias()).'?tab=about')
            ->assertOk()
            ->assertSee(__('app.social_links'), false)
            ->assertSee('Crowdfunding', false)
            ->assertSee('Patreon', false);
    }

    public function test_profile_edit_renders_collapsible_social_sections(): void
    {
        $user = User::factory()->create([
            'social_links' => ['github' => 'demo-dev'],
        ]);

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee(__('app.social_links'), false)
            ->assertSee('social_links[github]', false)
            ->assertSee(':aria-expanded="open"', false);
    }
}
