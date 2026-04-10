<?php

namespace Tests\Feature;

use App\Models\Agent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgentApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_an_agent_settings_record(): void
    {
        $response = $this->postJson('/api/agents', [
            'name' => 'K Sales AI',
            'company_name' => 'KLabs',
            'website_url' => 'https://klabs.example',
            'industry' => 'Technology',
            'contact_email' => 'owner@klabs.example',
            'support_email' => 'support@klabs.example',
            'support_phone' => '+97311112222',
            'welcome_message' => 'Welcome to KLabs.',
            'fallback_message' => 'I need more company knowledge to answer that.',
            'settings' => [
                'lead_capture_enabled' => true,
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'K Sales AI')
            ->assertJsonPath('data.company_name', 'KLabs')
            ->assertJsonPath('data.settings.lead_capture_enabled', true);

        $this->assertDatabaseHas('agents', [
            'name' => 'K Sales AI',
            'company_name' => 'KLabs',
            'industry' => 'Technology',
            'contact_email' => 'owner@klabs.example',
            'support_email' => 'support@klabs.example',
            'is_active' => true,
        ]);
    }

    public function test_it_shows_agent_settings(): void
    {
        $agent = Agent::query()->create([
            'name' => 'K Sales AI',
            'company_name' => 'KLabs',
            'widget_token' => 'widget-token',
        ]);

        $response = $this->getJson("/api/agents/{$agent->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $agent->id)
            ->assertJsonPath('data.name', 'K Sales AI')
            ->assertJsonPath('data.widget_token', 'widget-token');
    }

    public function test_it_updates_agent_settings(): void
    {
        $agent = Agent::query()->create([
            'name' => 'Old Agent',
            'company_name' => 'Old Company',
            'widget_token' => 'widget-token',
        ]);

        $response = $this->putJson("/api/agents/{$agent->id}", [
            'name' => 'New Agent',
            'company_name' => 'New Company',
            'industry' => 'Education',
            'welcome_message' => 'Hello from the new company.',
            'is_active' => false,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'New Agent')
            ->assertJsonPath('data.company_name', 'New Company')
            ->assertJsonPath('data.industry', 'Education')
            ->assertJsonPath('data.is_active', false);

        $this->assertDatabaseHas('agents', [
            'id' => $agent->id,
            'name' => 'New Agent',
            'company_name' => 'New Company',
            'industry' => 'Education',
            'is_active' => false,
        ]);
    }

    public function test_it_regenerates_the_widget_token(): void
    {
        $agent = Agent::query()->create([
            'name' => 'Token Agent',
            'company_name' => 'Token Company',
            'widget_token' => 'old-token',
        ]);

        $response = $this->postJson("/api/agents/{$agent->id}/regenerate-widget-token");

        $response->assertOk()
            ->assertJsonPath('data.id', $agent->id);

        $this->assertNotSame('old-token', $agent->fresh()->widget_token);
    }
}
