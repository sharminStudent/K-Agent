<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_account_is_created_by_migration(): void
    {
        $user = User::query()->where('email', 'super@agent.com')->first();

        $this->assertNotNull($user);
        $this->assertTrue($user->isSuperAdmin());
    }

    public function test_guest_is_redirected_to_the_shared_admin_login(): void
    {
        $this->get('/super-admin')->assertRedirect('/super-admin/login');
    }

    public function test_super_admin_can_view_global_pages_from_the_super_admin_panel(): void
    {
        $agent = Agent::query()->create([
            'name' => 'Acme Assistant',
            'company_name' => 'Acme Demo',
            'widget_token' => 'demo-widget-token',
        ]);

        $client = User::factory()->create([
            'agent_id' => $agent->id,
        ]);

        $superAdmin = User::query()->where('email', 'super@agent.com')->firstOrFail();

        foreach ([
            '/super-admin',
            '/super-admin/clients',
            '/super-admin/workspace-users',
            '/super-admin/admins',
            '/super-admin/agent-settings',
            '/super-admin/profile',
            '/super-admin/activity-logs',
            '/super-admin/all-chat-sessions',
            '/super-admin/all-leads',
            '/super-admin/all-knowledge-files',
        ] as $path) {
            $this->actingAs($superAdmin)
                ->get($path)
                ->assertOk();
        }

        $this->assertSame($agent->id, $client->agent_id);
    }

    public function test_regular_workspace_user_cannot_access_global_super_admin_resources(): void
    {
        $agent = Agent::query()->create([
            'name' => 'Acme Assistant',
            'company_name' => 'Acme Demo',
            'widget_token' => 'demo-widget-token',
        ]);

        $user = User::factory()->create([
            'agent_id' => $agent->id,
            'is_super_admin' => false,
        ]);

        $this->actingAs($user)
            ->get('/super-admin/clients')
            ->assertForbidden();
    }

    public function test_super_admin_workspace_users_and_admins_are_separated_and_have_view_pages(): void
    {
        $agent = Agent::query()->create([
            'name' => 'Acme Assistant',
            'company_name' => 'Acme Demo',
            'widget_token' => 'demo-widget-token',
        ]);

        $client = User::factory()->create([
            'name' => 'Client User',
            'email' => 'client@example.com',
            'agent_id' => $agent->id,
            'is_super_admin' => false,
        ]);

        $superAdmin = User::query()->where('email', 'super@agent.com')->firstOrFail();

        $this->actingAs($superAdmin)
            ->get('/super-admin/workspace-users')
            ->assertOk()
            ->assertSee('Client User')
            ->assertDontSee('super@agent.com');

        $this->actingAs($superAdmin)
            ->get('/super-admin/admins')
            ->assertOk()
            ->assertSee('super@agent.com')
            ->assertDontSee('client@example.com');

        $this->actingAs($superAdmin)
            ->get('/super-admin/workspace-users/'.$client->getKey())
            ->assertOk()
            ->assertSee('client@example.com');

        $this->actingAs($superAdmin)
            ->get('/super-admin/admins/'.$superAdmin->getKey())
            ->assertOk()
            ->assertSee('super@agent.com');
    }

    public function test_super_admin_can_view_client_provider_api_keys_in_agent_settings(): void
    {
        $agent = Agent::query()->create([
            'name' => 'Acme Assistant',
            'company_name' => 'Acme Demo',
            'widget_token' => 'demo-widget-token',
            'settings' => [
                'provider_credentials' => [
                    'openai' => [
                        'enabled' => true,
                        'api_key' => Crypt::encryptString('openai-secret-key'),
                    ],
                    'qdrant' => [
                        'enabled' => true,
                        'api_key' => Crypt::encryptString('qdrant-secret-key'),
                    ],
                    'railway' => [
                        'enabled' => true,
                        'api_key' => Crypt::encryptString('railway-secret-key'),
                    ],
                ],
            ],
        ]);

        $superAdmin = User::query()->where('email', 'super@agent.com')->firstOrFail();

        $this->actingAs($superAdmin)
            ->get('/super-admin/agent-settings')
            ->assertOk()
            ->assertSee('openai-secret-key')
            ->assertSee('qdrant-secret-key')
            ->assertSee('railway-secret-key')
            ->assertDontSee('Use Client OpenAI Override')
            ->assertDontSee('Use Client Qdrant Override')
            ->assertDontSee('Use Client Railway Override');
    }

    public function test_super_admin_can_view_resolved_platform_provider_api_keys_in_agent_settings(): void
    {
        config()->set('services.openai.api_key', 'platform-openai-key');
        config()->set('services.qdrant.api_key', 'platform-qdrant-key');
        config()->set('services.railway.api_key', 'platform-railway-key');

        $agent = Agent::query()->create([
            'name' => 'Acme Assistant',
            'company_name' => 'Acme Demo',
            'widget_token' => 'demo-widget-token',
            'settings' => [
                'provider_credentials' => [
                    'openai' => [
                        'enabled' => false,
                    ],
                    'qdrant' => [
                        'enabled' => false,
                    ],
                    'railway' => [
                        'enabled' => false,
                    ],
                ],
            ],
        ]);

        $superAdmin = User::query()->where('email', 'super@agent.com')->firstOrFail();

        $this->actingAs($superAdmin)
            ->get('/super-admin/agent-settings')
            ->assertOk()
            ->assertSee('platform-openai-key')
            ->assertSee('platform-qdrant-key')
            ->assertSee('platform-railway-key');
    }

    public function test_invalid_client_openai_placeholder_falls_back_to_platform_key(): void
    {
        config()->set('services.openai.api_key', 'platform-openai-key');

        Agent::query()->create([
            'name' => 'Acme Assistant',
            'company_name' => 'Acme Demo',
            'widget_token' => 'demo-widget-token',
            'settings' => [
                'provider_credentials' => [
                    'openai' => [
                        'api_key' => Crypt::encryptString('client@agent'),
                        'chat_model' => 'gpt-5.3-chat-latest',
                        'embedding_model' => 'text-embedding-3-large',
                    ],
                ],
            ],
        ]);

        $superAdmin = User::query()->where('email', 'super@agent.com')->firstOrFail();

        $this->actingAs($superAdmin)
            ->get('/super-admin/agent-settings')
            ->assertOk()
            ->assertSee('platform-openai-key')
            ->assertDontSee('client@agent');
    }
}
