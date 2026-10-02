<?php

namespace Tests\Feature\Integration;

use App\Models\User;
use App\Support\Features;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * AI writing tools feature gate on the editor create page.
 */
class AiWritingToolsFlowIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Features::seedDefaults();
    }

    public function test_enabled_feature_shows_ai_panel_on_write_page(): void
    {
        Features::set('ai_writing_tools', true);
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('posts.create'))
            ->assertOk()
            ->assertSee('id="ai-writing-tools"', false)
            ->assertSee('AI writing & grammar assistants', false)
            ->assertSee('id="ai-writing-config"', false)
            ->assertSee('Assist writing', false);
    }

    public function test_disabled_feature_hides_ai_panel_on_write_page(): void
    {
        Features::set('ai_writing_tools', false);
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('posts.create'))
            ->assertOk()
            ->assertDontSee('id="ai-writing-tools"', false)
            ->assertDontSee('AI writing & grammar assistants', false);
    }
}
