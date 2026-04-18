<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_the_admin_login_page(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_authenticated_user_can_view_the_admin_dashboard(): void
    {
        $agent = Agent::query()->create([
            'name' => 'K-Agent',
            'company_name' => 'K-Agent Demo',
            'widget_token' => 'demo-widget-token',
        ]);

        $user = User::factory()->create([
            'agent_id' => $agent->id,
        ]);

        $this->actingAs($user)
            ->get('/admin')
            ->assertOk();
    }

    public function test_authenticated_user_can_view_the_main_filament_pages(): void
    {
        $agent = Agent::query()->create([
            'name' => 'K-Agent',
            'company_name' => 'K-Agent Demo',
            'widget_token' => 'demo-widget-token',
        ]);

        $user = User::factory()->create([
            'agent_id' => $agent->id,
        ]);

        foreach ([
            '/admin',
            '/admin/agent-settings',
            '/admin/ai-tester',
            '/admin/chat-sessions',
            '/admin/leads',
            '/admin/knowledge-files',
            '/admin/general-settings',
            '/admin/company-profile',
            '/admin/edit-company-profile',
        ] as $path) {
            $this->actingAs($user)
                ->get($path)
                ->assertOk();
        }
    }

    public function test_user_without_an_agent_is_sent_to_one_time_setup(): void
    {
        $user = User::factory()->create([
            'agent_id' => null,
        ]);

        $this->actingAs($user)
            ->get('/admin/agent-setup')
            ->assertOk();

        $this->actingAs($user)
            ->get('/admin/agent-settings')
            ->assertRedirect('/admin/agent-setup');

        $this->actingAs($user)
            ->get('/admin/agents')
            ->assertRedirect('/admin/agent-setup');
    }

    public function test_user_with_an_existing_agent_is_sent_to_agent_settings(): void
    {
        $agent = Agent::query()->create([
            'name' => 'K-Agent',
            'company_name' => 'K-Agent Demo',
            'widget_token' => 'demo-widget-token',
        ]);

        $user = User::factory()->create([
            'agent_id' => $agent->id,
        ]);

        $this->actingAs($user)
            ->get('/admin/agent-settings')
            ->assertOk();

        $this->actingAs($user)
            ->get('/admin/agent-setup')
            ->assertRedirect('/admin/agent-settings');

        $this->actingAs($user)
            ->get('/admin/agents')
            ->assertRedirect('/admin/agent-settings');
    }

    public function test_company_user_can_download_its_own_transcript(): void
    {
        $agent = Agent::query()->create([
            'name' => 'K-Agent',
            'company_name' => 'K-Agent Demo',
            'widget_token' => 'demo-widget-token',
        ]);

        $chatSession = ChatSession::query()->create([
            'agent_id' => $agent->id,
            'visitor_name' => 'Visitor',
            'visitor_email' => 'visitor@example.com',
        ]);

        ChatMessage::query()->create([
            'agent_id' => $agent->id,
            'chat_session_id' => $chatSession->id,
            'role' => 'user',
            'content' => 'Hello from the widget.',
        ]);

        $user = User::factory()->create([
            'agent_id' => $agent->id,
        ]);

        $this->actingAs($user)
            ->get("/admin/chat-sessions/{$chatSession->id}/transcript")
            ->assertOk()
            ->assertHeader('content-type', 'text/plain; charset=UTF-8');
    }

    public function test_company_user_cannot_download_another_companys_transcript(): void
    {
        $agent = Agent::query()->create([
            'name' => 'K-Agent',
            'company_name' => 'K-Agent Demo',
            'widget_token' => 'demo-widget-token',
        ]);

        $otherAgent = Agent::query()->create([
            'name' => 'Other Agent',
            'company_name' => 'Other Demo',
            'widget_token' => 'other-widget-token',
        ]);

        $chatSession = ChatSession::query()->create([
            'agent_id' => $otherAgent->id,
            'visitor_name' => 'Visitor',
        ]);

        $user = User::factory()->create([
            'agent_id' => $agent->id,
        ]);

        $this->actingAs($user)
            ->get("/admin/chat-sessions/{$chatSession->id}/transcript")
            ->assertForbidden();
    }
}
