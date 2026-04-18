<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class AgentApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_an_agent_and_become_its_owner(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/agents', [
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

        $this->assertSame(
            Agent::query()->where('company_name', 'KLabs')->value('id'),
            $user->fresh()->agent_id
        );
    }

    public function test_guest_cannot_access_agent_routes(): void
    {
        $agent = Agent::query()->create([
            'name' => 'K Sales AI',
            'company_name' => 'KLabs',
            'widget_token' => 'widget-token',
        ]);

        $this->getJson("/api/agents/{$agent->id}")->assertUnauthorized();
        $this->putJson("/api/agents/{$agent->id}", [
            'name' => 'Blocked Update',
        ])->assertUnauthorized();
        $this->postJson("/api/agents/{$agent->id}/regenerate-widget-token")->assertUnauthorized();
    }

    public function test_owner_can_view_its_own_agent_settings(): void
    {
        $agent = Agent::query()->create([
            'name' => 'K Sales AI',
            'company_name' => 'KLabs',
            'widget_token' => 'widget-token',
        ]);
        $user = User::factory()->create([
            'agent_id' => $agent->id,
        ]);

        $response = $this->actingAs($user)->getJson("/api/agents/{$agent->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $agent->id)
            ->assertJsonPath('data.name', 'K Sales AI')
            ->assertJsonPath('data.widget_token', 'widget-token');
    }

    public function test_company_cannot_view_another_companys_agent_settings(): void
    {
        $agent = Agent::query()->create([
            'name' => 'K Sales AI',
            'company_name' => 'KLabs',
            'widget_token' => 'widget-token',
        ]);
        $otherUser = User::factory()->create();

        $this->actingAs($otherUser)
            ->getJson("/api/agents/{$agent->id}")
            ->assertForbidden();
    }

    public function test_owner_can_update_its_own_agent_settings(): void
    {
        $agent = Agent::query()->create([
            'name' => 'Old Agent',
            'company_name' => 'Old Company',
            'widget_token' => 'widget-token',
        ]);
        $user = User::factory()->create([
            'agent_id' => $agent->id,
        ]);

        $response = $this->actingAs($user)->putJson("/api/agents/{$agent->id}", [
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

    public function test_owner_can_update_its_company_logo_path(): void
    {
        $agent = Agent::query()->create([
            'name' => 'Logo Agent',
            'company_name' => 'Logo Company',
            'widget_token' => 'widget-token',
        ]);
        $user = User::factory()->create([
            'agent_id' => $agent->id,
        ]);

        $response = $this->actingAs($user)->putJson("/api/agents/{$agent->id}", [
            'logo_path' => 'company-logos/logo.png',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.logo_path', 'company-logos/logo.png');

        $this->assertDatabaseHas('agents', [
            'id' => $agent->id,
            'logo_path' => 'company-logos/logo.png',
        ]);
    }

    public function test_owner_can_store_company_specific_provider_settings_without_exposing_secrets(): void
    {
        $agent = Agent::query()->create([
            'name' => 'Provider Agent',
            'company_name' => 'Provider Company',
            'widget_token' => 'widget-token',
        ]);
        $user = User::factory()->create([
            'agent_id' => $agent->id,
        ]);

        $response = $this->actingAs($user)->putJson("/api/agents/{$agent->id}", [
            'provider_settings' => [
                'openai' => [
                    'enabled' => true,
                    'api_key' => 'sk-company-openai',
                    'base_url' => 'https://api.openai.com/v1',
                    'chat_model' => 'gpt-5.3',
                    'embedding_model' => 'text-embedding-3-large',
                    'timeout' => 45,
                ],
                'qdrant' => [
                    'enabled' => true,
                    'api_key' => 'qdrant-secret',
                    'base_url' => 'http://qdrant.company.test:6333',
                    'collection' => 'company_vectors',
                    'timeout' => 20,
                    'distance' => 'Cosine',
                ],
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('data.provider_settings.openai.enabled', true)
            ->assertJsonPath('data.provider_settings.openai.has_api_key', true)
            ->assertJsonPath('data.provider_settings.qdrant.enabled', true)
            ->assertJsonPath('data.provider_settings.qdrant.has_api_key', true)
            ->assertJsonMissingPath('data.settings.provider_credentials');

        $settings = $agent->fresh()->settings;

        $this->assertSame('sk-company-openai', Crypt::decryptString($settings['provider_credentials']['openai']['api_key']));
        $this->assertSame('qdrant-secret', Crypt::decryptString($settings['provider_credentials']['qdrant']['api_key']));
    }

    public function test_company_cannot_update_another_companys_agent_settings(): void
    {
        $agent = Agent::query()->create([
            'name' => 'Old Agent',
            'company_name' => 'Old Company',
            'widget_token' => 'widget-token',
        ]);
        $otherUser = User::factory()->create();

        $this->actingAs($otherUser)->putJson("/api/agents/{$agent->id}", [
            'name' => 'New Agent',
        ])->assertForbidden();
    }

    public function test_owner_can_regenerate_its_widget_token(): void
    {
        $agent = Agent::query()->create([
            'name' => 'Token Agent',
            'company_name' => 'Token Company',
            'widget_token' => 'old-token',
        ]);
        $user = User::factory()->create([
            'agent_id' => $agent->id,
        ]);

        $response = $this->actingAs($user)->postJson("/api/agents/{$agent->id}/regenerate-widget-token");

        $response->assertOk()
            ->assertJsonPath('data.id', $agent->id);

        $this->assertNotSame('old-token', $agent->fresh()->widget_token);
    }

    public function test_user_with_existing_agent_cannot_create_another_agent(): void
    {
        $existingAgent = Agent::query()->create([
            'name' => 'Existing Agent',
            'company_name' => 'Existing Company',
            'widget_token' => 'existing-token',
        ]);
        $user = User::factory()->create([
            'agent_id' => $existingAgent->id,
        ]);

        $this->actingAs($user)->postJson('/api/agents', [
            'name' => 'Second Agent',
            'company_name' => 'Second Company',
        ])->assertForbidden();
    }
}
